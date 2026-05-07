<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/activity-logger.php';

if (!$conn) {
    $_SESSION['reimbursement_list_msg'] = 'Database connection failed.';
    header('Location: reimbursement-list.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['reimbursement_list_msg'] = 'Invalid reimbursement ID.';
    header('Location: reimbursement-list.php');
    exit;
}

$conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_path VARCHAR(255) NULL");
$conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_original_name VARCHAR(255) NULL");
$conn->query("ALTER TABLE reimbursements ADD COLUMN reimbursed_at DATETIME NULL");

if (!isset($_FILES['admin_receipt']) || !is_array($_FILES['admin_receipt'])) {
    $_SESSION['reimbursement_list_msg'] = 'Please upload reimbursement receipt.';
    header('Location: reimbursement-list.php');
    exit;
}

$fileError = (int)($_FILES['admin_receipt']['error'] ?? UPLOAD_ERR_NO_FILE);
if ($fileError !== UPLOAD_ERR_OK) {
    $_SESSION['reimbursement_list_msg'] = 'Receipt upload failed.';
    header('Location: reimbursement-list.php');
    exit;
}

$maxFileSize = 5 * 1024 * 1024 * 1024; // 5GB
$fileSize = (int)($_FILES['admin_receipt']['size'] ?? 0);
if ($fileSize > $maxFileSize) {
    $_SESSION['reimbursement_list_msg'] = 'Receipt must not exceed 5GB.';
    header('Location: reimbursement-list.php');
    exit;
}

$originalName = (string)($_FILES['admin_receipt']['name'] ?? 'receipt');
$extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
if (!in_array($extension, $allowed, true)) {
    $_SESSION['reimbursement_list_msg'] = 'Allowed receipt formats: JPG, JPEG, PNG, WEBP, PDF.';
    header('Location: reimbursement-list.php');
    exit;
}

$targetDir = __DIR__ . '/../uploads/reimbursements/admin-proof';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    $_SESSION['reimbursement_list_msg'] = 'Failed to create upload directory.';
    header('Location: reimbursement-list.php');
    exit;
}

$safeName = 'admin_receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
$fullPath = $targetDir . '/' . $safeName;

if (!move_uploaded_file((string)$_FILES['admin_receipt']['tmp_name'], $fullPath)) {
    $_SESSION['reimbursement_list_msg'] = 'Failed to save uploaded receipt.';
    header('Location: reimbursement-list.php');
    exit;
}

$dbPath = 'reimbursements/admin-proof/' . $safeName;
$stmt = $conn->prepare("UPDATE reimbursements SET admin_receipt_path = ?, admin_receipt_original_name = ?, reimbursed_at = NOW() WHERE id = ? AND status = 'Approved'");
$stmt->bind_param('ssi', $dbPath, $originalName, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    logActivity($conn, 'Attach Reimbursement Receipt', 'Reimbursement', $id, 'Attached admin reimbursement receipt proof.');
    $_SESSION['reimbursement_list_msg'] = 'Reimbursement receipt attached successfully.';
} else {
    $_SESSION['reimbursement_list_msg'] = 'Failed to attach reimbursement receipt.';
}

header('Location: reimbursement-list.php');
exit;
