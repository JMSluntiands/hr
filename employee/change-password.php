<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../database/db.php';

header('Content-Type: application/json');

$userId = (int)$_SESSION['user_id'];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
    exit;
}

if ($currentPassword === $newPassword) {
    echo json_encode(['status' => 'error', 'message' => 'New password must be different from current password']);
    exit;
}

// Verify current password
$hashedCurrentPassword = md5($currentPassword);
$stmt = $conn->prepare("SELECT id, password FROM user_login WHERE id = ? AND password = ?");
$stmt->bind_param('is', $userId, $hashedCurrentPassword);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
    exit;
}

// Update password
$hashedNewPassword = md5($newPassword);
$updateStmt = $conn->prepare("UPDATE user_login SET password = ? WHERE id = ?");

if (!$updateStmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$updateStmt->bind_param('si', $hashedNewPassword, $userId);

if ($updateStmt->execute()) {
    // Clear default password flag from session
    unset($_SESSION['is_default_password']);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Password changed successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update password: ' . $updateStmt->error
    ]);
}

$updateStmt->close();
$conn->close();
?>
