<?php
/* Builds the stylesheet that restyles Unraid's login page. Pure functions: no file or request access, so the page
 * preview and the real login page get exactly the same CSS. */

require_once __DIR__ . '/config.php';

function ls_presets(): array {
    return [
        'midnight' => ['label' => 'Midnight', 'bg1' => '#0b1020', 'bg2' => '#16305a', 'a1' => '#4f7cf0', 'a2' => '#3fa7b8'],
        'graphite' => ['label' => 'Graphite', 'bg1' => '#111113', 'bg2' => '#25262b', 'a1' => '#6b7280', 'a2' => '#c3c8d2'],
        'ember'    => ['label' => 'Ember',    'bg1' => '#130e0e', 'bg2' => '#2a1810', 'a1' => '#e22828', 'a2' => '#ff8c2f'],
    ];
}

function ls_colors(array $cfg): array {
    return $cfg['preset'] === 'custom' ? $cfg['custom'] : (ls_presets()[$cfg['preset']] ?? ls_presets()['midnight']);
}

function ls_rgba(string $hex, float $a): string {
    return sprintf('rgba(%d,%d,%d,%s)', hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)), rtrim(rtrim(number_format($a, 2, '.', ''), '0'), '.') ?: '0');
}

/** $light is true only when the user chose "follow Unraid's theme" and Unraid is on a light theme. */
function ls_css(array $cfg, bool $light = false): string {
    $c = ls_colors($cfg);
    $img = $cfg['resolved']['bg_image'] ?? '';
    $logo = $cfg['resolved']['logo'] ?? '';
    $dim = (float)$cfg['bg_dim'];
    if ($light) {
        $v = ['page1' => '#f4f6fa', 'page2' => '#dfe5ef', 'card' => '#ffffff', 'ink' => '#1c1b1b', 'soft' => '#5b6272',
              'input' => '#f2f2f2', 'line' => '#c9cfdb', 'shadow' => '0 8px 30px rgba(20,30,60,.18)'];
    } else {
        $v = ['page1' => $c['bg1'], 'page2' => $c['bg2'], 'card' => '#141824', 'ink' => '#e9edf6', 'soft' => '#9aa3b8',
              'input' => '#0d111b', 'line' => '#2b3348', 'shadow' => '0 10px 40px rgba(0,0,0,.55)'];
    }
    $grad = "linear-gradient(90deg,{$c['a1']},{$c['a2']})";
    $page = "linear-gradient(160deg,{$v['page1']},{$v['page2']})";
    $body = $img === ''
        ? $page
        : sprintf('linear-gradient(160deg,%s,%s),url("%s") center/cover', ls_rgba($v['page1'], $dim), ls_rgba($v['page2'], $dim), $img);

    $css = <<<CSS
/* unraid-login-style */
html{background:{$v['page1']};background:{$body} fixed no-repeat;min-height:100%}
body{background:none!important;color:{$v['ink']}!important}
#login{background:{$v['card']}!important;box-shadow:{$v['shadow']}!important;border:1px solid {$v['line']};overflow:hidden}
#login .logo{border-radius:0!important}
.angle:after{background:{$grad}!important}
h1{color:{$v['ink']}}
h2{color:{$v['soft']}}
a{color:{$c['a2']}!important}
a:hover{color:{$v['ink']}!important}
[type=text],[type=password],[type=email],[type=number],[type=search],[type=tel],[type=url],textarea{background-color:{$v['input']}!important;color:{$v['ink']}!important;border:1px solid {$v['line']}!important;border-radius:6px!important}
[type=text]:focus,[type=password]:focus,[type=email]:focus,[type=number]:focus,[type=search]:focus,[type=tel]:focus,[type=url]:focus,textarea:focus{border-color:{$c['a2']}!important;box-shadow:0 0 0 3px {$c['a2']}33}
::placeholder{color:{$v['soft']}!important;opacity:1}
.button,.button--small{background:{$grad}!important;background-size:auto!important;color:#fff!important;border:0!important;border-radius:6px!important;box-shadow:none!important}
.button:hover{filter:brightness(1.12);color:#fff!important}
#login .error{color:#ff6b6b}
@media (max-width:500px){body{background:{$v['card']}!important}#login{border:0}}

CSS;
    if ($logo !== '') {
        $css .= "#login .wordmark{position:relative;z-index:1;height:72px;margin:24px 2rem;padding:0!important;background:url(\"$logo\") left center/contain no-repeat}\n"
              . "#login .wordmark svg{display:none}\n";
    }
    return $css;
}
