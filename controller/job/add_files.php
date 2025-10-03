<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref    = $_POST['ref'] ?? '';
    $column = $_POST['column'] ?? '';
    $files  = $_FILES['new_files'] ?? null;

    $updated_by = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');
    $safeDate = $_POST['safeDate'] ?? date("Y-m-d H:i:s");

    if (!$ref || !$column || !$files) {
        echo json_encode(['status'=>'error','message'=>'Missing parameters']);
        exit;
    }

    if (!in_array($column, ['upload_files','upload_project_files'])) {
        echo json_encode(['status'=>'error','message'=>'Invalid column']);
        exit;
    }

    // Hanapin job
    $stmt = $conn->prepare("SELECT job_id, $column FROM jobs WHERE job_reference_no = ?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $res = $stmt->get_result();
    $job = $res->fetch_assoc();

    if (!$job) {
        echo json_encode(['status'=>'error','message'=>'Job not found']);
        exit;
    }

    $job_id   = $job['job_id'];
    $existing = json_decode($job[$column], true) ?? [];

    $uploadDir = "../../document/" . $ref . "/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFiles = [];
    foreach ($files['name'] as $i => $name) {
        $tmpPath = $files['tmp_name'][$i];
        $newName = basename($name);
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($tmpPath, $targetPath)) {
            $existing[] = $newName;
            $newFiles[] = $newName;
        }
    }

    $newJson = json_encode(array_values($existing));

    $stmt2 = $conn->prepare("UPDATE jobs SET $column = ? WHERE job_id = ?");
    $stmt2->bind_param("si", $newJson, $job_id);
    $stmt2->execute();

    // 📝 Step 3: activity log
    if (!empty($newFiles)) {
        $label = ($column === 'upload_files') ? 'Plan' : 'Document';
        $desc  = mysqli_real_escape_string($conn, "📂 Added $label(s): " . implode(', ', $newFiles));
        $conn->query("
            INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
            VALUES ($job_id, 'Upload', '$desc', '$updated_by', '$safeDate')
        ");
    }

    echo json_encode([
        'status'=>'success',
        'message'=>'Files added successfully',
        'files'=>$newFiles
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
}
