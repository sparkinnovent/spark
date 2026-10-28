<?php
/**
 * Participant sign-out.
 *
 * FIX L5 — the original set one session key to an empty string and called
 * session_destroy(). That removes the server-side data file but leaves the
 * PHPSESSID cookie sitting in the browser, so the client keeps presenting a
 * dead identifier. Unset the array, expire the cookie, then destroy.
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/Util.php';

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
