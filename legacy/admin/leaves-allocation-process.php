<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Include database connection
include '../database/db.php';
include 'include/activity-logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $leaveType = trim($_POST['leave_type'] ?? '');
    $totalDays = (int)($_POST['total_days'] ?? 0);
    $year = (int)($_POST['year'] ?? date('Y'));
    
    if ($employeeId > 0 && !empty($leaveType) && $totalDays > 0 && $conn) {
        // Check if allocation already exists for this employee, leave type, and year
        $checkStmt = $conn->prepare("SELECT id, used_days FROM leave_allocations WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $checkStmt->bind_param("isi", $employeeId, $leaveType, $year);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // Get employee name for logging
        $empStmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $empStmt->bind_param("i", $employeeId);
        $empStmt->execute();
        $empResult = $empStmt->get_result();
        $empName = 'Unknown';
        if ($empResult && $empRow = $empResult->fetch_assoc()) {
            $empName = $empRow['full_name'];
        }
        $empStmt->close();
        
        if ($checkResult->num_rows > 0) {
            // Update existing allocation
            $existing = $checkResult->fetch_assoc();
            $usedDays = (int)$existing['used_days'];
            $remainingDays = max(0, $totalDays - $usedDays);
            
            $updateStmt = $conn->prepare("UPDATE leave_allocations SET total_days = ?, remaining_days = ? WHERE id = ?");
            $updateStmt->bind_param("iii", $totalDays, $remainingDays, $existing['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Log activity
            logActivity($conn, 'Update Leave Allocation', 'Leave Allocation', $existing['id'], "Updated $leaveType allocation for $empName: $totalDays days (Year: $year)");
            
            $_SESSION['success_message'] = 'Leave allocation updated successfully!';
        } else {
            // Insert new allocation
            $remainingDays = $totalDays;
            $insertStmt = $conn->prepare("INSERT INTO leave_allocations (employee_id, leave_type, total_days, used_days, remaining_days, year) VALUES (?, ?, ?, 0, ?, ?)");
            $insertStmt->bind_param("isiii", $employeeId, $leaveType, $totalDays, $remainingDays, $year);
            $insertStmt->execute();
            $allocationId = $conn->insert_id;
            $insertStmt->close();
            
            // Log activity
            logActivity($conn, 'Allocate Leave', 'Leave Allocation', $allocationId, "Allocated $leaveType for $empName: $totalDays days (Year: $year)");
            
            $_SESSION['success_message'] = 'Leave allocation added successfully!';
        }
        
        $checkStmt->close();
    } else {
        $_SESSION['error_message'] = 'Please fill in all required fields correctly.';
    }
}

header('Location: leaves-allocation.php');
exit;
?>
