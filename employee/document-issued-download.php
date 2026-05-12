<?php
/**
 * Download an HR-issued file from document_files (e.g. signed COE PDF uploaded by admin).
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require_once __DIR__ . '/../database/db.php';

$fileId = (int)($_GET['file_id'] ?? 0);
if ($fileId <= 0 || !$conn) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare('SELECT email, role FROM user_login WHERE id = ?');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$stmt = $conn->prepare(
    'SELECT df.id, df.document_type, df.file_path, e.email
     FROM document_files df
     INNER JOIN employees e ON df.employee_id = e.id
     WHERE df.id = ?'
);
$stmt->bind_param('i', $fileId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$userEmail = (string)($user['email'] ?? '');
$userRole = strtolower((string)($user['role'] ?? ''));
if ($userRole !== 'admin' && $userEmail !== (string)($row['email'] ?? '')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$rel = trim((string)($row['file_path'] ?? ''));
if ($rel === '' || strpos($rel, '..') !== false) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (preg_match('#[/\\\\]uploads[/\\\\](.+)$#i', str_replace('\\', '/', $rel), $m)) {
    $rel = $m[1];
} elseif (preg_match('#^uploads[/\\\\]+#i', $rel)) {
    $rel = preg_replace('#^uploads[/\\\\]+#i', '', $rel);
} elseif (isset($rel[0]) && ($rel[0] === '/' || $rel[0] === '\\')) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$rel = ltrim(str_replace('\\', '/', $rel), '/');

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

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';
$slug = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string)($row['document_type'] ?? 'document'));
$downloadName = $slug . '_' . $fileId . '.' . ($ext !== '' ? $ext : 'bin');

$inline = isset($_GET['inline']) && (string)$_GET['inline'] === '1';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
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
