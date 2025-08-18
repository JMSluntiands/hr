<?php
  include_once '../../database/db.php';

  header("Content-Type: application/json"); // Set response type

  $sql = "SELECT DISTINCT 
              j.job_id, 
              j.log_date, 
              j.client_code, 
              j.job_reference_no, 
              j.client_reference_no, 
              j.ncc_compliance, 
              ca.client_account_name, 
              ca.client_account_address, 
              jr.job_request_id, 
              jr.job_request_type, 
              j.job_type, 
              j.priority, 
              j.plan_complexity, 
              s.name AS staff_name, 
              c.name AS checker_name
          FROM jobs j
          LEFT JOIN staff s 
              ON j.staff_id = s.staff_id
          LEFT JOIN checker c 
              ON j.checker_id = c.checker_id
          LEFT JOIN client_accounts ca 
              ON j.client_account_id = ca.client_account_id
          LEFT JOIN job_requests jr 
              ON j.job_request_id = jr.job_request_id;
";
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
      "staff_name" => $row['staff_name'],
      "checker_name" => $row['checker_name'],
      "plan_complexity" => $row['plan_complexity'],
      "client_code" => $row['client_code'],
    ];
  }

  $response = ["data" => $data, "count" => count($data)];

  echo json_encode($response); // Return JSON

  $stmt->close();
  $conn->close();
?>
