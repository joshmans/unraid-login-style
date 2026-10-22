<?php
/* Settings live on flash (/boot/config/plugins/unraid-login-style/config.json) and are only written when the
 * user saves. Nothing here touches the login page: that is include/inject.php and scripts/apply. */

function ls_plugin_dir(): string { return dirname(__DIR__); }
function ls_config_path(): string { return getenv('LS_CONFIG') ?: '/boot/config/plugins/unraid-login-style/config.json'; }

const LS_PRESETS = ['midnight', 'graphite', 'ember', 'custom'];
const LS_MAX_IMAGE_BYTES = 1500000;

function ls_defaults(): array {
    return [
        'enabled'  => true,
        'mode'     => 'dark',          // dark: always dark; follow: dark or light with Unraid's own theme
        'preset'   => 'midnight',
        'custom'   => ['bg1' => '#0b1020', 'bg2' => '#16305a', 'a1' => '#4f7cf0', 'a2' => '#3fa7b8'],
        'bg_image' => '',              // what the user typed: an https:// URL or a file on the server
        'bg_dim'   => 0.55,            // how much of the background colour is laid over the image
        'logo'     => '',              // same, for the logo that replaces the Unraid wordmark
        'resolved' => ['bg_image' => '', 'logo' => ''],   // what the CSS uses: a URL, or a data: URI read from the file
    ];
}

function ls_load_config(?string $file = null): array {
    $cfg = ls_defaults();
    $j = json_decode((string)@file_get_contents($file ?? ls_config_path()), true);
    if (!is_array($j)) return $cfg;
    foreach (['enabled' => 'is_bool', 'mode' => 'is_string', 'preset' => 'is_string', 'bg_image' => 'is_string', 'logo' => 'is_string'] as $k => $fn) {
        if (isset($j[$k]) && $fn($j[$k])) $cfg[$k] = $j[$k];
    }
    if (isset($j['bg_dim']) && is_numeric($j['bg_dim'])) $cfg['bg_dim'] = max(0.0, min(0.9, (float)$j['bg_dim']));
    foreach (['custom', 'resolved'] as $k) {
        if (isset($j[$k]) && is_array($j[$k])) $cfg[$k] = array_replace($cfg[$k], array_intersect_key($j[$k], $cfg[$k]));
    }
    if (!in_array($cfg['mode'], ['dark', 'follow'], true)) $cfg['mode'] = 'dark';
    if (!in_array($cfg['preset'], LS_PRESETS, true)) $cfg['preset'] = 'midnight';
    foreach ($cfg['custom'] as $k => $v) if (!ls_is_hex($v)) $cfg['custom'][$k] = ls_defaults()['custom'][$k];
    foreach ($cfg['resolved'] as $k => $v) if (!ls_safe_css_url((string)$v)) $cfg['resolved'][$k] = '';
    return $cfg;
}

function ls_save_config(array $cfg, ?string $file = null): bool {
    $file ??= ls_config_path();
    $json = json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    if (is_file($file) && file_get_contents($file) === $json) return true;
    if (!is_dir(dirname($file)) && !@mkdir(dirname($file), 0755, true)) return false;
    $tmp = $file . '.' . getmypid();
    if (@file_put_contents($tmp, $json) === false) return false;
    return @rename($tmp, $file);
}

function ls_is_hex($v): bool { return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) === 1; }

/** A value that is safe inside url("..."): an https URL or an image data URI, with nothing that could end the string or the rule. */
function ls_safe_css_url(string $u): bool {
    if ($u === '') return true;
    if (strlen($u) > 2100000 || preg_match('/["\'()\\\\<>\s]/', $u)) return false;
    return preg_match('#^https://[^/]+/#i', $u) === 1 || preg_match('#^data:image/(png|jpeg|gif|webp|svg\+xml);base64,[A-Za-z0-9+/=]+$#', $u) === 1;
}

/**
 * Turns what the user typed into something the CSS can use. An https URL is used as it is (the visitor's browser loads it);
 * a file on the server is read once, here, and kept as a data URI, because the login page is served to people who are not
 * signed in and so can't be given a path on the server.
 * Returns [resolved, error]; an empty input is fine and gives ['', null].
 */
function ls_resolve_image(string $in): array {
    $in = trim($in);
    if ($in === '') return ['', null];
    if (preg_match('#^https://#i', $in)) {
        return ls_safe_css_url($in) ? [$in, null] : ['', 'That URL has characters that are not allowed (spaces, quotes or brackets).'];
    }
    if ($in[0] !== '/') return ['', 'Use an https:// address, or the full path of a file on the server.'];
    $real = realpath($in);
    if ($real === false || !is_file($real)) return ['', 'No such file: ' . $in];
    if (!preg_match('#^(/boot/|/mnt/|/tmp/)#', $real) && !getenv('LS_ALLOW_ANY_PATH')) return ['', 'The file must be under /boot, /mnt or /tmp.'];
    $size = filesize($real);
    if ($size === false || $size > LS_MAX_IMAGE_BYTES) return ['', 'That file is larger than ' . round(LS_MAX_IMAGE_BYTES / 1e6, 1) . ' MB; shrink it first.'];
    $types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
    $mime = $types[strtolower(pathinfo($real, PATHINFO_EXTENSION))] ?? null;
    if ($mime === null) return ['', 'Use a PNG, JPEG, GIF, WebP or SVG file.'];
    return ['data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($real)), null];
}

/** Validates what the form sent and lays it over $current. Returns [config, errors]. */
function ls_apply_post(array $current, array $post): array {
    $cfg = $current; $err = [];
    $cfg['enabled'] = !empty($post['enabled']);
    if (isset($post['mode']) && in_array($post['mode'], ['dark', 'follow'], true)) $cfg['mode'] = $post['mode'];
    if (isset($post['preset']) && in_array($post['preset'], LS_PRESETS, true)) $cfg['preset'] = $post['preset'];
    foreach (array_keys($cfg['custom']) as $k) {
        $v = $post['custom_' . $k] ?? null;
        if ($v === null) continue;
        if (ls_is_hex($v)) $cfg['custom'][$k] = strtolower($v); else $err[] = "Colour \"$k\" must look like #1a2b3c.";
    }
    if (isset($post['bg_dim']) && is_numeric($post['bg_dim'])) $cfg['bg_dim'] = max(0.0, min(0.9, (float)$post['bg_dim']));
    foreach (['bg_image' => 'Background image', 'logo' => 'Logo'] as $k => $label) {
        if (!isset($post[$k])) continue;
        [$res, $e] = ls_resolve_image((string)$post[$k]);
        if ($e !== null) { $err[] = "$label: $e"; continue; }
        $cfg[$k] = trim((string)$post[$k]);
        $cfg['resolved'][$k] = $res;
    }
    return [$cfg, $err];
}
