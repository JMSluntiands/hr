<?php
include_once '../../database/db.php';
session_start();

$user_client = $_SESSION['role'] ?? '';
$client = mysqli_query($conn, "SELECT * FROM clients WHERE client_name = '$user_client'");
$fetch_client = mysqli_fetch_array($client);
$usersID = $fetch_client['client_code'] ?? '';

header("Content-Type: application/json");

$luntian = ($user_client !== 'LUNTIAN') ? "j.client_code = '".$usersID."' AND" : '';

$sql = "SELECT DISTINCT 
            j.job_id, j.log_date, j.client_code, j.job_reference_no, 
            j.client_reference_no, j.ncc_compliance, ca.client_account_name, 
            j.staff_id, cl.client_name, j.checker_id, j.job_type, 
            j.priority, j.plan_complexity, j.completion_date,
            j.notes, j.last_update, j.job_status,
            s.name AS staff_name, c.name AS checker_name
        FROM jobs j
        LEFT JOIN staff s ON j.staff_id = s.staff_id
        LEFT JOIN checker c ON j.checker_id = c.checker_id
        LEFT JOIN clients cl ON j.client_code = cl.client_code
        LEFT JOIN client_accounts ca ON j.client_account_id = ca.client_account_id
        WHERE $luntian j.job_status IN ('Completed')
        ORDER BY j.log_date DESC";

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
    "client_account_name" => $row['client_account_name'],
    "job_status" => $row['job_status'],
    "client_code" => $row['client_code'],
    "staff_id" => $row['staff_id'],
    "checker_id" => $row['checker_id'],
    "job_type" => $row['job_type'],
    "client_name" => $row['client_name'],
    "complexity" => $row['plan_complexity'],
    "completion_date" => $row['completion_date'],
    "ncc_compliance" => $row['ncc_compliance'],
    "last_update" => $row['last_update'],
    "notes" => $row['notes'],
  ];
}

// 🔹 staffList
$staffList = [];
$resStaff = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY staff_id");
while ($s = mysqli_fetch_assoc($resStaff)) {
  $staffList[] = $s;
}

// 🔹 checkerList
$checkerList = [];
$resChecker = mysqli_query($conn, "SELECT checker_id, name FROM checker ORDER BY checker_id");
while ($c = mysqli_fetch_assoc($resChecker)) {
  $checkerList[] = $c;
}

$response = [
  "data" => $data,
  "count" => count($data),
  "staffList" => $staffList,
  "checkerList" => $checkerList
];

echo json_encode($response);

$stmt->close();
$conn->close();
