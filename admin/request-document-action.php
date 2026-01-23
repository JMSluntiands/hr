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

// Get document request details for logging
$drStmt = $conn->prepare("SELECT dr.*, e.full_name FROM document_requests dr JOIN employees e ON dr.employee_id = e.id WHERE dr.id = ?");
$drStmt->bind_param('i', $id);
$drStmt->execute();
$drResult = $drStmt->get_result();
$drData = $drResult->fetch_assoc();
$drStmt->close();

$empName = $drData['full_name'] ?? 'Unknown';
$docType = $drData['document_type'] ?? 'Unknown';

if ($action === 'approve') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update document request status
        if ($hasApprovedByName) {
            $stmt = $conn->prepare("UPDATE document_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), approved_by_name = ?, rejection_reason = NULL WHERE id = ?");
            $stmt->bind_param('isi', $adminId, $adminName, $id);
        } else {
            $stmt = $conn->prepare("UPDATE document_requests SET status = 'Approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
            $stmt->bind_param('ii', $adminId, $id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update document request: ' . $stmt->error);
        }
        $stmt->close();
        
        // Move to document_files table
        $employeeId = $drData['employee_id'] ?? 0;
        $filePath = ''; // Document file path - can be updated later if files are stored
        
        // Check if document_files table exists and has required columns
        $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
        if ($checkTable && $checkTable->num_rows > 0) {
            // Insert into document_files
            $insertStmt = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            if ($insertStmt) {
                $insertStmt->bind_param('issss', $employeeId, $docType, $filePath, $adminId, $adminName);
                if (!$insertStmt->execute()) {
                    // Log error but don't fail the transaction
                    error_log('Failed to insert into document_files: ' . $insertStmt->error);
                }
                $insertStmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        logActivity($conn, 'Approve Document Request', 'Document Request', $id, "Approved $docType request for $empName");
        $_SESSION['request_document_msg'] = '✓ Document request approved and moved to document files.';
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['request_document_msg'] = 'Failed to approve: ' . $e->getMessage();
    }
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
        logActivity($conn, 'Decline Document Request', 'Document Request', $id, "Declined $docType request for $empName. Reason: " . substr($reason, 0, 100));
        $_SESSION['request_document_msg'] = 'Document request declined.';
    } else {
        $_SESSION['request_document_msg'] = 'Failed to decline.';
    }
    $stmt->close();
}

header('Location: request-document.php');
exit;
