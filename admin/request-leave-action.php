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

// Get leave request details for logging and updating allocations
$lrStmt = $conn->prepare("SELECT lr.*, e.full_name, 
                          COALESCE(lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1) as calculated_days
                          FROM leave_requests lr 
                          JOIN employees e ON lr.employee_id = e.id 
                          WHERE lr.id = ?");
$lrStmt->bind_param('i', $id);
$lrStmt->execute();
$lrResult = $lrStmt->get_result();
$lrData = $lrResult->fetch_assoc();
$lrStmt->close();

$empName = $lrData['full_name'] ?? 'Unknown';
$leaveType = $lrData['leave_type'] ?? 'Unknown';
$employeeId = (int)($lrData['employee_id'] ?? 0);
$leaveDays = (int)($lrData['calculated_days'] ?? 0);

if ($action === 'approve') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update leave request status
        if ($hasApprovedByName) {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), approved_by_name = ?, rejection_reason = NULL WHERE id = ?");
            $stmt->bind_param('isi', $adminId, $adminName, $id);
        } else {
            $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
            $stmt->bind_param('ii', $adminId, $id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update leave request: ' . $stmt->error);
        }
        $stmt->close();
        
        // Update leave_allocations: increase used_days and decrease remaining_days
        // Handle BL and EL - they use VL credits
        $checkLeaveType = $leaveType;
        if ($leaveType === 'Bereavement Leave' || $leaveType === 'Emergency Leave') {
            $checkLeaveType = 'Vacation Leave';
        }
        
        // Only update allocations for SL, VL, BL, EL
        if (in_array($leaveType, ['Sick Leave', 'Vacation Leave', 'Bereavement Leave', 'Emergency Leave'])) {
            $currentYear = (int)date('Y');
            $updateAllocStmt = $conn->prepare("UPDATE leave_allocations 
                                               SET used_days = used_days + ?,
                                                   remaining_days = GREATEST(0, remaining_days - ?)
                                               WHERE employee_id = ? 
                                               AND leave_type = ? 
                                               AND year = ?");
            
            if (!$updateAllocStmt) {
                throw new Exception('Failed to prepare allocation update: ' . $conn->error);
            }
            
            $updateAllocStmt->bind_param('iiisi', $leaveDays, $leaveDays, $employeeId, $checkLeaveType, $currentYear);
            
            if (!$updateAllocStmt->execute()) {
                throw new Exception('Failed to update leave allocations: ' . $updateAllocStmt->error);
            }
            $updateAllocStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        logActivity($conn, 'Approve Leave Request', 'Leave Request', $id, "Approved $leaveType request for $empName");
        $_SESSION['request_leaves_msg'] = '✓ Leave request approved.';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['request_leaves_msg'] = 'Failed to approve: ' . $e->getMessage();
    }
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
