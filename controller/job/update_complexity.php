<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

$jobID      = intval($_POST['job_id'] ?? 0);
$complexity = intval($_POST['complexity'] ?? 0);
$safeDate   = $_POST['safeDate'] ?? date("Y-m-d H:i:s");
$updated_by = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');

if ($jobID > 0 && $complexity >= 1 && $complexity <= 5) {
    $stmt = $conn->prepare("UPDATE jobs SET plan_complexity = ? WHERE job_id = ?");
    $stmt->bind_param("ii", $complexity, $jobID);

    if ($stmt->execute()) {
        // 📝 Activity log
        $desc = mysqli_real_escape_string($conn, "⭐ Updated Plan Complexity to: $complexity");

        $conn->query("
            INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
            VALUES ($jobID, 'Update', '$desc', '$updated_by', '$safeDate')
        ");

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB update failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
}
