<?php
session_start();
include '../../database/db.php'; // mysqli connection

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please fill in all fields',
    ]);
    exit;
}

// Use the same hashing style as before (md5)
$hashedPassword = md5($password);

$stmt = $conn->prepare('SELECT id, email, password, role FROM user_login WHERE email = ? AND password = ? LIMIT 1');
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error.',
    ]);
    exit;
}

$stmt->bind_param('ss', $email, $hashedPassword);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'employee';

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $_SESSION['role'],
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email or password',
    ]);
}
