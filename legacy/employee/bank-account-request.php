<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');

include '../database/db.php';

$userId = (int)$_SESSION['user_id'];
$employeeDbId = null;

if ($conn) {
    $userStmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    if ($user) {
        $empStmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $empStmt->bind_param('s', $user['email']);
        $empStmt->execute();
        $empResult = $empStmt->get_result();
        $employee = $empResult->fetch_assoc();
        $empStmt->close();
        if ($employee) {
            $employeeDbId = (int)$employee['id'];
        }
    }
}

if (!$employeeDbId) {
    echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    exit;
}

// Ensure table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'bank_account_change_requests'");
if (!$checkTable || $checkTable->num_rows == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS `bank_account_change_requests` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `bank_name` varchar(255) NOT NULL,
      `account_number` varchar(100) NOT NULL,
      `account_name` varchar(255) NOT NULL,
      `account_type` enum('Savings','Checking','Current') DEFAULT 'Savings',
      `branch` varchar(255) DEFAULT NULL,
      `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
      `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `approved_by` int(11) DEFAULT NULL,
      `approved_by_name` varchar(255) DEFAULT NULL,
      `approved_at` timestamp NULL DEFAULT NULL,
      `rejection_reason` text DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_employee_id` (`employee_id`),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Check for existing pending request
$pendingStmt = $conn->prepare("SELECT id FROM bank_account_change_requests WHERE employee_id = ? AND status = 'Pending' LIMIT 1");
$pendingStmt->bind_param('i', $employeeDbId);
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
if ($pendingResult->num_rows > 0) {
    $pendingStmt->close();
    echo json_encode(['status' => 'error', 'message' => 'You already have a pending request. Wait for admin approval.']);
    exit;
}
$pendingStmt->close();

$bankName = trim($_POST['bank_name'] ?? '');
$accountNumber = trim($_POST['account_number'] ?? '');
$accountName = trim($_POST['account_name'] ?? '');
$accountType = $_POST['account_type'] ?? 'Savings';
if (!in_array($accountType, ['Savings', 'Checking', 'Current'], true)) {
    $accountType = 'Savings';
}
$branch = trim($_POST['branch'] ?? '');

if (empty($bankName) || empty($accountNumber) || empty($accountName)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

$insertStmt = $conn->prepare("INSERT INTO bank_account_change_requests (employee_id, bank_name, account_number, account_name, account_type, branch, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
$insertStmt->bind_param('isssss', $employeeDbId, $bankName, $accountNumber, $accountName, $accountType, $branch);

if ($insertStmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Bank account change requested. Admin will review and approve.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit request. Please try again.']);
}
$insertStmt->close();
$conn->close();
