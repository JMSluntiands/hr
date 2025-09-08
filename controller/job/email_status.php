<?php
include '../../database/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = intval($_POST['job_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($job_id <= 0 || $status === '') {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE jobs SET job_status = ? WHERE job_id = ?");
    $stmt->bind_param("si", $status, $job_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    $stmt->close();
    $conn->close();
}
?>
