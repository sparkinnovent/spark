<?php
// Re-check two claims from the report.
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== L1: convertToBytes return type ===\n";
$units = ['B','KB','MB','GB','TB','PB'];
function convertToBytes(string $from): ?int {
    $units = ['B','KB','MB','GB','TB','PB'];
    $number = substr($from, 0, -2);
    $suffix = strtoupper(substr($from,-2));
    if(is_numeric(substr($suffix, 0, 1))) {
        return preg_replace('/[^\d]/', '', $from);   // returns STRING
    }
    $exponent = array_flip($units)[$suffix] ?? null;
    if($exponent === null) return null;
    return $number * (1024 ** $exponent);
}
foreach (['10MB','5MB','1024B'] as $s) {
    try {
        $v = convertToBytes($s);
        echo "  convertToBytes('$s') = " . var_export($v, true) . "  type=" . gettype($v) . "\n";
    } catch (Throwable $e) {
        echo "  convertToBytes('$s') -> " . get_class($e) . ": " . $e->getMessage() . "\n";
    }
}

echo "\n=== orphan check: what deleteUpload.php does NOT delete ===\n";
$sql = file_get_contents(__DIR__ . '/../uitmktco_gdiid25.sql');
$tables = ['project','project_member','project_payment','project_loa','project_timeline',
           'project_presentation','project_extended_abstract'];
foreach ($tables as $t) {
    preg_match("/CREATE TABLE `$t` \((.*?)\n\)/s", $sql, $m);
    $cols = [];
    if (!empty($m[1])) {
        preg_match_all('/^\s*`([a-z_0-9]+)`/m', $m[1], $cm);
        $cols = $cm[1];
    }
    $hasUploadId = in_array('upload_id', $cols, true);
    $hasProjectId = in_array('project_id', $cols, true);
    printf("  %-30s upload_id=%-3s project_id=%-3s\n", $t,
        $hasUploadId ? 'yes' : 'no', $hasProjectId ? 'yes' : 'no');
}
echo "\n  deleteUpload.php only touches: project (by upload_id), project_member (by upload_id)\n";
echo "  -> project_payment / project_loa / project_timeline / presentation /\n";
echo "     extended_abstract are keyed by project_id and are left orphaned.\n";
