<?php
/**
 * RETIRED — GDIID 2021 letter-of-acceptance generator.
 *
 * FIX M8. This file was left in the admin console from an earlier conference:
 *
 *   - it included six files that are not in this codebase
 *       user-session.php, ../../dbcontroller.php, ../../Util.php,
 *       ../../icectcontroller.php, ../../iaccontroller.php, ../../tibeccontroller.php
 *     so any request produced a stream of warnings and then a fatal on the
 *     undefined DBController class;
 *   - it queried `paper` and `author`, tables that do not exist in this schema;
 *   - it had no authentication gate.
 *
 * The 2025 flow is admin/participant-loa.php -> send-loa-pub.php, which builds
 * the PDF from `project` / `project_member` and records it in `project_loa`.
 */

session_start();
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../Notify.php';

require_admin();

Notify::set(
    'This letter generator belongs to a previous conference and has been retired. '
    . 'Use Participant > LOA to generate a letter.',
    'warning'
);

header('Location: ./participant-loa.php');
exit;
