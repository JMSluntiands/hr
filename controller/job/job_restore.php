<?php
include '../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'] ?? '';

    if (empty($job_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Job ID missing']);
        exit;
    }

    // ✅ Restore job (set status to Allocated o kung anong gusto mong default status)
    $sql = "UPDATE jobs SET job_status = 'Allocated' WHERE job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job restored successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to restore job']);
    }
}
