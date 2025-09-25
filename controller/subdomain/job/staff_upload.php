<?php
session_start();
include '../../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id      = intval($_POST['job_id'] ?? 0);
    $comment     = trim($_POST['comment'] ?? '');
    $uploaded_by = $_SESSION['role'] ?? 'system';
    $createdAt   = $_POST['createdAt'] ?? ''; // 🕒 time galing JS

    if ($job_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Missing job ID.']);
        exit;
    }

    // 🔹 Kunin job_reference_no gamit get_result()
    $ref = '';
    $stmt = $conn->prepare("SELECT job_reference_no FROM jobs WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $ref = $row['job_reference_no'];
    }
    $stmt->close();

    if (empty($ref)) {
        echo json_encode([
            'success' => false,
            'message' => "Invalid job reference for job_id {$job_id}."
        ]);
        exit;
    }

    // 🔹 Check files
    if (!isset($_FILES['docs'])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
        exit;
    }

    $files = $_FILES['docs'];
    $uploadDir = "../../../document/$ref/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // ✅ Allow PDF + ZIP
    $allowedExt = ['pdf', 'zip'];
    $maxSize    = 10 * 1024 * 1024; // 10MB
    $uploadedFiles = [];

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;
        if ($files['size'][$i] > $maxSize) continue;

        $safeName   = $name; // sanitize
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
            $uploadedFiles[] = $safeName;

            // 🔹 Optional ZIP extraction
            /*
            if ($ext === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($targetPath) === true) {
                    $zip->extractTo($uploadDir);
                    $zip->close();
                }
            }
            */
        }
    }

    if (!empty($uploadedFiles)) {
        $jsonFiles = json_encode($uploadedFiles);
        $safeDate  = $createdAt ?: date("Y-m-d H:i:s");

        $stmt = $conn->prepare("
            INSERT INTO staff_uploaded_files (job_id, files_json, comment, uploaded_by, uploaded_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $job_id, $jsonFiles, $comment, $uploaded_by, $safeDate);

        if ($stmt->execute()) {
            echo json_encode([
                'success'   => true,
                'message'   => 'Files uploaded successfully',
                'files'     => $uploadedFiles,
                'reference' => $ref
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB insert failed: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No valid files uploaded.']);
    }
}
?>
