<?php
include_once '../../../database/db.php';
session_start();

header("Content-Type: application/json");

$user_role   = $_SESSION['role'] ?? '';
$unique_id   = $_SESSION['unique_id'] ?? null;

$usersID = null;
if ($user_role !== "LUNTIAN" && $user_role !== "Staff" && $user_role !== "Checker") {
    // Client role → kunin client_code
    $stmt = $conn->prepare("SELECT client_code FROM clients WHERE LOWER(client_name) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $user_role);
    $stmt->execute();
    $stmt->bind_result($usersID);
    $stmt->fetch();
    $stmt->close();
}

// ✅ Conditional WHERE clause
$where = "";
if ($user_role === "LUNTIAN") {
    $where = "WHERE j.job_status = 'For Review'";
} elseif ($user_role === "Staff" && !empty($unique_id)) {
    $where = "WHERE j.staff_id = '$unique_id' AND j.job_status = 'For Review'";
} elseif ($user_role === "Checker" && !empty($unique_id)) {
    $where = "WHERE j.checker_id = '$unique_id' AND j.job_status = 'For Review'";
} elseif (!empty($usersID)) {
    $where = "WHERE j.client_code = '$usersID' AND j.job_status = 'For Review'";
} else {
    echo json_encode(["status" => "error", "message" => "Unauthorized or invalid client"]);
    exit;
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
        j.completion_date,
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
    $formattedDate = null;
    if (!empty($row['log_date'])) {
        $dateTime = new DateTime($row['log_date']);
        $formattedDate = $dateTime->format("F j, Y g:i A");
    }

    $last_update = null;
    if (!empty($row['completion_date'])) {
        $updateTime = new DateTime($row['completion_date']);
        $last_update = $updateTime->format("F j, Y g:i A");
    }

    $job_ref = $row['client_code'];
    if ($job_ref && strlen($job_ref) > 2) {
        $job_ref = substr($job_ref, 0, -2);
    }

    $data[] = [
        "job_id"              => $row['job_id'],
        "log_date"            => $formattedDate,
        "job_reference_no"    => $row['job_reference_no'],
        "start_ref"           => $job_ref,
        "client_reference_no" => $row['client_reference_no'],
        "priority"            => $row['priority'],
        "client_account_name" => $row['client_account_name'],
        "job_status"          => $row['job_status'],
        "client_code"         => $row['client_code'],
        "staff_name"          => $row['staff_name'],
        "checker_name"        => $row['checker_name'],
        "plan_complexity"     => $row['plan_complexity'],
        "completion_date"     => $row['completion_date'],
        "ncc_compliance"      => $row['ncc_compliance'],
        "last_update"         => $last_update,
    ];
}

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);

$stmt->close();
$conn->close();
?>
