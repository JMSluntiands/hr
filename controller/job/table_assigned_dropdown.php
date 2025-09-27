<?php
include '../../database/db.php';
header('Content-Type: application/json');

$response = [
  "staff" => [],
  "checker" => []
];

// Staff
$staffRes = $conn->query("SELECT staff_id, name FROM staff ORDER BY name ASC");
while ($row = $staffRes->fetch_assoc()) {
  $response["staff"][] = $row;
}

// Checker
$checkerRes = $conn->query("SELECT checker_id, name FROM checker ORDER BY name ASC");
while ($row = $checkerRes->fetch_assoc()) {
  $response["checker"][] = $row;
}

echo json_encode($response);
$conn->close();
