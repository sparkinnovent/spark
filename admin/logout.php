<?php
/**
 * Administrator sign-out.
 *
 * FIX L5 — see the participant logout. The cookie has to be expired as well as
 * the server-side session destroyed, otherwise the browser keeps replaying an
 * identifier for a session that no longer exists.
 */

require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../Util.php';

$util = new Util();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => (bool)$p['secure'],
            'httponly' => (bool)$p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();

header('Location: ./');
exit;
