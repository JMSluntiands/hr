<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id   = intval($_POST['job_id'] ?? 0);
    $comment  = trim($_POST['comment'] ?? '');
    $uploaded_by = $_SESSION['role'] ?? 'system';

    if (!isset($_FILES['docs'])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
        exit;
    }

    $files = $_FILES['docs'];
    $uploadDir = "../../document/$job_id/"; 
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $allowedExt = ['pdf'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $uploadedFiles = [];

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;
        if ($files['size'][$i] > $maxSize) continue;

        $safeName = uniqid("doc_", true) . "." . $ext;
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
            $uploadedFiles[] = $safeName;
        }
    }

    if (!empty($uploadedFiles)) {
        $jsonFiles = json_encode($uploadedFiles);

        $stmt = $conn->prepare("
            INSERT INTO staff_uploaded_files (job_id, files_json, comment, uploaded_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $job_id, $jsonFiles, $comment, $uploaded_by);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'files'   => $uploadedFiles
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB insert failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No valid files uploaded.']);
    }
}
?>
