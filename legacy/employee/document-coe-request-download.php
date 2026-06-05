<?php
/**
 * Download or inline-preview the approved COE PDF for a document_requests row (generates if needed).
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require_once __DIR__ . '/../controller/session_timeout.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/include/employee_data.php';
require_once __DIR__ . '/../include/ensure_document_files_request_link.php';
require_once __DIR__ . '/../include/ensure_document_requests_coe_columns.php';
require_once __DIR__ . '/../include/generate_coe_pdf.php';
require_once __DIR__ . '/../include/ensure_document_request_issued_file.php';

if (!$conn || !$employeeDbId) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

ensure_document_files_request_link($conn);
ensure_document_requests_coe_columns($conn);

$requestId = (int)($_GET['request_id'] ?? 0);
if ($requestId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$eid = (int)$employeeDbId;
$sql = 'SELECT id, document_type, status FROM document_requests WHERE id = ' . $requestId . ' AND employee_id = ' . $eid . " AND LOWER(TRIM(CAST(status AS CHAR))) = 'approved' LIMIT 1";
$res = $conn->query($sql);
if (!$res) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}
$row = $res->fetch_assoc();

if (!$row || !coe_document_type_is_coe((string)($row['document_type'] ?? ''))) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$rel = generate_coe_pdf_for_document_request($conn, $eid, $requestId);
if ($rel === null || $rel === '') {
    $detail = coe_pdf_take_failure_message();
    header('HTTP/1.1 503 Service Unavailable');
    header('Content-Type: text/plain; charset=UTF-8');
    $body = 'COE PDF could not be generated. Ensure `composer install` was run in the project root and PHP has extensions: dom, mbstring, xml.';
    if ($detail !== '') {
        $body .= "\n\nDetail: " . $detail;
    }
    echo $body;
    exit;
}

coe_register_generated_file_for_request($conn, $eid, $requestId, 'COE', $rel);

$rel = trim(str_replace('\\', '/', $rel));
if (preg_match('#[/\\\\]uploads[/\\\\](.+)$#i', $rel, $m)) {
    $rel = $m[1];
} elseif (preg_match('#^uploads[/\\\\]+#i', $rel)) {
    $rel = preg_replace('#^uploads[/\\\\]+#i', '', $rel);
}
$rel = ltrim($rel, '/');

$baseDir = realpath(__DIR__ . '/../uploads');
if ($baseDir === false) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

$candidate = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
$full = is_file($candidate) ? $candidate : (realpath($candidate) ?: false);
if ($full === false || !is_file($full)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}
$full = realpath($full) ?: $full;
if (strncmp($full, $baseDir, strlen($baseDir)) !== 0) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$inline = isset($_GET['inline']) && (string)$_GET['inline'] === '1';
$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$downloadName = 'COE_request_' . $requestId . '.' . ($ext !== '' ? $ext : 'pdf');

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
if ($inline) {
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
}
header('Content-Length: ' . (string)filesize($full));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($full);
exit;
