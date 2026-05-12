<?php
session_start();

require_once __DIR__ . '/../controller/session_timeout.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/include/employee_data.php';
require_once __DIR__ . '/../include/ensure_document_requests_coe_columns.php';

if (!$conn || !$employeeDbId) {
    $_SESSION['request_cert_msg'] = 'Could not verify your account. Please try again.';
    header('Location: request.php');
    exit;
}

ensure_document_requests_coe_columns($conn);

$docMap = [
    'COE' => 'COE',
    'SSS' => 'SSS Certificate',
    'PAGIBIG' => 'Pag-IBIG Certificate',
    'PHILHEALTH' => 'PhilHealth Certificate',
];

$code = strtoupper(trim($_POST['document_code'] ?? ''));
$documentType = $docMap[$code] ?? '';

if ($documentType === '') {
    $_SESSION['request_cert_msg'] = 'Invalid document type.';
    header('Location: request.php');
    exit;
}

$coePurposes = [
    'Bank Account',
    'Loan and Credit Application',
    'Visa and Travel Requirements',
    'Employment',
    'Job Application',
    'Government Transaction',
    'Rental',
    'Leasing Agreement',
    'Others',
];

$coePurpose = null;
$coeIncludeSalary = null;

if ($documentType === 'COE') {
    $coePurpose = trim($_POST['coe_purpose'] ?? '');
    $coeIncludeSalary = $_POST['coe_include_salary'] ?? '';

    if (!in_array($coePurpose, $coePurposes, true)) {
        $_SESSION['request_cert_msg'] = 'Please select a valid purpose for your COE request.';
        header('Location: request.php');
        exit;
    }
    if (!in_array($coeIncludeSalary, ['Yes', 'No'], true)) {
        $_SESSION['request_cert_msg'] = 'Please indicate whether salary information should be included on the COE.';
        header('Location: request.php');
        exit;
    }
}

$pendingStmt = $conn->prepare(
    'SELECT id FROM document_requests WHERE employee_id = ? AND document_type = ? AND status = ? LIMIT 1'
);
$pending = 'Pending';
$pendingStmt->bind_param('iss', $employeeDbId, $documentType, $pending);
$pendingStmt->execute();
$pendingRes = $pendingStmt->get_result();
if ($pendingRes && $pendingRes->num_rows > 0) {
    $pendingStmt->close();
    $_SESSION['request_cert_msg'] = 'You already have a pending request for this document.';
    header('Location: request.php');
    exit;
}
$pendingStmt->close();

if ($documentType === 'COE') {
    $ins = $conn->prepare(
        'INSERT INTO document_requests (employee_id, document_type, coe_purpose, coe_include_salary, status) VALUES (?, ?, ?, ?, ?)'
    );
    $ins->bind_param('issss', $employeeDbId, $documentType, $coePurpose, $coeIncludeSalary, $pending);
} else {
    $ins = $conn->prepare(
        'INSERT INTO document_requests (employee_id, document_type, coe_purpose, coe_include_salary, status) VALUES (?, ?, NULL, NULL, ?)'
    );
    $ins->bind_param('iss', $employeeDbId, $documentType, $pending);
}

if ($ins->execute()) {
    $newId = (int)$conn->insert_id;
    if ($newId <= 0) {
        $_SESSION['request_cert_msg'] = 'Your request could not be recorded correctly (invalid id). Please contact HR or try again in a moment.';
    } else {
        $_SESSION['request_cert_msg_ok'] = true;
        $_SESSION['request_cert_msg'] = 'Your document request was submitted. HR will review it shortly.';
    }
} else {
    $_SESSION['request_cert_msg'] = 'Could not submit your request. Please try again later.';
}
$ins->close();

header('Location: request.php');
exit;
