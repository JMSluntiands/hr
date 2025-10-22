<?php
include '../../database/db.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM announcement ORDER BY id DESC";
    $result = $conn->query($sql);

    $announcements = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => $announcements
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
