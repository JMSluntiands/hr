<?php
include '../../../database/db.php';
header('Content-Type: application/json');

session_start();

$role = $_SESSION['role'] ?? '';
$unique_id = $_SESSION['unique_id'] ?? '';

$condition = "1=1"; // default condition

if ($role === 'LUNTIAN') {
    $condition = "1=1"; // lahat ng jobs
} elseif ($role === 'Staff') {
    $condition = "j.staff_id = '".$conn->real_escape_string($unique_id)."'";
} elseif ($role === 'Checker') {
    $condition = "j.checker_id = '".$conn->real_escape_string($unique_id)."'";
} else {
    // Client role → hanapin client_code
    $client = $conn->prepare("SELECT client_code FROM clients WHERE client_name = ?");
    $client->bind_param("s", $role);
    $client->execute();
    $client->bind_result($client_code);
    $client->fetch();
    $client->close();

    if (!empty($client_code)) {
        $condition = "j.client_code = '".$conn->real_escape_string($client_code)."'";
    } else {
        $condition = "0=1"; // walang job
    }
}

$sql = "
    SELECT DISTINCT 
        j.job_id, 
        j.log_date, 
        j.staff_id, 
        j.checker_id,
        j.job_reference_no,
        ca.client_email,
        f.files_json,
        f.uploaded_at,
        f.uploaded_by
    FROM jobs j
    LEFT JOIN clients ca 
        ON j.client_code = ca.client_code
    LEFT JOIN staff_uploaded_files f 
        ON f.job_id = j.job_id
       AND f.uploaded_at = (
            SELECT MAX(f2.uploaded_at) 
            FROM staff_uploaded_files f2 
            WHERE f2.job_id = j.job_id
       )
    WHERE j.job_status = 'For Email Confirmation'
      AND $condition
    ORDER BY j.log_date DESC
";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format log_date
        $formattedDate = "";
        if (!empty($row['log_date'])) {
            $dateTime = new DateTime($row['log_date']);
            $formattedDate = $dateTime->format("F j, Y g:i A");
        }

        // Format uploaded_at
        $uploadedDate = "";
        if (!empty($row['uploaded_at'])) {
            $uDate = new DateTime($row['uploaded_at']);
            $uploadedDate = $uDate->format("F j, Y g:i A");
        }

        $data[] = [
            "job_id"         => $row['job_id'],
            "log_date"       => $formattedDate,
            "job_reference_no" => $row['job_reference_no'],
            "staff_id"       => $row['staff_id'],
            "checker_id"     => $row['checker_id'],
            "client_email"   => $row['client_email'],
            "files_json"     => $row['files_json'],
            "uploaded_at"    => $uploadedDate,
            "uploaded_by"    => $row['uploaded_by']
        ];
    }
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);

$conn->close();
?>
