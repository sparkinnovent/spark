<?php
/**
 * Spark / GDIID 2025 — shared security helpers.
 *
 * Loaded by every entry point that reads input or writes state. Provides:
 *   - session bootstrap with hardened cookie flags
 *   - CSRF token issue + verify
 *   - output escaping
 *   - safe upload handling (extension whitelist, random names, MIME probe)
 *   - authenticated file delivery
 *   - a prepared-statement wrapper so callers stop interpolating SQL
 *
 * Nothing here echoes. Nothing here redirects on its own except csrf_verify()
 * and require_*_auth(), which are explicit by design.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Anti-fixation: never adopt an id the server did not mint itself.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/* ------------------------------------------------------------------ */
/* CSRF                                                               */
/* ------------------------------------------------------------------ */

/** Current token, created on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Abort unless the request carries a valid token.
 * Call at the top of every state-changing POST handler.
 */
function csrf_verify(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session token mismatch. Reload the page and try again.');
    }
}

/**
 * Destructive GET endpoints must carry a token in the query string, because
 * there is no form. Compared in constant time.
 */
function csrf_verify_query(): void
{
    $sent = $_GET['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session token mismatch. Reload the page and try again.');
    }
}

/** Append the CSRF token to an internal URL. */
function csrf_url(string $url): string
{
    $sep = str_contains($url, '?') ? '&' : '?';
    return $url . $sep . 'csrf=' . urlencode(csrf_token());
}

/**
 * Insert a hidden token into every <form method="post"> as the page is flushed.
 *
 * The console has POST forms spread across a dozen templates, and a single
 * missed one silently breaks a workflow once csrf_verify() is switched on. This
 * guarantees coverage from one call site per page instead of thirty edits.
 *
 * Forms that already carry a token are left alone.
 */
function csrf_auto_inject(): void
{
    static $started = false;
    if ($started) {
        return;
    }
    $started = true;

    ob_start(static function (string $html): string {
        return csrf_inject_forms($html);
    });
}

/**
 * Pure transform: add a token to each POST form.
 * Separated from the output-buffer hook so it can be tested directly.
 */
function csrf_inject_forms(string $html): string
{
    if ($html === '' || stripos($html, '<form') === false) {
        return $html;
    }

    // Walk form tags in order so each one's body can be inspected.
    if (!preg_match_all('#<form\b[^>]*>#i', $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $html;
    }

    $out = '';
    $cursor = 0;
    $field = csrf_field();

    foreach ($matches[0] as $i => [$tag, $offset]) {
        $bodyStart = $offset + strlen($tag);
        $bodyEnd = isset($matches[0][$i + 1])
            ? $matches[0][$i + 1][1]
            : strlen($html);
        $body = substr($html, $bodyStart, $bodyEnd - $bodyStart);

        // Everything before this tag, then the tag itself.
        $out .= substr($html, $cursor, $offset - $cursor);
        $out .= $tag;

        $isPost = preg_match('#method\s*=\s*(["\']?)post\1#i', $tag) === 1;
        $already = stripos($body, 'name="csrf_token"') !== false
                || stripos($body, "name='csrf_token'") !== false;

        // Only POST forms, and never a second copy.
        if ($isPost && !$already) {
            $out .= $field;
        }

        $cursor = $bodyStart;
    }

    $out .= substr($html, $cursor);
    return $out;
}

/* ------------------------------------------------------------------ */
/* Output escaping                                                    */
/* ------------------------------------------------------------------ */

/** Escape for HTML text and attribute contexts. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape for a JS string literal inside a <script> block. */
function e_js($value): string
{
    return json_encode((string)($value ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Render a status message that may contain one whitelisted mailto anchor.
 *
 * Everything is escaped first. Each mailto anchor is then lifted out into an
 * opaque placeholder, and only after the whole string has been escaped and
 * placeholder-substituted is the anchor rebuilt from a validated address and a
 * separately escaped label. Nothing from the caller becomes markup verbatim.
 */
function spark_safe_message(string $message): string
{
    $replacements = [];
    $stub = "\x01SPARKLINK";

    // Pull out "<a href='mailto:addr'>label</a>" in either quote style.
    $pattern = '#<a\s+href=(["\'])mailto:([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+)\1\s*>(.*?)</a>#is';

    $stripped = preg_replace_callback(
        $pattern,
        function (array $m) use (&$replacements, $stub): string {
            $key = $stub . count($replacements) . "\x01";
            $replacements[$key] = [
                'addr'  => $m[2],                       // already restricted to a mail charset
                'label' => $m[3],
            ];
            return $key;
        },
        $message
    );

    if (!is_string($stripped)) {
        $stripped = $message;
        $replacements = [];
    }

    $out = htmlspecialchars($stripped, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    foreach ($replacements as $key => $link) {
        $anchor = '<a href="mailto:' . e($link['addr']) . '">' . e($link['label']) . '</a>';
        $out = str_replace(e($key), $anchor, $out);
    }

    return $out;
}

/* ------------------------------------------------------------------ */
/* Authentication gates                                               */
/* ------------------------------------------------------------------ */

/** Participant portal: must hold a project in session. */
function require_project(): void
{
    if (empty($_SESSION['project'])) {
        header('Location: ./index.php');
        exit;
    }
}

/** Admin console: must hold a user in session. */
function require_admin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ./login.php');
        exit;
    }
}

/**
 * Role check. Roles seeded in members_role:
 *   1 super admin, 2 admin, 3 bendahari, 4 sijil, 5 ajk
 * Pass an allow-list; anything else is refused.
 */
function require_role(array $allowed): void
{
    require_admin();
    $level = null;
    foreach ($_SESSION['user'] as $u) {
        $level = (int)($u['level'] ?? 0);
        break;
    }
    if (!in_array($level, $allowed, true)) {
        http_response_code(403);
        exit('You do not have permission to perform this action.');
    }
}

/** True when the current admin is super admin. */
function is_super_admin(): bool
{
    foreach ($_SESSION['user'] ?? [] as $u) {
        return (int)($u['level'] ?? 0) === 1;
    }
    return false;
}

/* ------------------------------------------------------------------ */
/* Uploads                                                            */
/* ------------------------------------------------------------------ */

/**
 * Move an uploaded file into $directory under a random name.
 *
 * Rejects on: upload error, missing tmp file, disallowed extension,
 * extension/MIME mismatch, or a size over $maxBytes.
 *
 * @return array{ok:bool, name?:string, error?:string}
 */
function save_upload(array $file, string $directory, array $allowedExt, int $maxBytes): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Malformed upload.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed (code ' . (int)$file['error'] . ').'];
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'No uploaded file was received.'];
    }
    if ((int)$file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'The file is larger than the limit.'];
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($ext === '' || !in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Unsupported file type.'];
    }

    $mime = detect_mime($file['tmp_name']);
    if ($mime === null || !mime_allowed_for_ext($ext, $mime)) {
        return ['ok' => false, 'error' => 'The file content does not match its type.'];
    }

    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['ok' => false, 'error' => 'The storage directory is unavailable.'];
    }

    // Random name. Extension comes from the whitelist, never from the client.
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'The file could not be stored.'];
    }
    @chmod($target, 0644);
    return ['ok' => true, 'name' => $name];
}

