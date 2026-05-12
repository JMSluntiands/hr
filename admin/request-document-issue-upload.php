<?php
/**
 * Attach an issued PDF/image to a specific approved document_requests row (links document_files.document_request_id).
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$role = strtolower((string)($_SESSION['role'] ?? ''));
if ($role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: request-document.php');
    exit;
}

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/ensure_document_requests_coe_columns.php';
require_once __DIR__ . '/../include/ensure_document_files_request_link.php';

if ($conn) {
    ensure_document_requests_coe_columns($conn);
    ensure_document_files_request_link($conn);
}

$adminId = (int)$_SESSION['user_id'];
$adminName = (string)($_SESSION['name'] ?? 'Admin');
$drId = (int)($_POST['document_request_id'] ?? 0);

if (!$conn || $drId <= 0) {
    $_SESSION['request_document_msg'] = 'Invalid request.';
    header('Location: request-document.php');
    exit;
}

if (!isset($_FILES['issue_file']) || $_FILES['issue_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['request_document_msg'] = 'Please choose a PDF or image file to upload.';
    header('Location: request-document.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT dr.id, dr.employee_id, dr.document_type, dr.status
     FROM document_requests dr WHERE dr.id = ? LIMIT 1'
);
$stmt->bind_param('i', $drId);
$stmt->execute();
$res = $stmt->get_result();
$dr = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$dr) {
    $_SESSION['request_document_msg'] = 'That document request was not found.';
    header('Location: request-document.php');
    exit;
}

if (($dr['status'] ?? '') !== 'Approved') {
    $_SESSION['request_document_msg'] = 'You can only attach a file after the request is approved.';
    header('Location: request-document.php');
    exit;
}

$employeeId = (int)($dr['employee_id'] ?? 0);
$docType = (string)($dr['document_type'] ?? '');

$file = $_FILES['issue_file'];
$allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    $_SESSION['request_document_msg'] = 'Invalid file type. Allowed: PDF, JPG, PNG.';
    header('Location: request-document.php');
    exit;
}

$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    $_SESSION['request_document_msg'] = 'File too large. Maximum 5MB.';
    header('Location: request-document.php');
    exit;
}

$uploadDir = __DIR__ . '/../uploads/employee_documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$docSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($docType));
$filename = $employeeId . '_req' . $drId . '_' . $docSlug . '_' . time() . '.' . $ext;
$fullPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    $_SESSION['request_document_msg'] = 'Failed to save the uploaded file.';
    header('Location: request-document.php');
    exit;
}

$relativePath = 'employee_documents/' . $filename;
$now = date('Y-m-d H:i:s');

$chkDf = $conn->query("SHOW TABLES LIKE 'document_files'");
if (!$chkDf || $chkDf->num_rows === 0) {
    $_SESSION['request_document_msg'] = 'document_files table is missing. Ask IT to run database setup.';
    header('Location: request-document.php');
    exit;
}

$conn->begin_transaction();
try {
    $sel = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 1');
    $sel->bind_param('i', $drId);
    $sel->execute();
    $exRes = $sel->get_result();
    $existing = $exRes ? $exRes->fetch_assoc() : null;
    $sel->close();

    if ($existing) {
        $upd = $conn->prepare(
            'UPDATE document_files SET file_path = ?, document_type = ?, approved_by = ?, approved_by_name = ?, approved_at = ?, updated_at = ? WHERE id = ?'
        );
        $exId = (int)$existing['id'];
        $upd->bind_param('ssisssi', $relativePath, $docType, $adminId, $adminName, $now, $now, $exId);
        if (!$upd->execute()) {
            throw new RuntimeException($upd->error);
        }
        $upd->close();
    } else {
        $ins = $conn->prepare(
            'INSERT INTO document_files (employee_id, document_request_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->bind_param('iississs', $employeeId, $drId, $docType, $relativePath, $adminId, $adminName, $now, $now);
        if (!$ins->execute()) {
            throw new RuntimeException($ins->error);
        }
        $ins->close();
    }

    $conn->commit();
    $_SESSION['request_document_msg'] = '✓ Issued file attached to this request. The employee will see Download/Preview on My Request.';
} catch (Throwable $e) {
    $conn->rollback();
    @unlink($fullPath);
    $_SESSION['request_document_msg'] = 'Could not save file link: ' . $e->getMessage();
}

header('Location: request-document.php');
exit;
