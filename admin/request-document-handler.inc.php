<?php
/**
 * Approve / decline document_requests (COE, etc.).
 * Include from request-document.php (same-page POST) or request-document-action.php.
 * Expects session with user_id; requires $conn (mysqli).
 */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../database/db.php';
}

require_once __DIR__ . '/../include/ensure_document_files_request_link.php';
if ($conn) {
    ensure_document_files_request_link($conn);
}

require_once __DIR__ . '/include/activity-logger.php';

$rawAction = (string)($_POST['req_action'] ?? $_GET['req_action'] ?? $_POST['action'] ?? $_GET['action'] ?? '');
$action = strtolower(trim($rawAction));
$id = (int)($_POST['document_request_id'] ?? $_POST['id'] ?? $_GET['document_request_id'] ?? $_GET['id'] ?? 0);

if (!$conn) {
    $_SESSION['request_document_msg'] = 'Database connection failed. Please try again.';
    header('Location: request-document.php');
    exit;
}
if (!$id) {
    $_SESSION['request_document_msg'] = 'Missing document request reference.';
    header('Location: request-document.php');
    exit;
}
if (!in_array($action, ['approve', 'decline'], true)) {
    $_SESSION['request_document_msg'] = 'Missing or invalid action. Please use Approve or Decline from the list.';
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

$drStmt = $conn->prepare("SELECT dr.*, e.full_name FROM document_requests dr JOIN employees e ON dr.employee_id = e.id WHERE dr.id = ?");
$drStmt->bind_param('i', $id);
$drStmt->execute();
$drResult = $drStmt->get_result();
$drData = $drResult->fetch_assoc();
$drStmt->close();

if (!$drData) {
    $_SESSION['request_document_msg'] = 'That document request was not found or may have been removed.';
    header('Location: request-document.php');
    exit;
}

$empName = $drData['full_name'] ?? 'Unknown';
$docType = $drData['document_type'] ?? 'Unknown';

if ($action === 'approve') {
    $conn->begin_transaction();

    try {
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

        $employeeId = (int)($drData['employee_id'] ?? 0);
        $filePath = '';

        $fileCheck = $conn->prepare("SELECT file_path FROM employee_document_uploads WHERE employee_id = ? AND document_type = ? AND status = 'Approved' ORDER BY created_at DESC LIMIT 1");
        if ($fileCheck) {
            $fileCheck->bind_param('is', $employeeId, $docType);
            $fileCheck->execute();
            $fileResult = $fileCheck->get_result();
            if ($fileRow = $fileResult->fetch_assoc()) {
                $filePath = trim((string)($fileRow['file_path'] ?? ''));
            }
            $fileCheck->close();
        }

        require_once __DIR__ . '/../include/ensure_document_request_issued_file.php';
        ensure_issued_file_for_document_request($conn, $employeeId, $id, $docType, $filePath, $adminId, $adminName);
        sync_missing_template_links_for_employee($conn, $employeeId);

        $conn->commit();

        logActivity($conn, 'Approve Document Request', 'Document Request', $id, "Approved $docType request for $empName");
        $_SESSION['request_document_msg'] = '✓ Approved. COE PDF was generated from employee records; other documents use templates or HR uploads when available.';
    } catch (Exception $e) {
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
