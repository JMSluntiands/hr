<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'employee') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../controller/session_timeout.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/incident_reports_schema.php';

$redirect = 'incident-report-list.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$conn) {
    header('Location: ' . $redirect);
    exit;
}

ensureIncidentReportsTable($conn);

$action = $_POST['action'] ?? '';
if ($action !== 'create') {
    $_SESSION['incident_report_flash'] = 'Invalid request.';
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
$attachmentPath = null;
$injuryTypesDb = null;
$injuryDetailsDb = null;
$actionTakenDb = null;

if (!empty($_FILES['attachment']['name']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['attachment'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['incident_report_flash'] = 'Attachment upload failed.';
        header('Location: ' . $redirect);
        exit;
    }
    if ($f['size'] > 5 * 1024 * 1024) {
        $_SESSION['incident_report_flash'] = 'Attachment must be 5MB or smaller.';
        header('Location: ' . $redirect);
        exit;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $okExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($ext, $okExt, true)) {
        $_SESSION['incident_report_flash'] = 'Invalid file type.';
        header('Location: ' . $redirect);
        exit;
    }
    $dir = __DIR__ . '/../uploads/incident_reports/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $basename = 'ir_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . $basename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $_SESSION['incident_report_flash'] = 'Could not save attachment.';
        header('Location: ' . $redirect);
        exit;
    }
    $attachmentPath = 'uploads/incident_reports/' . $basename;
}

$stmt = $conn->prepare(
    'INSERT INTO incident_reports (
        submitted_by_user_id, company_name, employee_name, location_area,
        incident_date, incident_time, incident_type, incident_details,
        witness_name, anyone_injured, injury_types, injury_details,
        report_date, report_time, action_taken, attachment_path, review_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

if (!$stmt) {
    $_SESSION['incident_report_flash'] = 'Database error.';
    header('Location: ' . $redirect);
    exit;
}

$witnessNameDb = $witnessName === '' ? '' : $witnessName;
if ($anyoneInjured === 'Yes') {
    $injuryTypesDb = $injuryTypes !== '' ? $injuryTypes : null;
    $injuryDetailsDb = $injuryDetails !== '' ? $injuryDetails : null;
}
if ($actionTaken !== '') {
    $actionTakenDb = $actionTaken;
}

$reviewStatus = 'Pending';
$stmt->bind_param(
    'i' . str_repeat('s', 16),
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
    $attachmentPath,
    $reviewStatus
);

if ($stmt->execute()) {
    $_SESSION['incident_report_flash'] = 'Incident report submitted for admin review.';
} else {
    $_SESSION['incident_report_flash'] = 'Could not save report.';
}
$stmt->close();

header('Location: ' . $redirect);
exit;
