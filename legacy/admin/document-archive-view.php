<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$userRole = strtolower($_SESSION['role'] ?? '');
if ($userRole !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

include '../database/db.php';

$archiveId = (int)($_GET['id'] ?? 0);
if ($archiveId <= 0 || !$conn) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$stmt = $conn->prepare("SELECT file_path, document_type FROM document_archive WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $archiveId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || empty($row['file_path'])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$filePath = dirname(__DIR__) . '/uploads/' . $row['file_path'];
if (!is_file($filePath)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
