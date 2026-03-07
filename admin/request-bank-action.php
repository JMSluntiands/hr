<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/activity-logger.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$conn || !$id || !in_array($action, ['approve', 'decline'], true)) {
    $_SESSION['request_bank_msg'] = 'Invalid request.';
    header('Location: request-bank.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

$reqStmt = $conn->prepare("SELECT r.*, e.full_name, e.employee_id FROM bank_account_change_requests r JOIN employees e ON e.id = r.employee_id WHERE r.id = ? AND r.status = 'Pending'");
$reqStmt->bind_param('i', $id);
$reqStmt->execute();
$reqResult = $reqStmt->get_result();
$req = $reqResult->fetch_assoc();
$reqStmt->close();

if (!$req) {
    $_SESSION['request_bank_msg'] = 'Request not found or already processed.';
    header('Location: request-bank.php');
    exit;
}

$employeeId = (int)$req['employee_id'];
$empName = $req['full_name'] ?? 'Unknown';

if ($action === 'approve') {
    $conn->begin_transaction();
    try {
        $upd = $conn->prepare("UPDATE bank_account_change_requests SET status = 'Approved', approved_by = ?, approved_by_name = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $upd->bind_param('isi', $adminId, $adminName, $id);
        if (!$upd->execute()) {
            throw new Exception('Failed to update request');
        }
        $upd->close();

        $checkBank = $conn->query("SHOW TABLES LIKE 'employee_bank_details'");
        if (!$checkBank || $checkBank->num_rows == 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS `employee_bank_details` (
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
              UNIQUE KEY `unique_employee_bank` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        $bankName = $req['bank_name'];
        $accountNumber = $req['account_number'];
        $accountName = $req['account_name'];
        $accountType = $req['account_type'] ?? 'Savings';
        $branch = $req['branch'] ?? '';

        $existStmt = $conn->prepare("SELECT id FROM employee_bank_details WHERE employee_id = ?");
        $existStmt->bind_param('i', $employeeId);
        $existStmt->execute();
        $existResult = $existStmt->get_result();
        $exists = $existResult->fetch_assoc();
        $existStmt->close();

        if ($exists) {
            $updBank = $conn->prepare("UPDATE employee_bank_details SET bank_name = ?, account_number = ?, account_name = ?, account_type = ?, branch = ? WHERE employee_id = ?");
            $updBank->bind_param('sssssi', $bankName, $accountNumber, $accountName, $accountType, $branch, $employeeId);
            $updBank->execute();
            $updBank->close();
        } else {
            $insBank = $conn->prepare("INSERT INTO employee_bank_details (employee_id, bank_name, account_number, account_name, account_type, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insBank->bind_param('isssss', $employeeId, $bankName, $accountNumber, $accountName, $accountType, $branch);
            $insBank->execute();
            $insBank->close();
        }

        $conn->commit();
        logActivity($conn, 'Approve Bank Account Change', 'Bank Request', $id, "Approved bank account change for $empName");
        $_SESSION['request_bank_msg'] = '✓ Bank account change approved and updated.';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['request_bank_msg'] = 'Failed: ' . $e->getMessage();
    }
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $_SESSION['request_bank_msg'] = 'Please provide a reason for declining.';
        header('Location: request-bank.php');
        exit;
    }
    $upd = $conn->prepare("UPDATE bank_account_change_requests SET status = 'Rejected', approved_by = ?, approved_by_name = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
    $upd->bind_param('issi', $adminId, $adminName, $reason, $id);
    if ($upd->execute()) {
        logActivity($conn, 'Decline Bank Account Change', 'Bank Request', $id, "Declined bank account change for $empName");
        $_SESSION['request_bank_msg'] = 'Bank account change declined.';
    } else {
        $_SESSION['request_bank_msg'] = 'Failed to decline.';
    }
    $upd->close();
}

header('Location: request-bank.php');
exit;
