<?php
include_once '../../database/db.php';

$searchTerm = isset($_GET['q']) ? $_GET['q'] : "";
$searchTerm = "%" . $searchTerm . "%";

// Prepare and execute the statement
$stmt = $conn->prepare("SELECT * FROM job_requests WHERE job_request_type LIKE ?");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();

// echo "SELECT * FROM job_requests WHERE job_request_type = $searchTerm";

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "text" => $row['job_request_type'],
        "id" => $row['job_request_id'] // Use database primary key ID
    ];
}

$stmt->close();
$conn->close();

echo json_encode($data);
?>
