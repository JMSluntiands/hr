<?php
include '../../database/db.php';
header('Content-Type: application/json');

session_start();

$user_client = $_SESSION['role'] ?? '';

$usersID = '';
if ($user_client !== 'LUNTIAN') {
    $client = mysqli_query($conn, "SELECT * FROM clients WHERE client_name = '$user_client'");
    $fetch_client = mysqli_fetch_array($client);
    $usersID = $fetch_client['client_code'] ?? '';
}

// base SQL
$sql = "SELECT DISTINCT 
            j.job_id, 
            j.log_date, 
            j.client_code, 
            j.job_reference_no, 
            j.client_reference_no, 
            j.ncc_compliance, 
            ca.client_account_name, 
            jr.job_request_id, 
            jr.job_request_type, 
            j.job_type, 
            j.priority, 
            j.plan_complexity, 
            j.last_update, 
            j.job_status,
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
            ON j.job_request_id = jr.job_request_id
        WHERE j.job_status = 'Deleted'";

// idagdag lang filter kung hindi LUNTIAN
if ($user_client !== 'LUNTIAN' && $usersID !== '') {
    $sql .= " AND j.client_code = '$usersID'";
}

$sql .= " ORDER BY j.log_date DESC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);
?>
