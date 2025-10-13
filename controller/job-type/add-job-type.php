<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_code      = trim($_POST['client_code'] ?? '');
    $job_request_id   = trim($_POST['job_request_id'] ?? '');
    $job_request_type = trim($_POST['job_request_type'] ?? '');

    // Validation
    if (empty($client_code) || empty($job_request_id) || empty($job_request_type)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check duplicate job request
    $check = $conn->prepare("SELECT id FROM job_requests WHERE job_request_id = ?");
    $check->bind_param("s", $job_request_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Job Request ID already exists.']);
        exit;
    }

    // Insert into job_request
    $stmt = $conn->prepare("INSERT INTO job_requests (client_code, job_request_id, job_request_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $client_code, $job_request_id, $job_request_type);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Job request added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add job request.']);
    }

    $stmt->close();
    $conn->close();
}
?>
