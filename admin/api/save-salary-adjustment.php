<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../../database/db.php';

$employeeId = $_POST['employee_id'] ?? null;
$previousSalary = $_POST['previous_salary'] ?? null;
$newSalary = $_POST['new_salary'] ?? null;
$reason = $_POST['reason'] ?? null;
$dateApproved = $_POST['date_approved'] ?? null;
$approvedBy = $_POST['approved_by'] ?? $_SESSION['name'] ?? 'Admin';

// Validation
if (!$employeeId || !$previousSalary || !$newSalary || !$reason || !$dateApproved) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Validate reason
$validReasons = ['Promotion', 'Annual Increase', 'Adjustment', 'Other'];
if (!in_array($reason, $validReasons)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid reason']);
    exit;
}

if ($conn) {
    // Ensure table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if (!$checkTable || $checkTable->num_rows == 0) {
        // Create table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `previous_salary` decimal(10,2) NOT NULL,
            `new_salary` decimal(10,2) NOT NULL,
            `reason` enum('Promotion','Annual Increase','Adjustment','Other') DEFAULT 'Adjustment',
            `approved_by` varchar(255) DEFAULT NULL,
            `date_approved` date NOT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee_id` (`employee_id`),
            KEY `idx_date_approved` (`date_approved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($createTable);
    }

    // Insert salary adjustment
    $stmt = $conn->prepare("INSERT INTO employee_salary_adjustments (employee_id, previous_salary, new_salary, reason, approved_by, date_approved) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param('iddsss', $employeeId, $previousSalary, $newSalary, $reason, $approvedBy, $dateApproved);
        
        if ($stmt->execute()) {
            // Update employee_compensation table with new salary
            $checkCompTable = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
            if ($checkCompTable && $checkCompTable->num_rows > 0) {
                $updateStmt = $conn->prepare("UPDATE employee_compensation SET basic_salary_monthly = ?, updated_at = NOW() WHERE employee_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param('di', $newSalary, $employeeId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Salary adjustment saved successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to save salary adjustment: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
}
