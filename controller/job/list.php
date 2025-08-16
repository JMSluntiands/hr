<?php
  include_once '../../database/db.php';

  header("Content-Type: application/json"); // Set response type

  $sql = "SELECT * FROM jobs ORDER BY job_id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      "job_id" => $row['job_id'],
      "log_date" => $row['log_date'],
      "job_reference_no" => $row['job_reference_no'],
      "client_reference_no" => $row['client_reference_no'],
      "priority" => $row['priority'],
      "client_account" => $row['client_account'],
      "job_address" => $row['job_address'],
      "job_status" => $row['job_status'],
      "client_code" => $row['client_code'],
      "client_code" => $row['client_code'],
      "client_code" => $row['client_code'],
      "plan_complexity" => $row['plan_complexity'],
      "client_code" => $row['client_code'],
    ];
  }

  $response = ["data" => $data, "count" => count($data)];

  echo json_encode($response); // Return JSON

  $stmt->close();
  $conn->close();
?>
