<?php
/* Saves the settings form. POST only: Unraid's CSRF check in local_prepend.php runs before this file does. */
require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
[$cfg, $err] = ls_apply_post(ls_load_config(), $_POST);
$ok = ls_save_config($cfg);
$msg = $ok ? ($err ? 'saved-with-notes' : 'saved') : 'failed';
if ($ok && $err) @file_put_contents('/tmp/unraid-login-style.notes', implode("\n", $err));
elseif (is_file('/tmp/unraid-login-style.notes')) @unlink('/tmp/unraid-login-style.notes');
header('Location: /Settings/LoginStyle?ls=' . $msg);
