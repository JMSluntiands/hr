<?php
/**
 * Fetches current employee from DB (user_login -> employees).
 * Sets: $employeeData, $employeeName, $employeePhoto, $position, $department, $employeeId, $dateHired, $employeeDbId
 */
$employeeData = null;
$employeeDbId = null;
$employeeName = 'Employee';
$employeePhoto = null;
$position = '';
$department = '';
$employeeId = '';
$dateHired = '';

if (!isset($conn) || !$conn) {
    return;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    return;
}

$userStmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if ($user) {
    $empStmt = $conn->prepare("SELECT id, employee_id, full_name, email, position, department, date_hired, profile_picture FROM employees WHERE email = ?");
    $empStmt->bind_param('s', $user['email']);
    $empStmt->execute();
    $empResult = $empStmt->get_result();
    $employeeData = $empResult->fetch_assoc();
    $empStmt->close();

    if ($employeeData) {
        $employeeDbId = (int)$employeeData['id'];
        $employeeName = $employeeData['full_name'] ?? 'Employee';
        $employeePhoto = !empty($employeeData['profile_picture']) ? $employeeData['profile_picture'] : null;
        $position = $employeeData['position'] ?? '';
        $department = $employeeData['department'] ?? '';
        $employeeId = $employeeData['employee_id'] ?? '';
        $dateHired = !empty($employeeData['date_hired']) ? date('M d, Y', strtotime($employeeData['date_hired'])) : '';
    }
}
