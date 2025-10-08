<?php
include_once '../../database/db.php';
session_start();

$user_role   = $_SESSION['role'] ?? '';
$user_client = $_SESSION['client_name'] ?? ''; // dapat naka-store client name sa session

// Kunin client_code kung hindi LUNTIAN
$usersID = null;
if ($user_role !== "LUNTIAN") {
    $client = mysqli_query($conn, "SELECT * FROM clients WHERE client_name = '$user_client'");
    $fetch_client = mysqli_fetch_array($client);
    $usersID = $fetch_client['client_code'] ?? null;
}

header("Content-Type: application/json");

// ✅ Conditional WHERE clause
$where = "";
if ($user_role === "LUNTIAN") {
    $where = "WHERE j.job_status IN ('For Review')";
} else {
    $where = "WHERE j.client_code = '$usersID' AND j.job_status IN ('For Review')";
}

$sql = "
    SELECT DISTINCT 
        j.job_id, 
        j.log_date, 
        j.client_code, 
        j.job_reference_no, 
        j.client_reference_no, 
        j.ncc_compliance, 
        ca.client_account_name, 
        j.staff_id,
        j.checker_id,
        jr.job_request_id, 
        jr.job_request_type, 
        j.job_type, 
        j.priority, 
        j.plan_complexity, 
        j.ncc_compliance, 
        j.completion_date,
        j.notes,
        j.last_update, 
        j.job_status,
        s.name AS staff_name, 
        c.name AS checker_name
    FROM jobs j
    LEFT JOIN staff s ON j.staff_id = s.staff_id
    LEFT JOIN checker c ON j.checker_id = c.checker_id
    LEFT JOIN client_accounts ca ON j.client_account_id = ca.client_account_id
    LEFT JOIN job_requests jr ON j.job_request_id = jr.job_request_id
    $where
    ORDER BY j.log_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $dateTime = new DateTime($row['log_date']);
    $formattedDate = $dateTime->format("F j, Y g:i A"); 

    $last_update = null;
    if (!empty($row['completion_date'])) {
        $updateTime = new DateTime($row['completion_date']);
        $last_update = $updateTime->format("F j, Y g:i A"); 
    }

    $job_ref = $row['client_code'];
    $job_ref = substr($job_ref, 0, -2);

    $data[] = [
        "job_id" => $row['job_id'],
        "log_date" => $formattedDate,
        "job_reference_no" => $row['job_reference_no'],
        "start_ref" => $job_ref,
        "client_reference_no" => $row['client_reference_no'],
        "priority" => $row['priority'],
        "client_account_name" => $row['client_account_name'],
        "job_status" => $row['job_status'],
        "client_code" => $row['client_code'],
        "staff_id" => $row['staff_id'],
        "staff_name" => $row['staff_name'],
        "checker_id" => $row['checker_id'],
        "checker_name" => $row['checker_name'],
        "plan_complexity" => $row['plan_complexity'],
        "completion_date" => $row['completion_date'],
        "ncc_compliance" => $row['ncc_compliance'],
        "last_update" => $last_update,
        "notes" => $row['notes']
    ];
}

// staff list
$staffList = [];
$resStaff = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY staff_id");
while ($s = mysqli_fetch_assoc($resStaff)) {
  $staffList[] = $s;
}

// checker list
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
