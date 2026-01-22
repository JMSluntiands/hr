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
    
    // Check if password is default (common defaults: password123, 123456789, password, admin123)
    $defaultPasswords = ['password123', '123456789', 'password', 'admin123', '123456'];
    $isDefaultPassword = in_array(strtolower($password), array_map('strtolower', $defaultPasswords));
    
    // Also check if password matches common default patterns
    if (!$isDefaultPassword) {
        // Check if password is all numbers or very simple
        if (preg_match('/^[0-9]{6,}$/', $password) || strlen($password) <= 6) {
            $isDefaultPassword = true;
        }
    }
    
    $_SESSION['is_default_password'] = $isDefaultPassword;

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $_SESSION['role'],
        'is_default_password' => $isDefaultPassword,
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email or password',
    ]);
}
