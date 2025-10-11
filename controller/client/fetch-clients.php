<?php
include '../../database/db.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, client_code, client_name, client_email, NOW() as log_date FROM clients WHERE client_name != 'LUNTIAN' ORDER BY id DESC";
    $result = $conn->query($sql);

    $clients = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $clients
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
