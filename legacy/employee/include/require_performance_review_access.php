<?php
/**
 * Guards employee performance module: session, DB row, department flag, submissions table.
 * Sets $conn, $employeeDbId, $employeeName, etc. via employee_data.
 */
if (! defined('HR_LEGACY_EMBEDDED')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (! isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
} elseif (! isset($_SESSION['user_id']) || (int) ($_SESSION['user_id'] ?? 0) <= 0) {
    header('Location: '.(defined('HR_APP_URL') ? HR_APP_URL : '/'));
    exit;
}

require_once __DIR__ . '/../../controller/session_timeout.php';

include __DIR__ . '/../../database/db.php';
include __DIR__ . '/employee_data.php';
require_once __DIR__ . '/performance_review_helpers.php';
require_once __DIR__ . '/performance_employee_urls.inc.php';

if (! $conn || ! $employeeDbId) {
    header('Location: '.hr_performance_employee_url('index.php', 'dashboard'));
    exit;
}

$department = $department ?? '';
if (! hr_department_performance_review_enabled($conn, (string) $department)) {
    $_SESSION['performance_review_denied'] = '1';
    header('Location: '.hr_performance_employee_url('index.php', 'dashboard'));
    exit;
}

hr_ensure_staff_performance_reviews_table($conn);
