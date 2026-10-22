<?php
require __DIR__ . '/lib.php';
require __DIR__ . '/../source/usr/local/emhttp/plugins/unraid-login-style/include/style.php';

// defaults and damaged files
$d = ls_tmpdir();
check('missing file gives defaults', ls_load_config("$d/none.json") === ls_defaults());
file_put_contents("$d/bad.json", '{not json');
check('damaged file gives defaults', ls_load_config("$d/bad.json") === ls_defaults());
file_put_contents("$d/evil.json", json_encode(['preset' => 'x', 'mode' => 'y', 'custom' => ['a1' => 'red;}'], 'resolved' => ['logo' => 'https://a/b");}body{display:none']]));
$c = ls_load_config("$d/evil.json");
check('unknown preset and mode fall back', $c['preset'] === 'midnight' && $c['mode'] === 'dark');
check('bad colour falls back', $c['custom']['a1'] === ls_defaults()['custom']['a1']);
check('unsafe resolved url is dropped', $c['resolved']['logo'] === '');

// round trip, and a no-change save does not rewrite
$cfg = ls_defaults(); $cfg['preset'] = 'ember';
check('save works', ls_save_config($cfg, "$d/c.json"));
$m = filemtime("$d/c.json"); touch("$d/c.json", $m - 100); clearstatcache();
ls_save_config($cfg, "$d/c.json"); clearstatcache();
check('unchanged save leaves the file alone', filemtime("$d/c.json") === $m - 100);
check('round trip', ls_load_config("$d/c.json")['preset'] === 'ember');

// url safety
foreach (['https://x.test/a.png', 'data:image/png;base64,AAAA', ''] as $u) check("safe: $u", ls_safe_css_url($u));
foreach (['http://x.test/a.png', 'https://x.test/a"b', 'https://x.test/a b', 'https://x.test/a)b', "https://x.test/a'b", 'javascript:alert(1)', 'data:text/html;base64,AAAA', 'https://x.test/a\\b', 'https://x.test/</style>'] as $u) check("unsafe: $u", !ls_safe_css_url($u));

// images: local files become data URIs, with limits
putenv('LS_ALLOW_ANY_PATH=1');
file_put_contents("$d/a.png", "\x89PNG\r\n");
[$r, $e] = ls_resolve_image("$d/a.png");
check('file becomes a data uri', $e === null && str_starts_with($r, 'data:image/png;base64,') && ls_safe_css_url($r));
check('missing file is an error', ls_resolve_image("$d/nope.png")[1] !== null);
file_put_contents("$d/a.txt", 'x');
check('wrong type is an error', ls_resolve_image("$d/a.txt")[1] !== null);
file_put_contents("$d/big.png", str_repeat('x', LS_MAX_IMAGE_BYTES + 1));
check('too big is an error', ls_resolve_image("$d/big.png")[1] !== null);
check('relative path is an error', ls_resolve_image('a.png')[1] !== null);
check('empty is fine', ls_resolve_image('  ') === ['', null]);
putenv('LS_ALLOW_ANY_PATH');
check('outside allowed folders is refused', ls_resolve_image('/etc/hostname')[1] !== null);

// the form
[$c, $err] = ls_apply_post(ls_defaults(), ['enabled' => '1', 'mode' => 'follow', 'preset' => 'custom', 'custom_a1' => '#FF0000', 'custom_a2' => 'blue', 'bg_dim' => '5', 'bg_image' => 'http://insecure/x.png']);
check('form: enabled and mode', $c['enabled'] && $c['mode'] === 'follow' && $c['preset'] === 'custom');
check('form: colour lowercased', $c['custom']['a1'] === '#ff0000');
check('form: bad colour reported and kept off', $c['custom']['a2'] === ls_defaults()['custom']['a2'] && count($err) >= 2, implode('|', $err));
check('form: dim clamped', $c['bg_dim'] === 0.9);
check('form: bad image not stored', $c['bg_image'] === '' && $c['resolved']['bg_image'] === '');
[$c] = ls_apply_post(ls_defaults(), []);
check('unticked checkbox means off', $c['enabled'] === false);

// css
$css = ls_css(ls_defaults());
check('css has the accent', str_contains($css, '#4f7cf0') && str_contains($css, '#3fa7b8'));
check('css is dark', str_contains($css, '#141824'));
check('gradient is on html, not body', str_contains($css, 'html{background:#0b1020;background:linear-gradient') && str_contains($css, 'body{background:none!important'));
check('css light variant differs', str_contains(ls_css(ls_defaults(), true), '#ffffff') && !str_contains(ls_css(ls_defaults(), true), '#141824'));
$cfg = ls_defaults(); $cfg['resolved'] = ['bg_image' => 'https://x.test/b.jpg', 'logo' => 'https://x.test/l.png'];
$css = ls_css($cfg);
check('css uses the image under the colour', str_contains($css, 'url("https://x.test/b.jpg")') && str_contains($css, 'rgba('));
check('css swaps the logo', str_contains($css, 'url("https://x.test/l.png")') && str_contains($css, '.wordmark svg{display:none}'));
check('no logo keeps the wordmark', !str_contains(ls_css(ls_defaults()), '.wordmark svg{display:none}'));
check('rgba formatting', ls_rgba('#ff8000', 0.5) === 'rgba(255,128,0,0.5)' && ls_rgba('#000000', 0.0) === 'rgba(0,0,0,0)');
finish('config');
