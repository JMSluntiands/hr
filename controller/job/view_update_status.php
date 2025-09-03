<?php
include_once '../../database/db.php';
session_start();
header('Content-Type: application/json');

// ✅ Current user
$updated_by = $_SESSION['role'] ?? 'System';

// ✅ Inputs
$jobID = $_POST['job_id'] ?? '';
$newStatus = $_POST['job_status'] ?? '';

if (!$jobID || !$newStatus) {
    echo json_encode(["success" => false, "message" => "Missing job_id or status"]);
    exit;
}

// ✅ Fetch old value
$old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT job_status FROM jobs WHERE job_id = '" . mysqli_real_escape_string($conn, $jobID) . "'"));

if ($old['job_status'] === $newStatus) {
    echo json_encode(["success" => false, "message" => "Status unchanged"]);
    exit;
}

// ✅ Update
$sql = "UPDATE jobs SET job_status = '" . mysqli_real_escape_string($conn, $newStatus) . "' WHERE job_id = '" . mysqli_real_escape_string($conn, $jobID) . "'";

if (mysqli_query($conn, $sql)) {
    // ✅ Log
    $changes = ["Status updated from {$old['job_status']} to {$newStatus}"];
    $desc = mysqli_real_escape_string($conn, implode("\n", $changes));
    $conn->query("
      INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by)
      VALUES ($jobID, 'Update', '$desc', '$updated_by')
    ");

    echo json_encode(["success" => true, "message" => "Status updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "DB Error: " . mysqli_error($conn)]);
}
?>
