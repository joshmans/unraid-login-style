<?php
/* Tiny test kit: check(), and temp dirs. */
$GLOBALS['ls_fail'] = 0;
$GLOBALS['ls_pass'] = 0;

function check(string $label, bool $ok, string $detail = ''): void {
    if ($ok) { $GLOBALS['ls_pass']++; return; }
    $GLOBALS['ls_fail']++;
    echo "FAIL: $label" . ($detail !== '' ? "\n      $detail" : '') . "\n";
}

function finish(string $name): void {
    echo "$name: {$GLOBALS['ls_pass']} passed, {$GLOBALS['ls_fail']} failed\n";
    exit($GLOBALS['ls_fail'] ? 1 : 0);
}

function ls_tmpdir(): string {
    $d = sys_get_temp_dir() . '/ls-test-' . bin2hex(random_bytes(4));
    mkdir($d, 0777, true);
    return $d;
}
