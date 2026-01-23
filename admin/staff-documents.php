<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../database/db.php';

header('Content-Type: application/json');

$employeeId = (int)($_GET['id'] ?? 0);

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

$documents = [];

if ($conn) {
    // Check if employee_document_uploads table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $stmt = $conn->prepare("SELECT id, document_type, file_path, status, created_at, updated_at 
                                FROM employee_document_uploads 
                                WHERE employee_id = ? 
                                ORDER BY document_type, created_at DESC");
        if ($stmt) {
            $stmt->bind_param('i', $employeeId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $documents[] = $row;
            }
            
            $stmt->close();
        }
    }
}

echo json_encode([
    'success' => true,
    'documents' => $documents
]);
