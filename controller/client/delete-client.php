<?php
include '../../database/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit;
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid client ID."]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Client deleted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Client not found or already deleted."]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
