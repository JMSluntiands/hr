<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../../database/db.php';

$employeeId = $_GET['employee_id'] ?? null;

if (!$employeeId) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
    exit;
}

$salary = null;

if ($conn) {
    // Check if employee_compensation table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Get current salary from employee_compensation table (monthly)
        $stmt = $conn->prepare("SELECT basic_salary_monthly FROM employee_compensation WHERE employee_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $employeeId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $salary = $row['basic_salary_monthly'] ?? 0.00;
            }
            $stmt->close();
        }
    }
    
    // If no compensation record found, check the latest salary adjustment
    if ($salary === null || $salary == 0) {
        $checkAdjustTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
        if ($checkAdjustTable && $checkAdjustTable->num_rows > 0) {
            $stmt = $conn->prepare("SELECT new_salary FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $employeeId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $salary = $row['new_salary'] ?? 0.00;
                }
                $stmt->close();
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'salary' => $salary !== null ? number_format($salary, 2, '.', '') : '0.00'
]);
