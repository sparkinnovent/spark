<?php
session_start();
require_once '../security.php';
require_once '../Dbcontroller.php';
require_once './Usersession.php';
require_once '../Util.php';
require_once '../Notify.php';

$db_handle = new DBController();
$user = new Usersession();
$util = new Util();

// FIX C1 class — no working gate on this file either.
require_role([1, 2]);

/*
 * FIX C3 — "delete last upload" removed only `project` and `project_member`,
 * leaving payment / LOA / abstract / presentation / timeline rows orphaned, and
 * it read $_GET['id'] with no escaping at all (SQL injection).
 *
 * It now behaves like deleteProject.php: resolves the project id, removes every
 * child row inside a transaction, then removes the stored files.
 */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Use POST to delete an upload batch.');
}
csrf_verify();

$upload_id = trim((string)($_POST["id"] ?? ''));
if ($upload_id === '') {
    Notify::set('No upload batch was specified.', 'danger');
    $util->redirect("participant-upload.php");
}

$link = $db_handle->link();
if (!$link instanceof mysqli) {
    Notify::set('The service is temporarily unavailable.', 'danger');
    $util->redirect("participant-upload.php");
}

// Find the project(s) created by this import.
$projects = db_exec($link, "SELECT id FROM project WHERE upload_id = ?", [$upload_id]);

if (!$projects) {
    Notify::set('No upload record was found for that batch.', 'warning');
    $util->redirect("participant-upload.php");
}

$childTables = [
    'project_member', 'project_payment', 'project_loa', 'project_timeline',
    'project_presentation', 'project_extended_abstract', 'project_ex_abstract_review',
    'project_cert', 'project_award', 'project_receipt', 'project_student_reject',
];
// project_upload is keyed by upload_id, not project_id — deleted separately below.

/*
 * Collect every stored filename before the rows go, so the files can be removed
 * afterwards. Without this the database stayed consistent but the payment
 * proofs, abstracts, LOAs and certificates were orphaned on disk forever, and
 * deleteProject.php already had this step.
 */
$dirs = [
    'project_payment'            => __DIR__ . '/../upload/payment/',
    'project_extended_abstract'  => __DIR__ . '/../upload/abstract/',
    'project_ex_abstract_review' => __DIR__ . '/../upload/review/',
    'project_loa'                => __DIR__ . '/../download/loa/',
    'project_cert'               => __DIR__ . '/../download/cert/',
    'project_award'              => __DIR__ . '/../download/cert/',
];

$fileSources = [
    ['project_payment',            'payment_proof',    'project_id'],
    ['project_extended_abstract',  'ex_abstract_file', 'project_id'],
    ['project_extended_abstract',  'turnitin_file',    'project_id'],
    ['project_ex_abstract_review', 'review_file',      'project_id'],
    ['project_loa',                'file',             'project_id'],
    ['project_cert',               'files',            'project_id'],
    ['project_award',              'files',            'project_id'],
    ['project_upload',             'file_upload',      'upload_id'],
];

$doomed = [];
foreach ($projects as $p) {
    $pid = (int)$p['id'];
    foreach ($fileSources as [$table, $col, $key]) {
        $val = $key === 'upload_id' ? $upload_id : $pid;
        $rows = db_exec($link, "SELECT {$col} AS f FROM {$table} WHERE {$key} = ?", [$val]);
        foreach ($rows as $r) {
            $name = basename((string)$r['f']);
            if ($name === '') {
                continue;
            }
            $dir = $col === 'turnitin_file'
                ? __DIR__ . '/../upload/turnitin/'
                : ($dirs[$table] ?? null);
            if ($dir !== null) {
                $doomed[] = [$dir, $name];
            }
        }
    }
}

$db_handle->beginTransaction();
$ok = true;

foreach ($projects as $p) {
    $pid = (int)$p['id'];
    $deleted = $db_handle->prepareAndExecute("DELETE FROM project WHERE id = ?", [$pid]);
    if ($deleted === 0) {
        $ok = false;
        break;
    }
    foreach ($childTables as $t) {
        $db_handle->prepareAndExecute("DELETE FROM {$t} WHERE project_id = ?", [$pid]);
    }
    // project_upload is keyed by the batch, not by project id.
    $db_handle->prepareAndExecute("DELETE FROM project_upload WHERE upload_id = ?", [$upload_id]);
}

if ($ok) {
    $db_handle->commitTransaction();

    // Filesystem work only after the transaction is durable, and only paths that
    // resolve inside the directory we chose.
    foreach ($doomed as [$dir, $name]) {
        $base = realpath($dir);
        if ($base === false) {
            continue;
        }
        $path = realpath($base . DIRECTORY_SEPARATOR . $name);
        if ($path !== false && str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            @unlink($path);
        }
    }

    $count = count($projects);
    Notify::set("Upload batch deleted ({$count} project" . ($count === 1 ? '' : 's') . ").", 'success');
} else {
    $db_handle->rollbackTransaction();
    Notify::set('The upload batch could not be deleted. No changes were made.', 'danger');
}

$util->redirect("participant-upload.php");
