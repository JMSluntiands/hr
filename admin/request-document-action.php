<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';

$msg = '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$conn || !$id || !in_array($action, ['approve', 'decline'], true)) {
    $_SESSION['request_document_msg'] = 'Invalid request.';
    header('Location: request-document.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

$hasApprovedByName = false;
$chk = @$conn->query("SHOW COLUMNS FROM document_requests LIKE 'approved_by_name'");
if ($chk && $chk->num_rows > 0) {
    $hasApprovedByName = true;
}

if ($action === 'approve') {
    if ($hasApprovedByName) {
        $stmt = $conn->prepare("UPDATE document_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), approved_by_name = ?, rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param('isi', $adminId, $adminName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE document_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param('ii', $adminId, $id);
    }
    if ($stmt->execute()) {
        $_SESSION['request_document_msg'] = '✓ Document request approved.';
    } else {
        $_SESSION['request_document_msg'] = 'Failed to approve.';
    }
    $stmt->close();
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $_SESSION['request_document_msg'] = 'Please provide a reason for declining.';
        header('Location: request-document.php');
        exit;
    }
    $stmt = $conn->prepare("UPDATE document_requests SET status = 'Rejected', rejection_reason = ?, approved_by = NULL, approved_at = NULL WHERE id = ?");
    $stmt->bind_param('si', $reason, $id);
    if ($stmt->execute()) {
        $_SESSION['request_document_msg'] = 'Document request declined.';
    } else {
        $_SESSION['request_document_msg'] = 'Failed to decline.';
    }
    $stmt->close();
}

header('Location: request-document.php');
exit;
