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
    $_SESSION['document_file_msg'] = 'Invalid request.';
    header('Location: request-document-file.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

// Get document upload details for logging
$docStmt = $conn->prepare("SELECT edu.*, e.full_name FROM employee_document_uploads edu JOIN employees e ON edu.employee_id = e.id WHERE edu.id = ?");
$docStmt->bind_param('i', $id);
$docStmt->execute();
$docResult = $docStmt->get_result();
$docData = $docResult->fetch_assoc();
$docStmt->close();

$empName = $docData['full_name'] ?? 'Unknown';
$docType = $docData['document_type'] ?? 'Unknown';

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE employee_document_uploads SET status = 'Approved', approved_by = ?, approved_by_name = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
    $stmt->bind_param('isi', $adminId, $adminName, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Approve Document File', 'Document File', $id, "Approved $docType upload for $empName");
        $_SESSION['document_file_msg'] = '✓ Document file approved.';
    } else {
        $_SESSION['document_file_msg'] = 'Failed to approve.';
    }
    $stmt->close();
} else {
    $reason = trim($_POST['rejection_reason'] ?? '');
    if ($reason === '') {
        $_SESSION['document_file_msg'] = 'Please provide a reason for declining.';
        header('Location: request-document-file.php');
        exit;
    }
    $stmt = $conn->prepare("UPDATE employee_document_uploads SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_by_name = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param('sisi', $reason, $adminId, $adminName, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Decline Document File', 'Document File', $id, "Declined $docType upload for $empName. Reason: " . substr($reason, 0, 100));
        $_SESSION['document_file_msg'] = 'Document file declined.';
    } else {
        $_SESSION['document_file_msg'] = 'Failed to decline.';
    }
    $stmt->close();
}

header('Location: request-document-file.php');
exit;
