<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_code  = trim($_POST['client_code'] ?? '');
    $client_name  = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');

    if (empty($client_code) || empty($client_name) || empty($client_email)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if code already exists
    $check = $conn->prepare("SELECT id FROM clients WHERE client_code = ?");
    $check->bind_param("s", $client_code);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Client code already exists.']);
        exit;
    }

    // Insert client
    $stmt = $conn->prepare("INSERT INTO clients (client_code, client_name, client_email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $client_code, $client_name, $client_email);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Client added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
?>
