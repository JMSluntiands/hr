<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
  exit;
}

$job_id = intval($_POST['job_id'] ?? 0);
$field  = trim($_POST['field'] ?? '');
$value  = trim($_POST['value'] ?? '');
$safeDate   = $_POST['safeDate'] ?? date("Y-m-d H:i:s");

$allowed = ['staff_id', 'checker_id', 'job_status'];

if ($job_id <= 0 || !in_array($field, $allowed)) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
  exit;
}

// Dahil lahat ay string, parehong "si" lang
$stmt = $conn->prepare("UPDATE jobs SET $field = ? WHERE job_id = ?");
$stmt->bind_param("si", $value, $job_id);

if ($stmt->execute()) {
  // 📝 Activity Log
  $updated_by = $_SESSION['username'] ?? ($_SESSION['role'] ?? 'system');

  // Kunin readable label
  $label = ucfirst(str_replace("_", " ", $field));
  $desc  = mysqli_real_escape_string($conn, "🔄 Updated $label to: $value");

  $conn->query("
    INSERT INTO activity_log (job_id, activity_type, activity_description, updated_by, activity_date)
    VALUES ($job_id, 'Update', '$desc', '$updated_by', '$safeDate')
  ");

  echo json_encode([
    'status' => 'success',
    'message' => "$label updated"
  ]);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
