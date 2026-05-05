<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

include '../database/db.php';

$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT email, role FROM user_login WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$stmt = $conn->prepare("SELECT ed.*, e.email FROM employee_document_uploads ed JOIN employees e ON ed.employee_id = e.id WHERE ed.id = ?");
$stmt->bind_param('i', $docId);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();
$stmt->close();

if (!$doc || empty($doc['file_path'])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$userEmail = $user['email'] ?? '';
$userRole = strtolower($user['role'] ?? '');
if ($userRole !== 'admin' && $userEmail !== ($doc['email'] ?? '')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$filePath = '../uploads/' . $doc['file_path'];
if (!file_exists($filePath)) {
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

$docTypeSlug = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string)($doc['document_type'] ?? 'document'));
$downloadName = $docTypeSlug . '_' . $docId . '.' . ($ext !== '' ? $ext : 'bin');

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
