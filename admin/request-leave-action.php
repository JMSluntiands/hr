<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/activity-logger.php';

$msg = '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$conn || !$id || !in_array($action, ['approve', 'decline'], true)) {
    $_SESSION['request_leaves_msg'] = 'Invalid request.';
    header('Location: request-leaves.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

// Check if approved_by_name exists
$hasApprovedByName = false;
$chk = @$conn->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_by_name'");
if ($chk && $chk->num_rows > 0) {
    $hasApprovedByName = true;
}

// Get leave request details for logging
$lrStmt = $conn->prepare("SELECT lr.*, e.full_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.id = ?");
$lrStmt->bind_param('i', $id);
$lrStmt->execute();
$lrResult = $lrStmt->get_result();
$lrData = $lrResult->fetch_assoc();
$lrStmt->close();

$empName = $lrData['full_name'] ?? 'Unknown';
$leaveType = $lrData['leave_type'] ?? 'Unknown';

if ($action === 'approve') {
    if ($hasApprovedByName) {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), approved_by_name = ?, rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param('isi', $adminId, $adminName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param('ii', $adminId, $id);
    }
    if ($stmt->execute()) {
        logActivity($conn, 'Approve Leave Request', 'Leave Request', $id, "Approved $leaveType request for $empName");
        $_SESSION['request_leaves_msg'] = '✓ Leave request approved.';
    } else {
        $_SESSION['request_leaves_msg'] = 'Failed to approve.';
    }
    $stmt->close();
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $_SESSION['request_leaves_msg'] = 'Please provide a reason for declining.';
        header('Location: request-leaves.php');
        exit;
    }
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Rejected', rejection_reason = ?, approved_by = NULL, approved_at = NULL WHERE id = ?");
    $stmt->bind_param('si', $reason, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Decline Leave Request', 'Leave Request', $id, "Declined $leaveType request for $empName. Reason: " . substr($reason, 0, 100));
        $_SESSION['request_leaves_msg'] = 'Leave request declined.';
    } else {
        $_SESSION['request_leaves_msg'] = 'Failed to decline.';
    }
    $stmt->close();
}

header('Location: request-leaves.php');
exit;
