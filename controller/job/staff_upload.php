<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id     = intval($_POST['job_id'] ?? 0);
    $comment    = trim($_POST['comment'] ?? '');
    $uploaded_by = $_SESSION['role'] ?? 'system';
    $createdAt  = $_POST['createdAt'] ?? ''; // 🕒 time galing JS

    // 🔹 Kunin muna job_reference_no
    $ref = '';
    $stmt = $conn->prepare("SELECT job_reference_no FROM jobs WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $stmt->bind_result($ref);
    $stmt->fetch();
    $stmt->close();

    if (empty($ref)) {
        echo json_encode(['success' => false, 'message' => 'Invalid job reference.']);
        exit;
    }

    if (!isset($_FILES['docs'])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
        exit;
    }

    $files = $_FILES['docs'];
    $uploadDir = "../../document/$ref/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // ✅ PDF + ZIP allowed
    $allowedExt = ['pdf', 'zip'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    $uploadedFiles = [];

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;
        if ($files['size'][$i] > $maxSize) continue;

        $targetPath = $uploadDir . $name;

        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
            $uploadedFiles[] = $name;

            // 🔹 Kung gusto i-extract ZIP, uncomment mo ito:
            /*
            if ($ext === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($targetPath) === true) {
                    $zip->extractTo($uploadDir); // extract sa job folder
                    $zip->close();
                }
            }
            */
        }
    }

    if (!empty($uploadedFiles)) {
        $jsonFiles = json_encode($uploadedFiles);

        // 🕒 fallback kung walang galing JS
        $safeDate = $createdAt ?: date("Y-m-d H:i:s");

        $stmt = $conn->prepare("
            INSERT INTO staff_uploaded_files (job_id, files_json, comment, uploaded_by, uploaded_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $job_id, $jsonFiles, $comment, $uploaded_by, $safeDate);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'files'   => $uploadedFiles,
                'reference' => $ref
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB insert failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No valid files uploaded.']);
    }
}
?>
