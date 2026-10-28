<?php
/**
 * Authenticated document delivery.
 *
 * FIX C4 — the six download-*.php handlers previously served any file by name
 * to anyone. There was no session check, no record lookup and no ownership test,
 * and filenames were epoch seconds, so the whole conference window was
 * enumerable.
 *
 * Every download now:
 *   1. requires a session (participant portal or admin console),
 *   2. looks the record up by file name AND proves the caller may see it,
 *   3. resolves the real path and confirms it stays inside its directory,
 *   4. streams with a canonical filename instead of the stored one.
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/Dbcontroller.php';

/**
 * @param string $kind one of: payment, loa, abstract, turnitin, review, receipt
 */
function spark_download(string $kind): void
{
    $map = [
        'payment'  => ['dir' => 'upload/payment',         'col' => 'payment_proof',   'table' => 'project_payment'],
        'receipt'  => ['dir' => 'upload/receipt',         'col' => 'receipt_file',    'table' => 'project_receipt'],
        'loa'      => ['dir' => 'download/loa',           'col' => 'file',            'table' => 'project_loa'],
        'abstract' => ['dir' => 'upload/abstract',        'col' => 'ex_abstract_file','table' => 'project_extended_abstract'],
        'turnitin' => ['dir' => 'upload/turnitin',        'col' => 'turnitin_file',   'table' => 'project_extended_abstract'],
        'review'   => ['dir' => 'upload/review',          'col' => 'review_file',     'table' => 'project_ex_abstract_review'],
    ];

    if (!isset($map[$kind])) {
        http_response_code(400);
        exit('Unknown download type.');
    }
    $cfg = $map[$kind];

    $requested = $_GET['file'] ?? '';
    if (!is_string($requested) || $requested === '') {
        http_response_code(400);
        exit('Invalid request.');
    }

    // Only a bare filename is ever accepted.
    $requested = basename($requested);
    if ($requested === '' || $requested === '.' || $requested === '..') {
        http_response_code(400);
        exit('Invalid request.');
    }

    $isAdmin    = !empty($_SESSION['user']);
    $projectId  = (string)($_SESSION['project'] ?? '');
    $memberMail = '';
    if (!$isAdmin) {
        if ($projectId === '') {
            http_response_code(403);
            exit('You must be signed in to download this file.');
        }
        $memberMail = (string)($_SESSION['project_email'] ?? '');
    }

    $db = new DBController();
    $link = $db->link();
    if (!$link instanceof mysqli) {
        http_response_code(503);
        exit('The service is temporarily unavailable.');
    }

    // Find the owning record.
    $sql = "SELECT * FROM {$cfg['table']} WHERE {$cfg['col']} = ? LIMIT 1";
    $record = db_one($link, $sql, [$requested]);

    if ($record === null) {
        http_response_code(404);
        exit('File not found.');
    }

    /*
     * Ownership test.
     *
     * Administrators may read anything. A participant may read a record only
     * when it hangs off their own project. project_payment, project_loa and
     * project_extended_abstract are keyed by project_id; project_receipt and
     * project_ex_abstract_review disagree slightly, so both keys are checked.
     */
    if (!$isAdmin) {
        $ownerProject = (string)($record['project_id'] ?? '');
        $ownerUpload  = (string)($record['upload_id']  ?? '');
        $mineUpload   = (string)($_SESSION['upload_id'] ?? '');

        $owns = ($ownerProject !== '' && hash_equals($projectId, $ownerProject))
             || ($ownerUpload !== '' && $mineUpload !== '' && hash_equals($mineUpload, $ownerUpload));

        if (!$owns) {
            http_response_code(403);
            exit('You do not have permission to download this file.');
        }
    }

    // Resolve and contain.
    $baseDir = realpath(__DIR__ . '/' . $cfg['dir']);
    if ($baseDir === false) {
        http_response_code(404);
        exit('File not found.');
    }
    $path = realpath($baseDir . DIRECTORY_SEPARATOR . $requested);
    if ($path === false || !str_starts_with($path, $baseDir . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('File not found.');
    }

    // Hand over with a canonical name, not whatever is stored.
    $label = preg_replace('/[^A-Za-z0-9_-]/', '', $projectId) ?: 'document';
    $ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime  = match ($ext) {
        'pdf'  => 'application/pdf',
        'jpg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        default => 'application/octet-stream',
    };

    send_file($path, $label . '-' . $kind . '.' . ($ext ?: 'bin'), $mime);
}
