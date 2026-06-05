<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

include '../database/db.php';

// Get employee ID
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

// Check if table exists, if not create it
$checkTable = $conn->query("SHOW TABLES LIKE 'employee_bank_details'");
if ($checkTable->num_rows == 0) {
    // Create table
    $createTable = "CREATE TABLE IF NOT EXISTS `employee_bank_details` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `employee_id` int(11) NOT NULL,
      `bank_name` varchar(255) NOT NULL,
      `account_number` varchar(100) NOT NULL,
      `account_name` varchar(255) NOT NULL,
      `account_type` enum('Savings','Checking','Current') DEFAULT 'Savings',
      `branch` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_employee_bank` (`employee_id`),
      KEY `idx_employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($createTable)) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
}

// Get form data
$bankName = trim($_POST['bank_name'] ?? '');
$accountNumber = trim($_POST['account_number'] ?? '');
$accountName = trim($_POST['account_name'] ?? '');
$accountType = $_POST['account_type'] ?? 'Savings';
$branch = trim($_POST['branch'] ?? '');

if (empty($bankName) || empty($accountNumber) || empty($accountName)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
    exit;
}

// Check if bank details already exist
$checkStmt = $conn->prepare("SELECT id FROM employee_bank_details WHERE employee_id = ?");
$checkStmt->bind_param('i', $employeeDbId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$existing = $checkResult->fetch_assoc();
$checkStmt->close();

if ($existing) {
    // Update existing
    $updateStmt = $conn->prepare("UPDATE employee_bank_details SET bank_name = ?, account_number = ?, account_name = ?, account_type = ?, branch = ? WHERE employee_id = ?");
    $updateStmt->bind_param('sssssi', $bankName, $accountNumber, $accountName, $accountType, $branch, $employeeDbId);
    
    if ($updateStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bank details updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating bank details: ' . $conn->error]);
    }
    $updateStmt->close();
} else {
    // Insert new
    $insertStmt = $conn->prepare("INSERT INTO employee_bank_details (employee_id, bank_name, account_number, account_name, account_type, branch) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param('isssss', $employeeDbId, $bankName, $accountNumber, $accountName, $accountType, $branch);
    
    if ($insertStmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bank details added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding bank details: ' . $conn->error]);
    }
    $insertStmt->close();
}

$conn->close();
?>
