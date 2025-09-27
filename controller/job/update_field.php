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

$allowed = ['staff_id', 'checker_id', 'job_status']; // ✅ limit para safe

if ($job_id <= 0 || !in_array($field, $allowed)) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
  exit;
}

// Dahil lahat ay string, parehong "si" lang
$stmt = $conn->prepare("UPDATE jobs SET $field = ? WHERE job_id = ?");
$stmt->bind_param("si", $value, $job_id);

if ($stmt->execute()) {
  echo json_encode([
    'status' => 'success',
    'message' => ucfirst(str_replace("_", " ", $field)) . " updated"
  ]);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
