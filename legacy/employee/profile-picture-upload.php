<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../database/db.php';

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$stmt = $conn->prepare("SELECT id, profile_picture FROM employees WHERE email = ?");
$stmt->bind_param('s', $user['email']);
$stmt->execute();
$empResult = $stmt->get_result();
$emp = $empResult->fetch_assoc();
$stmt->close();

if (!$emp) {
    echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    exit;
}

$empId = (int)$emp['id'];

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Please select an image file']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed = ['image/jpeg', 'image/jpg', 'image/png'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Use JPG or PNG.']);
    exit;
}

$ext = $mime === 'image/png' ? 'png' : 'jpg';
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'File too large. Max 2MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = $empId . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $filename;
$relativePath = 'profile_pictures/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    exit;
}

// Delete old photo
if (!empty($emp['profile_picture'])) {
    $oldPath = __DIR__ . '/../uploads/' . $emp['profile_picture'];
    if (file_exists($oldPath)) {
        @unlink($oldPath);
    }
}

$updateStmt = $conn->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
$updateStmt->bind_param('si', $relativePath, $empId);
if (!$updateStmt->execute()) {
    @unlink($filePath);
    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    exit;
}
$updateStmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Profile picture updated',
    'path' => $relativePath,
]);
