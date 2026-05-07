<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/activity-logger.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$conn || !$id || !in_array($action, ['approve', 'decline'], true)) {
    $_SESSION['reimbursement_msg'] = 'Invalid reimbursement action.';
    header('Location: reimbursement-review.php');
    exit;
}

$adminId = (int)($_SESSION['user_id'] ?? 0);
$adminName = (string)($_SESSION['name'] ?? 'Admin');

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE reimbursements SET status = 'Approved', rejection_reason = NULL, approved_by = ?, approved_by_name = ?, approved_at = NOW() WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('isi', $adminId, $adminName, $id);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        logActivity($conn, 'Approve Reimbursement', 'Reimbursement', $id, 'Approved reimbursement request.');
        $_SESSION['reimbursement_msg'] = 'Reimbursement approved.';
    } else {
        $_SESSION['reimbursement_msg'] = 'Failed to approve reimbursement.';
    }
} else {
    $reason = trim((string)($_POST['rejection_reason'] ?? ''));
    if ($reason === '') {
        $_SESSION['reimbursement_msg'] = 'Please provide rejection reason.';
        header('Location: reimbursement-review.php');
        exit;
    }
    $stmt = $conn->prepare("UPDATE reimbursements SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_by_name = ?, approved_at = NOW() WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param('sisi', $reason, $adminId, $adminName, $id);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        logActivity($conn, 'Decline Reimbursement', 'Reimbursement', $id, 'Declined reimbursement request.');
        $_SESSION['reimbursement_msg'] = 'Reimbursement declined.';
    } else {
        $_SESSION['reimbursement_msg'] = 'Failed to decline reimbursement.';
    }
}

header('Location: reimbursement-review.php');
exit;
