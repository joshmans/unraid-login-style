<?php
/* Runs scripts/apply against copies of real login pages from Unraid releases. */
require __DIR__ . '/lib.php';
$script = __DIR__ . '/../source/usr/local/emhttp/plugins/unraid-login-style/scripts/apply';

function run(string $page, string $cmd, string $d): array {
    $env = 'LS_LOGIN_PAGE=' . escapeshellarg($page) . ' LS_BACKUP_DIR=' . escapeshellarg("$d/bak") . ' LS_INJECT=/x/inject.php';
    exec("$env php " . escapeshellarg($GLOBALS['script']) . " $cmd 2>&1", $o, $rc);
    return [$rc, implode("\n", $o)];
}

foreach (glob(__DIR__ . '/fixtures/login.*.php') as $fx) {
    $name = basename($fx);
    $d = ls_tmpdir();
    $page = "$d/.login.php";
    copy($fx, $page);
    $orig = file_get_contents($page);
    chmod($page, 0640);

    check("$name: starts not applied", run($page, 'status', $d) === [0, 'not applied']);
    [$rc, $m] = run($page, 'apply', $d);
    check("$name: apply", $rc === 0 && $m === 'applied', $m);
    $new = file_get_contents($page);
    check("$name: block sits right before </head>", (bool)preg_match('#<!-- unraid-login-style:end -->\n</head>#', $new));
    check("$name: still parses", (function () use ($page) { exec('php -l ' . escapeshellarg($page), $o, $rc); return $rc === 0; })());
    check("$name: mode kept", (fileperms($page) & 0777) === 0640);
    check("$name: status says applied", run($page, 'status', $d) === [0, 'applied']);
    check("$name: stock page backed up", count(glob("$d/bak/.login.php.*")) === 1 && file_get_contents(glob("$d/bak/.login.php.*")[0]) === $orig);
    [$rc, $m] = run($page, 'apply', $d);
    check("$name: second apply changes nothing", $rc === 0 && file_get_contents($page) === $new, $m);
    check("$name: exactly one block", substr_count(file_get_contents($page), 'unraid-login-style:begin') === 1);
    [$rc, $m] = run($page, 'remove', $d);
    check("$name: remove gives the original back byte for byte", $rc === 0 && file_get_contents($page) === $orig, $m);
    check("$name: remove again is harmless", run($page, 'remove', $d)[0] === 0);

    // an Unraid update changed the page after we applied: remove must not put an old copy back
    run($page, 'apply', $d);
    $updated = str_replace('<title>', '<title>NEW ', file_get_contents($page));
    file_put_contents($page, $updated);
    run($page, 'remove', $d);
    check("$name: remove keeps later changes to the page", str_contains(file_get_contents($page), 'NEW ') && !str_contains(file_get_contents($page), 'unraid-login-style'));
}

// pages the patch must refuse
$d = ls_tmpdir();
file_put_contents("$d/none.php", "<?php echo 1; ?>no head here");
[$rc] = run("$d/none.php", 'apply', $d);
check('refuses a page with no </head>', $rc !== 0 && !str_contains(file_get_contents("$d/none.php"), 'unraid-login-style'));
file_put_contents("$d/two.php", "<?php ?></head></head>");
check('refuses a page with two </head>', run("$d/two.php", 'apply', $d)[0] !== 0);
check('missing page is an error', run("$d/missing.php", 'apply', $d)[0] !== 0);
finish('apply');
