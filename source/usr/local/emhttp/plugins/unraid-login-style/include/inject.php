<?php
/* Included from Unraid's login page (see scripts/apply) just before </head>. It runs for people who are not signed in,
 * so it must never fail loudly: any problem means the stock login page, not a broken one. */
try {
    require_once __DIR__ . '/style.php';
    $ls_cfg = ls_load_config();
    if ($ls_cfg['enabled']) {
        $ls_light = $ls_cfg['mode'] === 'follow' && isset($isDarkTheme) && !$isDarkTheme;
        echo "<style>\n" . ls_css($ls_cfg, $ls_light) . "</style>\n";
    }
} catch (Throwable $e) {
}
