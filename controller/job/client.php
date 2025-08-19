<?php
include_once '../../database/db.php';

$searchTerm = isset($_GET['q']) ? $_GET['q'] : "";
$searchTerm = "%" . $searchTerm . "%";

// Prepare and execute the statement
$stmt = $conn->prepare("SELECT * FROM client_accounts WHERE client_account_name LIKE ?");
$stmt->bind_param("s", $searchTerm);
$stmt->execute();

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "text" => $row['client_account_name'],
        "id" => $row['client_account_id'] // Use database primary key ID
    ];
}

$stmt->close();
$conn->close();

echo json_encode($data);
?>
