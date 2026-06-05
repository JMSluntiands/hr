<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../database/db.php';

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$empStmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
$empStmt->bind_param('s', $user['email']);
$empStmt->execute();
$emp = $empStmt->get_result()->fetch_assoc();
$empStmt->close();

if (!$emp) {
    echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
    exit;
}

$employeeId = (int)$emp['id'];
$docId = (int)($_POST['id'] ?? 0);

if ($docId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid document']);
    exit;
}

$colCheck = $conn->query("SHOW COLUMNS FROM employee_document_uploads LIKE 'deletion_requested_at'");
if (!$colCheck || $colCheck->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Document removal is not configured yet. Please contact HR.']);
    exit;
}

$docStmt = $conn->prepare("SELECT id, employee_id, status, deletion_requested_at FROM employee_document_uploads WHERE id = ? LIMIT 1");
$docStmt->bind_param('i', $docId);
$docStmt->execute();
$doc = $docStmt->get_result()->fetch_assoc();
$docStmt->close();

if (!$doc || (int)$doc['employee_id'] !== $employeeId) {
    echo json_encode(['status' => 'error', 'message' => 'Document not found']);
    exit;
}

if (($doc['status'] ?? '') !== 'Approved') {
    echo json_encode(['status' => 'error', 'message' => 'Only verified documents can be requested for removal.']);
    exit;
}

if (!empty($doc['deletion_requested_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'A removal request is already pending for this document.']);
    exit;
}

$upd = $conn->prepare("UPDATE employee_document_uploads SET deletion_requested_at = NOW() WHERE id = ? AND employee_id = ? AND status = 'Approved' AND deletion_requested_at IS NULL");
$upd->bind_param('ii', $docId, $employeeId);
if ($upd->execute() && $upd->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Removal request sent. HR will review; the file will stay in admin archive if approved.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Could not submit request. Please try again.']);
}
$upd->close();