/** Real MIME from file content, never from $_FILES['type']. */
function detect_mime(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        return is_string($m) ? $m : null;
    }
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $m = finfo_file($fi, $path);
            finfo_close($fi);
            return is_string($m) ? $m : null;
        }
    }
    return null;
}

/** Extension → acceptable MIME types. */
function mime_allowed_for_ext(string $ext, string $mime): bool
{
    static $map = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'doc'  => ['application/msword', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'application/zip'],
    ];
    $allowed = $map[$ext] ?? [];
    return in_array($mime, $allowed, true);
}

/**
 * Stream a file from a private location. Used by the download handlers after
 * they have proven the caller owns the record.
 */
function send_file(string $absolutePath, string $downloadName, string $contentType = 'application/octet-stream'): void
{
    if (!is_file($absolutePath) || !is_readable($absolutePath)) {
        http_response_code(404);
        exit('File not found.');
    }
    $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'download';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string)filesize($absolutePath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');

    readfile($absolutePath);
    exit;
}

/* ------------------------------------------------------------------ */
/* Database helpers                                                   */
/* ------------------------------------------------------------------ */

/**
 * Convenience wrapper around mysqli prepared statements.
 * Returns a list of rows for SELECT, affected rows for writes.
 *
 * @param mysqli $conn
 * @param string $sql    with ? placeholders
 * @param array  $params positional
 * @return array<int,array<string,mixed>>|int
 */
function db_exec(mysqli $conn, string $sql, array $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log('db_exec prepare failed: ' . $conn->error);
        return [];
    }
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            if (is_int($p))       $types .= 'i';
            elseif (is_float($p)) $types .= 'd';
            else                  $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('db_exec execute failed: ' . $stmt->error);
        $stmt->close();
        return [];
    }

    $out = [];
    $res = $stmt->get_result();
    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();
        $stmt->close();
        return $out;
    }
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/** Single row or null. */
function db_one(mysqli $conn, string $sql, array $params = []): ?array
{
    $rows = db_exec($conn, $sql, $params);
    return is_array($rows) && $rows ? $rows[0] : null;
}

/** Single scalar or null. */
function db_value(mysqli $conn, string $sql, array $params = [])
{
    $row = db_one($conn, $sql, $params);
    if ($row === null) {
        return null;
    }
    return array_values($row)[0] ?? null;
}

/* ------------------------------------------------------------------ */
/* Misc                                                               */
/* ------------------------------------------------------------------ */

/** Cryptographically strong URL-safe token. Replaces uniqid(). */
function secure_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** Timestamp in the conference timezone. */
function now_my(): string
{
    date_default_timezone_set('Asia/Kuala_Lumpur');
    return date('Y-m-d H:i:s');
}
