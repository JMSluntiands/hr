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

function uploadReimbursementReceipt(string $fileKey): array
{
    if (!isset($_FILES[$fileKey]) || !is_array($_FILES[$fileKey]) || (int) $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'name' => null, 'error' => null];
    }
    $f = $_FILES[$fileKey];
    if ((int) $f['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'name' => null, 'error' => 'Receipt upload failed.'];
    }
    $maxFileSize = 5 * 1024 * 1024 * 1024;
    if ((int) ($f['size'] ?? 0) > $maxFileSize) {
        return ['path' => null, 'name' => null, 'error' => 'Receipt must not exceed 5GB.'];
    }
    $originalName = (string) ($f['name'] ?? 'receipt');
    $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    if (!in_array($extension, $allowed, true)) {
        return ['path' => null, 'name' => null, 'error' => 'Allowed receipt formats: JPG, JPEG, PNG, WEBP, PDF.'];
    }
    $targetDir = __DIR__ . '/../uploads/reimbursements';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return ['path' => null, 'name' => null, 'error' => 'Failed to create upload directory.'];
    }
    $safeName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    if (!move_uploaded_file($f['tmp_name'], $targetDir . '/' . $safeName)) {
        return ['path' => null, 'name' => null, 'error' => 'Failed to save uploaded receipt.'];
    }

    return ['path' => 'reimbursements/' . $safeName, 'name' => $originalName, 'error' => null];
}

$isBulk = !empty($_POST['is_bulk']);

if ($isBulk) {
    $types = $_POST['bulk_expense_type'] ?? [];
    $descriptions = $_POST['bulk_expense_description'] ?? [];
    $dates = $_POST['bulk_purchased_date'] ?? [];
    $amounts = $_POST['bulk_amount'] ?? [];
    $bulkNotes = trim((string) ($_POST['bulk_notes'] ?? ''));

    if (!is_array($types) || count($types) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No bulk items found. Add at least one item.']);
        exit;
    }

    $itemCount = count($types);
    $inserted = 0;
    $errors = [];

    $stmt = $conn->prepare(
        "INSERT INTO reimbursements (employee_id, expense_type, expense_description, purchased_date, amount, notes, receipt_path, receipt_original_name, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
    );
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create request.']);
        exit;
    }

    for ($i = 0; $i < $itemCount; $i++) {
        $t = trim((string) ($types[$i] ?? ''));
        $d = trim((string) ($descriptions[$i] ?? ''));
        $dt = trim((string) ($dates[$i] ?? ''));
        $a = (float) ($amounts[$i] ?? 0);

        if ($t === '' || $d === '' || $dt === '' || $a <= 0) {
            $errors[] = 'Item ' . ($i + 1) . ': missing required fields.';
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
            $errors[] = 'Item ' . ($i + 1) . ': invalid date.';
            continue;
        }

        $upload = uploadReimbursementReceipt('bulk_receipt_' . $i);
        if ($upload['error']) {
            $errors[] = 'Item ' . ($i + 1) . ': ' . $upload['error'];
            continue;
        }

        $rp = $upload['path'];
        $rn = $upload['name'];
        $stmt->bind_param('isssdsss', $employeeDbId, $t, $d, $dt, $a, $bulkNotes, $rp, $rn);
        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors[] = 'Item ' . ($i + 1) . ': save failed.';
        }
    }
    $stmt->close();

    if ($inserted === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No items were saved. ' . implode(' ', $errors)]);
    } elseif (count($errors) > 0) {
        echo json_encode(['status' => 'success', 'message' => $inserted . ' of ' . $itemCount . ' items submitted. Issues: ' . implode(' ', $errors)]);
    } else {
        echo json_encode(['status' => 'success', 'message' => $inserted . ' reimbursement request' . ($inserted > 1 ? 's' : '') . ' submitted for review.']);
    }
    exit;
}

$expenseType = trim((string) ($_POST['expense_type'] ?? ''));
$expenseDescription = trim((string) ($_POST['expense_description'] ?? ''));
$purchasedDate = trim((string) ($_POST['purchased_date'] ?? ''));
$amountRaw = trim((string) ($_POST['amount'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($expenseType === '' || $expenseDescription === '' || $purchasedDate === '' || $amountRaw === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please complete all required fields.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchasedDate)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid purchased date.']);
    exit;
}

$amount = (float) $amountRaw;
if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than zero.']);
    exit;
}

$upload = uploadReimbursementReceipt('receipt');
if ($upload['error']) {
    echo json_encode(['status' => 'error', 'message' => $upload['error']]);
    exit;
}
$receiptPath = $upload['path'];
$receiptOriginalName = $upload['name'];

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
