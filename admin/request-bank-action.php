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

// Use r.employee_id (numeric) for saving; alias e.employee_id so it doesn't overwrite r.employee_id
$reqStmt = $conn->prepare("SELECT r.*, e.full_name, e.employee_id AS employee_badge FROM bank_account_change_requests r JOIN employees e ON e.id = r.employee_id WHERE r.id = ? AND r.status = 'Pending'");
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
        // 1. Ensure employee_bank_details table exists
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

        // 2. Write to employee_bank_details FIRST (so we never mark Approved unless this succeeds)
        $existStmt = $conn->prepare("SELECT id FROM employee_bank_details WHERE employee_id = ?");
        $existStmt->bind_param('i', $employeeId);
        $existStmt->execute();
        $existResult = $existStmt->get_result();
        $exists = $existResult->fetch_assoc();
        $existStmt->close();

        if ($exists) {
            $updBank = $conn->prepare("UPDATE employee_bank_details SET bank_name = ?, account_number = ?, account_name = ?, account_type = ?, branch = ?, updated_at = NOW() WHERE employee_id = ?");
            $updBank->bind_param('sssssi', $bankName, $accountNumber, $accountName, $accountType, $branch, $employeeId);
            if (!$updBank->execute()) {
                throw new Exception('Failed to update employee bank details: ' . $conn->error);
            }
            $updBank->close();
        } else {
            $insBank = $conn->prepare("INSERT INTO employee_bank_details (employee_id, bank_name, account_number, account_name, account_type, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insBank->bind_param('isssss', $employeeId, $bankName, $accountNumber, $accountName, $accountType, $branch);
            if (!$insBank->execute()) {
                throw new Exception('Failed to save employee bank details: ' . $conn->error);
            }
            $insBank->close();
        }

        // 3. Verify the row exists so employee will see it
        $verifyStmt = $conn->prepare("SELECT id, bank_name, account_number FROM employee_bank_details WHERE employee_id = ? LIMIT 1");
        $verifyStmt->bind_param('i', $employeeId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verified = $verifyResult->fetch_assoc();
        $verifyStmt->close();
        if (!$verified) {
            throw new Exception('Bank details were not saved. Please try again.');
        }

        // 4. Only then mark the request as Approved
        $upd = $conn->prepare("UPDATE bank_account_change_requests SET status = 'Approved', approved_by = ?, approved_by_name = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $upd->bind_param('isi', $adminId, $adminName, $id);
        if (!$upd->execute()) {
            throw new Exception('Failed to update request status');
        }
        $upd->close();

        $conn->commit();
        logActivity($conn, 'Approve Bank Account Change', 'Bank Request', $id, "Approved bank account change for $empName");
        $_SESSION['request_bank_msg'] = '✓ Bank account change approved and updated. Employee can refresh My Compensation to see their bank details.';
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
