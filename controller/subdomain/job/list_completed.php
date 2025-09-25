<?php
include_once '../../../database/db.php';
session_start();

$role = $_SESSION['role'] ?? '';
$unique_id = $_SESSION['unique_id'] ?? '';

header("Content-Type: application/json"); // JSON response

// Default condition (lahat)
$condition = "1=1";

// Build condition depende sa role
if ($role === 'LUNTIAN') {
    $condition = "1=1"; // all completed jobs
} elseif ($role === 'Staff') {
    $condition = "j.staff_id = '".$conn->real_escape_string($unique_id)."'";
} elseif ($role === 'Checker') {
    $condition = "j.checker_id = '".$conn->real_escape_string($unique_id)."'";
} else {
    // Client role
    $client = $conn->prepare("SELECT client_code FROM clients WHERE client_name = ?");
    $client->bind_param("s", $role);
    $client->execute();
    $client->bind_result($client_code);
    $client->fetch();
    $client->close();

    if (!empty($client_code)) {
        $condition = "j.client_code = '".$conn->real_escape_string($client_code)."'";
    } else {
        $condition = "0=1"; // no jobs if not found
    }
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
        cl.client_name,
        j.checker_id,
        jr.job_request_id, 
        jr.job_request_type, 
        j.job_type, 
        j.priority, 
        j.plan_complexity, 
        j.completion_date,
        j.last_update, 
        j.job_status,
        s.name AS staff_name, 
        c.name AS checker_name
    FROM jobs j
    LEFT JOIN staff s ON j.staff_id = s.staff_id
    LEFT JOIN checker c ON j.checker_id = c.checker_id
    LEFT JOIN clients cl ON j.client_code = cl.client_code
    LEFT JOIN client_accounts ca ON j.client_account_id = ca.client_account_id
    LEFT JOIN job_requests jr ON j.job_request_id = jr.job_request_id
    WHERE $condition 
      AND j.job_status = 'Completed'
    ORDER BY j.log_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Format log_date
    $formattedDate = "";
    if (!empty($row['log_date'])) {
        $dateTime = new DateTime($row['log_date']);
        $formattedDate = $dateTime->format("F j, Y g:i A");
    }

    // Format completion_date
    $last_update = "";
    if (!empty($row['completion_date'])) {
        $updateTime = new DateTime($row['completion_date']);
        $last_update = $updateTime->format("F j, Y g:i A");
    }

    // Clean job_ref (tanggalin last 2 chars sa client_code)
    $job_ref = $row['client_code'] ?? '';
    if (strlen($job_ref) > 2) {
        $job_ref = substr($job_ref, 0, -2);
    }

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
        "staff_name" => $row['staff_name'],
        "checker_name" => $row['checker_name'],
        "client_name" => $row['client_name'],
        "plan_complexity" => $row['plan_complexity'],
        "completion_date" => $row['completion_date'],
        "ncc_compliance" => $row['ncc_compliance'],
        "last_update" => $last_update
    ];
}

$response = ["data" => $data, "count" => count($data)];
echo json_encode($response);

$stmt->close();
$conn->close();
?>
