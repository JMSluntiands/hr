<?php
session_start();
include '../../database/db.php'; // mysqli connection

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields"]);
    exit;
}

$password = md5($password);
$query = $conn->prepare("SELECT * FROM user_logins WHERE username = ? AND password = ?");
$query->bind_param("ss", $email, $password);
$query->execute();
$result = $query->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['role'] = $row['userrole'];
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "role" => $row['userrole']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
}
