<?php
include_once '../../database/db.php';
session_start();
header('Content-Type: application/json');

// ✅ Current user
$updated_by = $_SESSION['role'] ?? 'System';

// ✅ Inputs
$jobID     = $_POST['job_id'] ?? '';
$newStatus = $_POST['job_status'] ?? '';
$createdAt = $_POST['createdAt'] ?? '';

if (!$jobID || !$newStatus) {
    echo json_encode(["success" => false, "message" => "Missing job_id or status"]);
    exit;
}

// ✅ Fetch old job
$jobIDEsc = mysqli_real_escape_string($conn, $jobID);
$old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT job_status FROM jobs WHERE job_id = '$jobIDEsc'"));

if (!$old) {
    echo json_encode(["success" => false, "message" => "Job not found"]);
    exit;
}

if ($old['job_status'] === $newStatus) {
    echo json_encode(["success" => false, "message" => "Status unchanged"]);
    exit;
}

// ✅ Safe date
$safeDate = $createdAt ?: date("Y-m-d H:i:s");

// 🚫 Restrict Completed status if no staff_uploaded_files
if ($newStatus === "Completed") {
    $check = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) as total FROM staff_uploaded_files WHERE job_id = '$jobIDEsc'"
    ));

    if ($check['total'] == 0) {
        echo json_encode([
            "success" => false,
            "message" => "Cannot mark as Completed. No uploaded files found for this job."
        ]);
        exit;
    }
}

// ✅ Update job status (+ completion_date if Completed)
if ($newStatus === "Completed") {
    $sql = "UPDATE jobs 
            SET job_status = '" . mysqli_real_escape_string($conn, $newStatus) . "', 
                completion_date = '" . mysqli_real_escape_string($conn, $safeDate) . "' 
            WHERE job_id = '$jobIDEsc'";
} else {
    $sql = "UPDATE jobs 
            SET job_status = '" . mysqli_real_escape_string($conn, $newStatus) . "' 
            WHERE job_id = '$jobIDEsc'";
}

if (mysqli_query($conn, $sql)) {
    // ✅ Log changes
    $changes = ["Status updated from {$old['job_status']} to {$newStatus}"];
    $desc = mysqli_real_escape_string($conn, implode("\n", $changes));

    $conn->query("
      INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
      VALUES ('$jobIDEsc', 'Update', '$desc', '$updated_by', '$safeDate')
    ");

    echo json_encode(["success" => true, "message" => "Status updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "DB Error: " . mysqli_error($conn)]);
}
?>
