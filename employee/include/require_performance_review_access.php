<?php
/**
 * Guards employee performance module: session, DB row, department flag, submissions table.
 * Call after session_start(). Sets $conn, $employeeDbId, $employeeName, etc. via employee_data.
 */
if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../controller/session_timeout.php';

include __DIR__ . '/../../database/db.php';
include __DIR__ . '/employee_data.php';
require_once __DIR__ . '/performance_review_helpers.php';

if (!$conn || !$employeeDbId) {
    header('Location: index.php');
    exit;
}

$department = $department ?? '';
if (!hr_department_performance_review_enabled($conn, (string)$department)) {
    $_SESSION['performance_review_denied'] = '1';
    header('Location: index.php');
    exit;
}

hr_ensure_staff_performance_reviews_table($conn);
