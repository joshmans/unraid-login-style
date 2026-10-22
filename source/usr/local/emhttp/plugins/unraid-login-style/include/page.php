<?php
/* The Settings page. Server-rendered; the preview is the login page's own markup with the same CSS the real page gets. */
require_once __DIR__ . '/style.php';

$cfg   = ls_load_config();
$csrf  = $GLOBALS['var']['csrf_token'] ?? '';
$ver   = trim((string)@file_get_contents(ls_plugin_dir() . '/VERSION')) ?: 'dev';
$state = trim((string)@shell_exec('php -q ' . escapeshellarg(ls_plugin_dir() . '/scripts/apply') . ' status 2>&1'));
$flash = $_GET['ls'] ?? '';
$notes = is_file('/tmp/unraid-login-style.notes') ? array_filter(explode("\n", (string)file_get_contents('/tmp/unraid-login-style.notes'))) : [];
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

$base = 'body{margin:0;font:14px clear-sans,sans-serif;background:#1c1b1b;color:#fff}'
      . '#login{width:380px;max-width:92%;margin:28px auto;border-radius:10px;background:#2b2a29}'
      . '#login .logo{position:relative;overflow:hidden;height:96px;border-radius:10px 10px 0 0}'
      . '.wordmark{position:relative;z-index:1;padding:1.6rem 2rem}.wordmark svg{width:90px}'
      . '.angle:after{content:"";position:absolute;top:0;left:0;width:100%;height:96px;transform-origin:bottom left;transform:skewY(-6deg)}'
      . '.content{padding:1.4rem 2rem 1.8rem}h1{font-size:1.6em;margin:0}h2{font-size:.8em;margin:0 0 1.2em}'
      . 'input{width:100%;box-sizing:border-box;padding:.65rem .9rem;margin-bottom:.8rem;font:inherit}'
      . 'button{padding:.7rem 1.4rem;font:600 .85rem clear-sans,sans-serif;text-transform:uppercase;letter-spacing:2px;cursor:pointer}'
      . 'a{text-transform:uppercase;font-weight:bold;letter-spacing:2px;font-size:.8em;text-decoration:none;display:block;margin-top:1rem}';
$mock = '<div id="login"><div class="logo angle"><div class="wordmark"><svg viewBox="0 0 222.4 39"><path fill="#fff" d="M146.7 29.5H135l-3 9h-6.5L138.9 0h8l13.4 38.5h-7.1L142.6 6.9l-5.8 16.9h8.2l1.7 5.7zM29.7 0v25.4c0 8.9-5.8 13.6-14.9 13.6C5.8 39 0 34.3 0 25.4V0h6.5v25.4c0 5.2 3.2 7.9 8.2 7.9 5.2 0 8.4-2.7 8.4-7.9V0h6.6zM50.9 12v26.5h-6.5V0h6.1l17 26.5V0H74v38.5h-6.1L50.9 12zM171.3 0h6.5v38.5h-6.5V0zM222.4 24.7c0 9-5.9 13.8-15.2 13.8h-14.5V0h14.6c9.2 0 15.1 4.8 15.1 13.8v10.9zm-6.6-10.9c0-5.3-3.3-8.1-8.5-8.1h-8.1v27.1h8c5.3 0 8.6-2.8 8.6-8.1V13.8zM108.3 23.9c4.3-1.6 6.9-5.3 6.9-11.5 0-8.7-5.1-12.4-12.8-12.4H88.8v38.5h6.5V5.7h6.9c3.8 0 6.2 1.8 6.2 6.7s-2.4 6.8-6.2 6.8h-3.4l9.2 19.4h7.5l-7.2-14.7z"/></svg></div></div>'
      . '<div class="content"><h1>tower</h1><h2>Media server</h2><input type="text" placeholder="Username"><input type="password" placeholder="Password">'
      . '<button class="button button--small">Login</button><a href="#">Password recovery</a></div></div>';
