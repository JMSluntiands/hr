<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../database/db.php';

// Get employee ID from session (user_login -> employees)
$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
$stmt->bind_param('s', $user['email']);
$stmt->execute();
$empResult = $stmt->get_result();
$employee = $empResult->fetch_assoc();
$stmt->close();

if (!$employee) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    exit;
}

$employeeId = (int)$employee['id'];
$documentType = trim($_POST['document_type'] ?? '');
$allowedTypes = ['Birth Certificate (PSA)', 'Government IDs (Valid ID Set)', 'Employment Contract', 'Company ID Form'];

if (empty($documentType) || !in_array($documentType, $allowedTypes, true)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid document type']);
    exit;
}

if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'File upload error']);
    exit;
}

$file = $_FILES['document_file'];
$allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: PDF, JPG, PNG']);
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'File too large. Max 5MB']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = '../uploads/employee_documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate filename: employee_id_document_type_slug_timestamp.ext
$docSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($documentType));
$filename = $employeeId . '_' . $docSlug . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    exit;
}

// Delete old file if exists
$checkStmt = $conn->prepare("SELECT file_path FROM employee_document_uploads WHERE employee_id = ? AND document_type = ?");
$checkStmt->bind_param('is', $employeeId, $documentType);
$checkStmt->execute();
$oldResult = $checkStmt->get_result();
if (($oldRow = $oldResult->fetch_assoc()) && !empty($oldRow['file_path'])) {
    $oldPath = '../uploads/' . $oldRow['file_path'];
    if (file_exists($oldPath)) {
        @unlink($oldPath);
    }
}
$checkStmt->close();

// Insert new upload (creates request for admin)
$relativePath = 'employee_documents/' . $filename;
$insertStmt = $conn->prepare("INSERT INTO employee_document_uploads (employee_id, document_type, file_path, status) VALUES (?, ?, ?, 'Pending')");
$insertStmt->bind_param('iss', $employeeId, $documentType, $relativePath);
$insertStmt->execute();
$insertStmt->close();

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Document uploaded successfully. Waiting for admin approval.']);
