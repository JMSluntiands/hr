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
    $_SESSION['request_document_msg'] = 'Invalid request.';
    header('Location: request-document.php');
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
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update employee_document_uploads status
        $stmt = $conn->prepare("UPDATE employee_document_uploads SET status = 'Approved', approved_by = ?, approved_by_name = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
        $stmt->bind_param('isi', $adminId, $adminName, $id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update document upload: ' . $stmt->error);
        }
        $stmt->close();
        
        // Move to document_files table
        $employeeId = $docData['employee_id'] ?? 0;
        $filePath = $docData['file_path'] ?? '';
        
        // Check if document_files table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
        if ($checkTable && $checkTable->num_rows > 0) {
            // Check if already exists to avoid duplicates
            $checkExists = $conn->prepare("SELECT id FROM document_files WHERE employee_id = ? AND document_type = ? AND file_path = ? LIMIT 1");
            $checkExists->bind_param('iss', $employeeId, $docType, $filePath);
            $checkExists->execute();
            $existsResult = $checkExists->get_result();
            
            if (!$existsResult || $existsResult->num_rows === 0) {
                // Insert into document_files
                $insertStmt = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                if ($insertStmt) {
                    $insertStmt->bind_param('issss', $employeeId, $docType, $filePath, $adminId, $adminName);
                    if (!$insertStmt->execute()) {
                        $errorMsg = 'Failed to insert into document_files: ' . $insertStmt->error;
                        error_log($errorMsg);
                        $_SESSION['debug_error'] = $errorMsg;
                    } else {
                        $_SESSION['debug_success'] = "Inserted into document_files: Employee $employeeId, Type: $docType, File: $filePath";
                    }
                    $insertStmt->close();
                } else {
                    error_log('Failed to prepare insert statement: ' . $conn->error);
                    $_SESSION['debug_error'] = 'Failed to prepare insert: ' . $conn->error;
                }
            } else {
                $_SESSION['debug_info'] = "Document already exists in document_files";
            }
            $checkExists->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        logActivity($conn, 'Approve Document File', 'Document File', $id, "Approved $docType upload for $empName");
        $_SESSION['request_document_msg'] = '✓ Document file approved and moved to document files.';
        
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
    $stmt = $conn->prepare("UPDATE employee_document_uploads SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_by_name = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param('sisi', $reason, $adminId, $adminName, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Decline Document File', 'Document File', $id, "Declined $docType upload for $empName. Reason: " . substr($reason, 0, 100));
        $_SESSION['request_document_msg'] = 'Document file declined.';
    } else {
        $_SESSION['request_document_msg'] = 'Failed to decline.';
    }
    $stmt->close();
}

header('Location: request-document.php');
exit;
