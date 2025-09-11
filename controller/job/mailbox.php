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

// base SQL (walang semicolon sa dulo)
$sql = "SELECT DISTINCT 
            j.job_id, 
            j.log_date, 
            j.staff_id, 
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
        WHERE j.job_status = 'For Email Confirmation'";

// idagdag lang filter kung hindi LUNTIAN
if ($user_client !== 'LUNTIAN' && $usersID !== '') {
    $sql .= " AND j.client_code = '$usersID'";
}

// ORDER BY laging huli // $mail->send();
$sql .= " ORDER BY j.log_date DESC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);
?>
