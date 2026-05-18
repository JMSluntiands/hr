<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/activity-logger.php';
require_once __DIR__ . '/../include/admin-permissions.php';

$userRole = strtolower($_SESSION['role'] ?? '');
if ($userRole !== 'admin') {
    $_SESSION['document_archive_msg'] = 'Unauthorized.';
    header('Location: document-archive.php');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$conn || !$id || !in_array($action, ['approve', 'reject'], true)) {
    $_SESSION['document_archive_msg'] = 'Invalid request.';
    header('Location: document-archive.php');
    exit;
}

$colCheck = $conn->query("SHOW COLUMNS FROM employee_document_uploads LIKE 'deletion_requested_at'");
$tableCheck = $conn->query("SHOW TABLES LIKE 'document_archive'");
if (!$colCheck || $colCheck->num_rows === 0 || !$tableCheck || $tableCheck->num_rows === 0) {
    $_SESSION['document_archive_msg'] = 'Archive tables are not set up. Run database/setup_document_deletion_archive.php first.';
    header('Location: document-archive.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin';

$docStmt = $conn->prepare("SELECT edu.*, e.full_name FROM employee_document_uploads edu JOIN employees e ON edu.employee_id = e.id WHERE edu.id = ? LIMIT 1");
$docStmt->bind_param('i', $id);
$docStmt->execute();
$docData = $docStmt->get_result()->fetch_assoc();
$docStmt->close();

if (!$docData || empty($docData['deletion_requested_at']) || ($docData['status'] ?? '') !== 'Approved') {
    $_SESSION['document_archive_msg'] = 'This document is not pending removal.';
    header('Location: document-archive.php');
    exit;
}

$empName = $docData['full_name'] ?? 'Unknown';
$docType = $docData['document_type'] ?? 'Unknown';
$employeeId = (int)$docData['employee_id'];
$filePathRel = $docData['file_path'] ?? '';

if (!adminCanApproveEmployee($conn, $adminId, 'approve_document_removal', $employeeId)) {
    adminDenyApprovalRedirect('document_archive_msg', 'document-archive.php');
}

if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE employee_document_uploads SET deletion_requested_at = NULL WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Reject Document Removal', 'Document', $id, "Declined removal for $docType — $empName (document stays active)");
        $_SESSION['document_archive_msg'] = 'Removal request declined. The employee keeps access to the document.';
    } else {
        $_SESSION['document_archive_msg'] = 'Failed to decline request.';
    }
    $stmt->close();
    header('Location: document-archive.php');
    exit;
}

// approve: move file to archive folder, insert document_archive, remove from document_files + employee_document_uploads
$conn->begin_transaction();
try {
    $uploadRoot = dirname(__DIR__) . '/uploads/';
    $src = $uploadRoot . $filePathRel;
    $archiveDir = $uploadRoot . 'employee_documents_archive/';
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }

    $ext = pathinfo($filePathRel, PATHINFO_EXTENSION);
    $safeExt = $ext !== '' ? '.' . preg_replace('/[^a-z0-9]+/i', '', $ext) : '';
    $newBase = $employeeId . '_' . $id . '_' . time() . $safeExt;
    $archiveRel = 'employee_documents_archive/' . $newBase;
    $dest = $uploadRoot . $archiveRel;

    if (is_file($src)) {
        if (!rename($src, $dest)) {
            if (!copy($src, $dest)) {
                throw new Exception('Could not move file to archive.');
            }
            @unlink($src);
        }
    } else {
        $archiveRel = $filePathRel;
    }

    $reqAt = $docData['deletion_requested_at'];
    $ins = $conn->prepare("INSERT INTO document_archive (employee_id, employee_full_name, document_type, file_path, source_upload_id, deletion_requested_at, archived_by, archived_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->bind_param('issisiss', $employeeId, $empName, $docType, $archiveRel, $id, $reqAt, $adminId, $adminName);
    if (!$ins->execute()) {
        throw new Exception('Failed to save archive record: ' . $ins->error);
    }
    $ins->close();

    $dfDel = $conn->prepare("DELETE FROM document_files WHERE employee_id = ? AND file_path = ?");
    $dfDel->bind_param('is', $employeeId, $filePathRel);
    $dfDel->execute();
    $dfDel->close();

    $delEdu = $conn->prepare("DELETE FROM employee_document_uploads WHERE id = ?");
    $delEdu->bind_param('i', $id);
    if (!$delEdu->execute()) {
        throw new Exception('Failed to remove upload record.');
    }
    $delEdu->close();

    $conn->commit();
    logActivity($conn, 'Approve Document Removal', 'Document Archive', $id, "Archived $docType for $empName after approved removal");
    $_SESSION['document_archive_msg'] = '✓ Removal approved. File is in Document Archive and removed from the employee profile.';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['document_archive_msg'] = 'Failed: ' . $e->getMessage();
}

header('Location: document-archive.php');
exit;
