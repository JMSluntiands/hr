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

    // Prevent marking as Completed if no uploaded files exist
    if ($status === "Completed") {
        $check = $conn->prepare("SELECT COUNT(*) as total FROM staff_uploaded_files WHERE job_id = ?");
        $check->bind_param("i", $job_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();

        if ($result['total'] == 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Cannot mark as Completed. No files uploaded for this job."
            ]);
            exit;
        }
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