$preview = '<!doctype html><meta charset="utf-8"><style>' . $base . '</style><style>' . ls_css($cfg, false) . '</style>' . $mock;
if ($cfg['mode'] === 'follow') $previewLight = '<!doctype html><meta charset="utf-8"><style>' . $base . '</style><style>' . ls_css($cfg, true) . '</style>' . $mock;
?>
<style>
 .ls-wrap{max-width:980px}
 .ls-grid{display:grid;grid-template-columns:minmax(300px,1fr) minmax(300px,420px);gap:28px;align-items:start}
 .ls-wrap dl{margin:0}.ls-wrap dt{margin-top:14px;font-weight:bold}.ls-wrap dd{margin:4px 0 0}
 .ls-wrap input[type=text]{width:100%;box-sizing:border-box}
 .ls-colors{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.ls-colors label{display:block;font-size:12px}
 .ls-colors input[type=color]{width:100%;height:34px;padding:0;border:0;background:none}
 .ls-note{margin:10px 0;padding:8px 12px;border-radius:4px;background:rgba(127,127,127,.15)}
 .ls-ok{border-left:4px solid #4c9a2a}.ls-bad{border-left:4px solid #c0392b}
 .ls-preview{width:100%;height:430px;border:1px solid rgba(127,127,127,.4);border-radius:6px;background:#111}
 .ls-small{font-size:12px;opacity:.75}
</style>
<div class="ls-wrap">
<?php if ($flash === 'saved'): ?><div class="ls-note ls-ok">Saved. Sign out (or open a private window) to see the new login page.</div>
<?php elseif ($flash === 'saved-with-notes'): ?><div class="ls-note ls-bad">Saved, but some values were not used:<ul><?php foreach ($notes as $n) echo '<li>' . $e($n) . '</li>'; ?></ul></div>
<?php elseif ($flash === 'failed'): ?><div class="ls-note ls-bad">Could not save the settings to the flash drive.</div><?php endif; ?>
<?php if ($state !== 'applied'): ?><div class="ls-note ls-bad">The login page is not patched yet (<?= $e($state) ?>). Run <code>php /usr/local/emhttp/plugins/unraid-login-style/scripts/apply apply</code>, or reinstall the plugin.</div><?php endif; ?>

<div class="ls-grid">
<form method="POST" action="/plugins/unraid-login-style/include/save.php">
  <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">
  <dl>
    <dt>Login style</dt>
    <dd><label><input type="checkbox" name="enabled" value="1" <?= $cfg['enabled'] ? 'checked' : '' ?>> Use this style on the login page</label>
        <div class="ls-small">Off puts the stock Unraid login back at once, without uninstalling.</div></dd>

    <dt>Mode</dt>
    <dd><select name="mode">
      <option value="dark" <?= $cfg['mode'] === 'dark' ? 'selected' : '' ?>>Always dark</option>
      <option value="follow" <?= $cfg['mode'] === 'follow' ? 'selected' : '' ?>>Follow Unraid's theme (light when Unraid is light)</option>
    </select></dd>

    <dt>Colours</dt>
    <dd><select name="preset">
      <?php foreach (ls_presets() as $k => $p) echo '<option value="' . $k . '"' . ($cfg['preset'] === $k ? ' selected' : '') . '>' . $e($p['label']) . '</option>'; ?>
      <option value="custom" <?= $cfg['preset'] === 'custom' ? 'selected' : '' ?>>Custom (below)</option>
    </select></dd>
    <dd class="ls-colors">
      <?php foreach (['bg1' => 'Page, top', 'bg2' => 'Page, bottom', 'a1' => 'Accent, start', 'a2' => 'Accent, end'] as $k => $label): ?>
        <label><?= $e($label) ?><input type="color" name="custom_<?= $k ?>" value="<?= $e($cfg['custom'][$k]) ?>"></label>
      <?php endforeach; ?>
    </dd>
    <dd class="ls-small">The custom colours are used only when the choice above is Custom.</dd>

    <dt>Background image</dt>
    <dd><input type="text" name="bg_image" value="<?= $e($cfg['bg_image']) ?>" placeholder="https://… or /mnt/user/appdata/…/background.jpg"></dd>
    <dd class="ls-small">An https:// address, or a file on the server (PNG, JPEG, WebP, GIF or SVG under 1.5 MB). A file is read when you save and kept inside the style, so it shows to people who are not signed in.</dd>
    <dd>Colour laid over the image: <input type="range" name="bg_dim" min="0" max="0.9" step="0.05" value="<?= $e($cfg['bg_dim']) ?>"></dd>

    <dt>Logo</dt>
    <dd><input type="text" name="logo" value="<?= $e($cfg['logo']) ?>" placeholder="https://… or a file path; empty keeps the Unraid wordmark"></dd>
  </dl>
  <p><input type="submit" value="Apply"></p>
  <p class="ls-small">Version <?= $e($ver) ?>. Unraid has no setting for this page, so the plugin adds one line to the login page file at install and every boot, and takes it out when you remove the plugin. It does not touch how signing in works.</p>
</form>
<div>
  <iframe class="ls-preview" sandbox srcdoc="<?= $e($preview) ?>" title="Preview"></iframe>
  <?php if (isset($previewLight)): ?><iframe class="ls-preview" style="margin-top:12px" sandbox srcdoc="<?= $e($previewLight) ?>" title="Light preview"></iframe><div class="ls-small">Second preview: how it looks when Unraid is on a light theme.</div><?php endif; ?>
  <div class="ls-small">Preview of the saved settings, not of unsaved changes. Press Apply, then check.</div>
</div>
</div>
</div>
