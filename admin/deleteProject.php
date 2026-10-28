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

// FIX C1 class — this endpoint had no working gate.
require_role([1, 2]);

/*
 * FIX H6 — destructive action reachable by a plain GET meant a single
 * <img src="…/deleteProject.php?id=4153"> on any page an admin visited would
 * wipe the project. It now requires POST and a CSRF token.
 */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Use POST to delete a project.');
}
csrf_verify();

// The request carries a project id; the old variable name said upload.
$projectId = (int)($_POST["id"] ?? 0);
if ($projectId < 1) {
    Notify::set('No project was specified.', 'danger');
    $util->redirect("./participant.php");
}

$link = $db_handle->link();
if (!$link instanceof mysqli) {
    Notify::set('The service is temporarily unavailable.', 'danger');
    $util->redirect("./participant.php");
}

/*
 * FIX C3 — collect the child file names before the rows disappear so the
 * stored files can be removed too, then delete inside a transaction. The
 * original left payment proofs, LOAs, abstracts and videos on disk forever.
 */
$files = [];
foreach ([
    ['project_payment',           'payment_proof'],
    ['project_extended_abstract', 'ex_abstract_file'],
    ['project_extended_abstract', 'turnitin_file'],
    ['project_ex_abstract_review', 'review_file'],
    ['project_loa',               'file'],
    ['project_cert',              'files'],
    ['project_award',             'files'],
    ['project_receipt',           'receipt_file'],
] as [$table, $col]) {
    $rows = db_exec($link, "SELECT {$col} AS f FROM {$table} WHERE project_id = ?", [$projectId]);
    foreach ($rows as $r) {
        if (!empty($r['f'])) {
            $files[] = ['table' => $table, 'col' => $col, 'name' => (string)$r['f']];
        }
    }
}

$tables = [
    'project_member',
    'project_payment',
    'project_loa',
    'project_timeline',
    'project_presentation',
    'project_extended_abstract',
    'project_ex_abstract_review',
    'project_cert',
    'project_award',
    'project_receipt',
    'project_student_reject',
];
// project_upload is keyed by upload_id, not project_id — it is never a child of
// a project row, so including it here would run a query against a column that
// does not exist and abort the delete.

$db_handle->beginTransaction();
$ok = $db_handle->prepareAndExecute("DELETE FROM project WHERE id = ?", [$projectId]) !== 0;

foreach ($tables as $t) {
    if (!$ok) {
        break;
    }
    // Column name is a literal from the list above, never user input.
    $db_handle->prepareAndExecute("DELETE FROM {$t} WHERE project_id = ?", [$projectId]);
}

if ($ok) {
    $db_handle->commitTransaction();
} else {
    $db_handle->rollbackTransaction();
}

if ($ok) {
    // Only now touch the filesystem, and only paths we resolved ourselves.
    $dirs = [
        'project_payment'            => __DIR__ . '/../upload/payment/',
        'project_extended_abstract'  => __DIR__ . '/../upload/abstract/',
        'project_ex_abstract_review' => __DIR__ . '/../upload/review/',
        'project_loa'                => __DIR__ . '/../download/loa/',
        'project_cert'               => __DIR__ . '/../download/cert/',
        // generate-award.php writes its PDF into download/cert/ as well.
        'project_award'              => __DIR__ . '/../download/cert/',
        'project_receipt'            => __DIR__ . '/../upload/receipt/',
    ];
    foreach ($files as $f) {
        if ($f['col'] === 'turnitin_file') {
            $dir = __DIR__ . '/../upload/turnitin/';
        } else {
            $dir = $dirs[$f['table']] ?? null;
        }
        if ($dir === null) {
            continue;
        }
        $path = realpath($dir . basename($f['name']));
        if ($path !== false && str_starts_with($path, realpath($dir) . DIRECTORY_SEPARATOR)) {
            @unlink($path);
        }
    }
    Notify::set('Project and all related records were deleted.', 'success');
} else {
    Notify::set('The project could not be deleted. No changes were made.', 'danger');
}

$util->redirect("./participant.php");
