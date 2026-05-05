<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/incident_reports_schema.php';

$redirect = 'incident-report-list';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    header('Location: ' . $redirect);
    exit;
}

ensureIncidentReportsTable($conn);

$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_POST['report_id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['incident_report_flash'] = 'Invalid delete request.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt = $conn->prepare('SELECT attachment_path FROM incident_reports WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) {
        $_SESSION['incident_report_flash'] = 'Report not found.';
        header('Location: ' . $redirect);
        exit;
    }
    $path = (string)($row['attachment_path'] ?? '');
    $del = $conn->prepare('DELETE FROM incident_reports WHERE id = ?');
    $del->bind_param('i', $id);
    if ($del->execute()) {
        if ($path !== '' && strpos($path, 'uploads/incident_reports/') === 0) {
            $full = __DIR__ . '/../' . $path;
            if (is_file($full)) {
                @unlink($full);
            }
        }
        $_SESSION['incident_report_flash'] = 'Report deleted.';
    } else {
        $_SESSION['incident_report_flash'] = 'Could not delete report.';
    }
    $del->close();
    header('Location: ' . $redirect);
    exit;
}

$allowed = incidentReportAllowedTypes();

$companyName = trim($_POST['company_name'] ?? '');
$employeeName = trim($_POST['employee_name'] ?? '');
$locationArea = trim($_POST['location_area'] ?? '');
$incidentDate = trim($_POST['incident_date'] ?? '');
$incidentTime = trim($_POST['incident_time'] ?? '');
$incidentType = trim($_POST['incident_type'] ?? '');
$incidentDetails = trim($_POST['incident_details'] ?? '');
$witnessName = trim($_POST['witness_name'] ?? '');
$anyoneInjured = trim($_POST['anyone_injured'] ?? 'No');
$injuryTypes = trim($_POST['injury_types'] ?? '');
$injuryDetails = trim($_POST['injury_details'] ?? '');
$reportDate = trim($_POST['report_date'] ?? '');
$reportTime = trim($_POST['report_time'] ?? '');
$actionTaken = trim($_POST['action_taken'] ?? '');

if ($anyoneInjured !== 'Yes') {
    $anyoneInjured = 'No';
    $injuryTypes = '';
    $injuryDetails = '';
}

if (
    $companyName === '' || $employeeName === '' || $locationArea === '' || $incidentDate === ''
    || $incidentTime === '' || $incidentDetails === '' || $reportDate === '' || $reportTime === ''
    || !in_array($incidentType, $allowed, true)
) {
    $_SESSION['incident_report_flash'] = 'Please complete all required fields.';
    header('Location: ' . $redirect);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$witnessNameDb = $witnessName === '' ? '' : $witnessName;
$injuryTypesDb = null;
$injuryDetailsDb = null;
if ($anyoneInjured === 'Yes') {
    $injuryTypesDb = $injuryTypes !== '' ? $injuryTypes : null;
    $injuryDetailsDb = $injuryDetails !== '' ? $injuryDetails : null;
}
$actionTakenDb = null;
if ($actionTaken !== '') {
    $actionTakenDb = $actionTaken;
}

function incidentReportSaveUpload(int $userId): ?string
{
    if (empty($_FILES['attachment']['name']) || (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES['attachment'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($f['size'] > 5 * 1024 * 1024) {
        return false;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $okExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($ext, $okExt, true)) {
        return false;
    }
    $dir = __DIR__ . '/../uploads/incident_reports/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $basename = 'ir_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . $basename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        return false;
    }
    return 'uploads/incident_reports/' . $basename;
}

if ($action === 'create') {
    $upload = incidentReportSaveUpload($userId);
    if ($upload === false) {
        $_SESSION['incident_report_flash'] = 'Attachment invalid or upload failed.';
        header('Location: ' . $redirect);
        exit;
    }
    $attachmentPath = $upload;

    $stmt = $conn->prepare(
        'INSERT INTO incident_reports (
            submitted_by_user_id, company_name, employee_name, location_area,
            incident_date, incident_time, incident_type, incident_details,
            witness_name, anyone_injured, injury_types, injury_details,
            report_date, report_time, action_taken, attachment_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        $_SESSION['incident_report_flash'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param(
        'i' . str_repeat('s', 15),
        $userId,
        $companyName,
        $employeeName,
        $locationArea,
        $incidentDate,
        $incidentTime,
        $incidentType,
        $incidentDetails,
        $witnessNameDb,
        $anyoneInjured,
        $injuryTypesDb,
        $injuryDetailsDb,
        $reportDate,
        $reportTime,
        $actionTakenDb,
        $attachmentPath
    );
    if ($stmt->execute()) {
        $_SESSION['incident_report_flash'] = 'Incident report saved.';
    } else {
        $_SESSION['incident_report_flash'] = 'Could not save report.';
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['report_id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['incident_report_flash'] = 'Invalid update.';
        header('Location: ' . $redirect);
        exit;
    }
    $cur = $conn->prepare('SELECT attachment_path FROM incident_reports WHERE id = ? LIMIT 1');
    $cur->bind_param('i', $id);
    $cur->execute();
    $cr = $cur->get_result();
    $existing = $cr ? $cr->fetch_assoc() : null;
    $cur->close();
    if (!$existing) {
        $_SESSION['incident_report_flash'] = 'Report not found.';
        header('Location: ' . $redirect);
        exit;
    }
    $oldPath = (string)($existing['attachment_path'] ?? '');
    $attachmentPath = $oldPath;

    $upload = incidentReportSaveUpload($userId);
    if ($upload === false) {
        $_SESSION['incident_report_flash'] = 'Attachment invalid or upload failed.';
        header('Location: incident-report-edit?id=' . $id);
        exit;
    }
    if ($upload !== null) {
        $attachmentPath = $upload;
    }

    $stmt = $conn->prepare(
        'UPDATE incident_reports SET
            company_name = ?, employee_name = ?, location_area = ?,
            incident_date = ?, incident_time = ?, incident_type = ?, incident_details = ?,
            witness_name = ?, anyone_injured = ?, injury_types = ?, injury_details = ?,
            report_date = ?, report_time = ?, action_taken = ?, attachment_path = ?
        WHERE id = ?'
    );
    if (!$stmt) {
        $_SESSION['incident_report_flash'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param(
        str_repeat('s', 15) . 'i',
        $companyName,
        $employeeName,
        $locationArea,
        $incidentDate,
        $incidentTime,
        $incidentType,
        $incidentDetails,
        $witnessNameDb,
        $anyoneInjured,
        $injuryTypesDb,
        $injuryDetailsDb,
        $reportDate,
        $reportTime,
        $actionTakenDb,
        $attachmentPath,
        $id
    );
    if ($stmt->execute()) {
        if ($upload !== null && $oldPath !== '' && $oldPath !== $attachmentPath && strpos($oldPath, 'uploads/incident_reports/') === 0) {
            $full = __DIR__ . '/../' . $oldPath;
            if (is_file($full)) {
                @unlink($full);
            }
        }
        $_SESSION['incident_report_flash'] = 'Report updated.';
        header('Location: ' . $redirect);
    } else {
        $_SESSION['incident_report_flash'] = 'Could not update report.';
        header('Location: incident-report-edit?id=' . $id);
    }
    $stmt->close();
    exit;
}

$_SESSION['incident_report_flash'] = 'Unknown request.';
header('Location: ' . $redirect);
exit;
