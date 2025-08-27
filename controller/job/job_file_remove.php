<?php
session_start();
include '../../database/db.php';
header('Content-Type: application/json');

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid method']);
    exit;
  }

  $job_id     = (int)($_POST['job_id'] ?? 0);
  $type       = $_POST['type'] ?? ''; // 'plans' or 'docs'
  $file       = $_POST['file'] ?? '';
  $updated_by = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');

  if (!$job_id || !$type || !$file) {
    echo json_encode(['status'=>'error','message'=>'Missing parameters']);
    exit;
  }
  if (!in_array($type, ['plans','docs'], true)) {
    echo json_encode(['status'=>'error','message'=>'Invalid type']);
    exit;
  }

  // Load job
  $stmt = $conn->prepare("SELECT job_reference_no, upload_files, upload_project_files FROM jobs WHERE job_id=?");
  $stmt->bind_param("i", $job_id);
  $stmt->execute();
  $job = $stmt->get_result()->fetch_assoc();
  if (!$job) {
    echo json_encode(['status'=>'error','message'=>'Job not found']);
    exit;
  }

  // Get current list
  $list = $type === 'plans'
        ? json_decode($job['upload_files'] ?? '[]', true)
        : json_decode($job['upload_project_files'] ?? '[]', true);
  if (!is_array($list)) $list = [];

  // Find and remove
  $idx = array_search($file, $list, true);
  if ($idx === false) {
    echo json_encode(['status'=>'error','message'=>'File not found in record']);
    exit;
  }
  array_splice($list, $idx, 1);

  // Save back JSON
  $json = json_encode(array_values($list));
  $column = $type === 'plans' ? 'upload_files' : 'upload_project_files';
  $sql = "UPDATE jobs SET $column = ?, last_update = NOW() WHERE job_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $json, $job_id);
  $stmt->execute();

  // Delete physical file
  $baseDir   = realpath(__DIR__ . '/../../') . '/document';
  $filePath  = $baseDir . '/' . $job['job_reference_no'] . '/' . $file;
  if (is_file($filePath)) {
    @unlink($filePath);
  }

  // Log activity
  $desc = "Removed " . ($type === 'plans' ? 'Plan' : 'Document') . ": " . $file;
  $stmt = $conn->prepare("
    INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by)
    VALUES (?, 'File Remove', ?, ?)
  ");
  $stmt->bind_param("iss", $job_id, $desc, $updated_by);
  $stmt->execute();

  echo json_encode(['status'=>'success','message'=>'File removed successfully']);
} catch (Throwable $e) {
  echo json_encode([
    'status'=>'error',
    'message'=>'Server error',
    'debug'=>$e->getMessage()
  ]);
}
