<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}

// Only allow @luntiands.com email addresses
$allowedDomain = 'luntiands.com';
$emailLower = strtolower($email);
if (substr($emailLower, -strlen($allowedDomain) - 1) !== '@' . $allowedDomain) {
    echo json_encode(['status' => 'error', 'message' => 'Access is restricted to @luntiands.com email addresses only.']);
    exit;
}

$hashedPassword = md5($password);

$stmt = $conn->prepare('SELECT id, email, password, role FROM user_login WHERE email = ? AND password = ? LIMIT 1');
$stmt->bind_param('ss', $email, $hashedPassword);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'employee';
    unset($_SESSION['admin_module']);
    $_SESSION['login_cache_buster'] = bin2hex(random_bytes(8));

    // Update last login timestamp (if column exists)
    $cc = $conn->query("SHOW COLUMNS FROM user_login LIKE 'last_login'");
    if ($cc && $cc->num_rows > 0) {
        $conn->query("UPDATE user_login SET last_login = NOW() WHERE id = " . (int)$user['id']);
    }

    $defaultPasswords = ['password123', '123456789', 'password', 'admin123', '123456'];
    $isDefaultPassword = in_array(strtolower($password), array_map('strtolower', $defaultPasswords));
    if (!$isDefaultPassword && (preg_match('/^[0-9]{6,}$/', $password) || strlen($password) <= 6)) {
        $isDefaultPassword = true;
    }
    $_SESSION['is_default_password'] = $isDefaultPassword;

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $_SESSION['role'],
        'is_default_password' => $isDefaultPassword,
        'cache_buster' => $_SESSION['login_cache_buster'],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}
