<?php
  include_once '../../database/db.php';

  session_start();
  $user_client = $_SESSION['role'] ?? '';

  $client = mysqli_query($conn, "SELECT * FROM clients WHERE client_name = '$user_client'");
  $fetch_client = mysqli_fetch_array($client);

  $usersID = $fetch_client['client_code'];

  header("Content-Type: application/json"); // Set response type

  $sql = "SELECT DISTINCT 
              j.job_id, 
              j.log_date, 
              j.client_code, 
              j.job_reference_no, 
              j.client_reference_no, 
              j.ncc_compliance, 
              ca.client_account_name, 
              j.staff_id,
              cl.client_name,
              j.checker_id,
              jr.job_request_id, 
              jr.job_request_type, 
              j.job_type, 
              j.priority, 
              j.plan_complexity, 
              j.ncc_compliance, 
              j.completion_date,
              j.last_update, 
              j.job_status,
              s.name AS staff_name, 
              c.name AS checker_name
          FROM jobs j
          LEFT JOIN staff s 
              ON j.staff_id = s.staff_id
          LEFT JOIN checker c 
              ON j.checker_id = c.checker_id
          LEFT JOIN clients cl
              ON j.client_code = cl.client_code
          LEFT JOIN client_accounts ca 
              ON j.client_account_id = ca.client_account_id
          LEFT JOIN job_requests jr 
              ON j.job_request_id = jr.job_request_id
          -- WHERE j.client_code = '$usersID' AND 
          WHERE j.job_status IN ('Completed')
";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  while ($row = $result->fetch_assoc()) {
  $dateTime = new DateTime($row['log_date']);
  $formattedDate = $dateTime->format("F j, Y g:i A"); 
  $updateTime = new DateTime($row['completion_date']);
  $last_update = $updateTime->format("F j, Y g:i A"); 

  $job_ref = $row['client_code'];
  $job_ref = substr($job_ref, 0, -2); // tanggalin last 2 characters

  // Example output: August 18, 2025 10:34 AM

  $data[] = [
    "job_id" => $row['job_id'],
    "log_date" => $formattedDate,  // already formatted
    "job_reference_no" => $row['job_reference_no'],
    "start_ref" => $job_ref,
    "client_reference_no" => $row['client_reference_no'],
    "priority" => $row['priority'],
    "client_account_name" => $row['client_account_name'], // ✅ ayusin name
    "job_status" => $row['job_status'],
    "client_code" => $row['client_code'],
    "staff_name" => $row['staff_id'],
    "checker_name" => $row['checker_id'],
    "plan_complexity" => $row['plan_complexity'],
    "completion_date" => $row['completion_date'],
    "ncc_compliance" => $row['ncc_compliance'],
    "last_update" => $last_update,
    "completion_date" => $row['completion_date'],
    "priority" => $row['priority'],
  ];
}


  $response = ["data" => $data, "count" => count($data)];

  echo json_encode($response); // Return JSON

  $stmt->close();
  $conn->close();
?>
