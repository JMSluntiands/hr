<?php
include '../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'] ?? '';

    if (empty($job_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Job ID missing']);
        exit;
    }

    // ✅ Update status instead of permanent delete
    $sql = "UPDATE jobs SET job_status = 'Deleted' WHERE job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job temporarily deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete job']);
    }
}
