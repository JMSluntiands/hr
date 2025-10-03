<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref      = $_POST['ref'] ?? '';
    $filename = $_POST['filename'] ?? '';
    $updated_by = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');
    $safeDate = $_POST['safeDate'] ?? date("Y-m-d H:i:s");

    if (!$ref || !$filename) {
        echo json_encode(['status'=>'error','message'=>'Missing parameters']);
        exit;
    }

    // Hanapin job gamit ref
    $stmt = $conn->prepare("SELECT job_id, upload_files FROM jobs WHERE job_reference_no = ?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $res = $stmt->get_result();
    $job = $res->fetch_assoc();

    if (!$job) {
        echo json_encode(['status'=>'error','message'=>'Job not found']);
        exit;
    }

    $job_id       = $job['job_id'];
    $upload_files = json_decode($job['upload_files'], true) ?? [];

    // Path ng file
    $filePath = "../../document/" . $ref . "/" . $filename;

    // Step 1: delete file
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            echo json_encode(['status'=>'error','message'=>'Failed to delete file']);
            exit;
        }
    }

    // Step 2: update DB (alisin yung filename sa array)
    $newFiles = array_values(array_filter($upload_files, function($f) use ($filename) {
        return $f !== $filename;
    }));

    $newJson = empty($newFiles) ? null : json_encode($newFiles);

    $stmt2 = $conn->prepare("UPDATE jobs SET upload_files = ? WHERE job_id = ?");
    $stmt2->bind_param("si", $newJson, $job_id);
    $stmt2->execute();

    // Step 3: activity log
    $desc = mysqli_real_escape_string($conn, "🗑️ Deleted Plan: $filename");
    $conn->query("
        INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
        VALUES ($job_id, 'Delete', '$desc', '$updated_by', '$safeDate')
    ");

    echo json_encode(['status'=>'success','message'=>'Plan deleted successfully']);
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
}
