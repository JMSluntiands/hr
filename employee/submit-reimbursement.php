<?php
session_start();
ob_start();

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/employee_data.php';

ob_clean();
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if (!$employeeDbId) {
    echo json_encode(['status' => 'error', 'message' => 'Employee record not found']);
    exit;
}

$expenseType = trim((string)($_POST['expense_type'] ?? ''));
$expenseDescription = trim((string)($_POST['expense_description'] ?? ''));
$purchasedDate = trim((string)($_POST['purchased_date'] ?? ''));
$amountRaw = trim((string)($_POST['amount'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

if ($expenseType === '' || $expenseDescription === '' || $purchasedDate === '' || $amountRaw === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please complete all required fields.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchasedDate)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid purchased date.']);
    exit;
}

$amount = (float)$amountRaw;
if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than zero.']);
    exit;
}

$maxFileSize = 5 * 1024 * 1024 * 1024; // 5GB
$receiptPath = null;
$receiptOriginalName = null;

if (isset($_FILES['receipt']) && is_array($_FILES['receipt']) && (int)$_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
    $fileError = (int)$_FILES['receipt']['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Receipt upload failed.']);
        exit;
    }

    $fileSize = (int)($_FILES['receipt']['size'] ?? 0);
    if ($fileSize > $maxFileSize) {
        echo json_encode(['status' => 'error', 'message' => 'Receipt must not exceed 5GB.']);
        exit;
    }

    $tmpName = (string)($_FILES['receipt']['tmp_name'] ?? '');
    $originalName = (string)($_FILES['receipt']['name'] ?? 'receipt');
    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($extension, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Allowed receipt formats: JPG, JPEG, PNG, WEBP, PDF.']);
        exit;
    }

    $targetDir = __DIR__ . '/../uploads/reimbursements';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory.']);
        exit;
    }

    $safeName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $fullPath = $targetDir . '/' . $safeName;

    if (!move_uploaded_file($tmpName, $fullPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded receipt.']);
        exit;
    }

    $receiptPath = 'reimbursements/' . $safeName;
    $receiptOriginalName = $originalName;
}

$createSql = "CREATE TABLE IF NOT EXISTS reimbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    expense_type VARCHAR(100) NOT NULL,
    expense_description TEXT NOT NULL,
    purchased_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    receipt_path VARCHAR(255) NULL,
    receipt_original_name VARCHAR(255) NULL,
    admin_receipt_path VARCHAR(255) NULL,
    admin_receipt_original_name VARCHAR(255) NULL,
    reimbursed_at DATETIME NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    rejection_reason TEXT NULL,
    approved_by INT NULL,
    approved_by_name VARCHAR(150) NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_purchased_date (purchased_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($createSql)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare reimbursement storage.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO reimbursements (employee_id, expense_type, expense_description, purchased_date, amount, notes, receipt_path, receipt_original_name, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create request.']);
    exit;
}

$stmt->bind_param(
    'isssdsss',
    $employeeDbId,
    $expenseType,
    $expenseDescription,
    $purchasedDate,
    $amount,
    $notes,
    $receiptPath,
    $receiptOriginalName
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Reimbursement request submitted for review.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit reimbursement request.']);
}

$stmt->close();
