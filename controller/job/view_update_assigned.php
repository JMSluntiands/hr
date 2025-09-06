<?php
include_once '../../database/db.php';
session_start();
header('Content-Type: application/json');

// ✅ Current user
$updated_by = $_SESSION['role'] ?? 'System';

// ✅ Inputs
$jobID      = $_POST['job_id']      ?? '';
$staff_id   = $_POST['staff_id']    ?? null;
$checker_id = $_POST['checker_id']  ?? null;
$createdAt  = $_POST['createdAt']   ?? '';

if (!$jobID) {
    echo json_encode(["success" => false, "message" => "Missing job_id"]);
    exit;
}

$updates = [];
$changes = [];

// ✅ Fetch old values para may comparison
$old = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT staff_id, checker_id FROM jobs WHERE job_id = '" . mysqli_real_escape_string($conn, $jobID) . "'"));

if ($staff_id !== null && $staff_id != $old['staff_id']) {
    $updates[] = "staff_id = '" . mysqli_real_escape_string($conn, $staff_id) . "'";
    $changes[] = "Staff updated from {$old['staff_id']} to {$staff_id}";
}
if ($checker_id !== null && $checker_id != $old['checker_id']) {
    $updates[] = "checker_id = '" . mysqli_real_escape_string($conn, $checker_id) . "'";
    $changes[] = "Checker updated from {$old['checker_id']} to {$checker_id}";
}

if (empty($updates)) {
    echo json_encode(["success" => false, "message" => "No changes made"]);
    exit;
}

$sql = "UPDATE jobs SET " . implode(", ", $updates) . " 
        WHERE job_id = '" . mysqli_real_escape_string($conn, $jobID) . "'";

if (mysqli_query($conn, $sql)) {
    // ✅ Insert activity log
    if (!empty($changes)) {
        $desc     = mysqli_real_escape_string($conn, implode("\n", $changes));
        $safeDate = $createdAt ?: date("Y-m-d H:i:s");


        $conn->query("
          INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
          VALUES ('" . mysqli_real_escape_string($conn, $jobID) . "', 'Update', '$desc', '$updated_by', '$safeDate')
        ");
    }

    echo json_encode(["success" => true, "message" => "Assigned staff/checker updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "DB Error: " . mysqli_error($conn)]);
}
?>
