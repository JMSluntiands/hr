<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_id      = trim($_POST['original_id'] ?? '');
    $client_code      = trim($_POST['client_code'] ?? '');
    $job_request_id   = trim($_POST['job_request_id'] ?? '');
    $job_request_type = trim($_POST['job_request_type'] ?? '');

    if (empty($client_code) || empty($job_request_id) || empty($job_request_type)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if new job_request_id already exists (and is not the same record)
    $check = $conn->prepare("SELECT id FROM job_requests WHERE job_request_id = ? AND job_request_id != ?");
    $check->bind_param("ss", $job_request_id, $original_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Job Request ID already exists.']);
        exit;
    }

    // Update record
    $stmt = $conn->prepare("
        UPDATE job_requests
        SET client_code = ?, job_request_id = ?, job_request_type = ?
        WHERE job_request_id = ?
    ");
    $stmt->bind_param("ssss", $client_code, $job_request_id, $job_request_type, $original_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job request updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update job request.']);
    }

    $stmt->close();
    $conn->close();
}
?>
