<?php
session_start();
include '../../database/db.php';

$jobID = intval($_POST['job_id'] ?? 0);
$complexity = intval($_POST['complexity'] ?? 0);

if ($jobID > 0 && $complexity >= 1 && $complexity <= 5) {
  $stmt = $conn->prepare("UPDATE jobs SET plan_complexity = ? WHERE job_id = ?");
  $stmt->bind_param("ii", $complexity, $jobID);

  if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
  } else {
    echo json_encode(["status" => "error", "message" => "DB update failed"]);
  }
} else {
  echo json_encode(["status" => "error", "message" => "Invalid input"]);
}
