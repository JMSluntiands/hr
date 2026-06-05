<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include '../../database/db.php';

$employees = [];

if ($conn) {
    $query = "SELECT id, employee_id, full_name 
              FROM employees 
              WHERE status = 'Active' OR status IS NULL
              ORDER BY full_name ASC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'id' => $row['id'],
                'employee_id' => $row['employee_id'],
                'full_name' => $row['full_name']
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $employees
]);
