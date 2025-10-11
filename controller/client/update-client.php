<?php
include '../../database/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit;
}

$id           = intval($_POST['id'] ?? 0);
$client_code  = trim($_POST['client_code'] ?? '');
$client_name  = trim($_POST['client_name'] ?? '');
$client_email = trim($_POST['client_email'] ?? '');

if (!$id || !$client_code || !$client_name || !$client_email) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE clients 
        SET client_code = ?, client_name = ?, client_email = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $client_code, $client_name, $client_email, $id);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo json_encode(["status" => "success", "message" => "Client updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "No changes were made."]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
