<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}

$hashedPassword = md5($password);
$LOCKOUT_AFTER = 5;

// Check if lockout columns exist
$hasLockout = false;
$r = $conn->query("SHOW COLUMNS FROM user_login");
if ($r) {
    $cols = [];
    while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
    $hasLockout = in_array('locked', $cols) && in_array('failed_attempts', $cols);
}

if ($hasLockout) {
    $chk = $conn->prepare('SELECT id, email, password, role, locked, failed_attempts FROM user_login WHERE email = ? LIMIT 1');
    $chk->bind_param('s', $email);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($row) {
        if (!empty($row['locked'])) {
            echo json_encode([
                'status' => 'locked',
                'message' => 'Account locked due to too many failed login attempts. Please request an unlock from admin.',
            ]);
            exit;
        }

        if ($row['password'] !== $hashedPassword) {
            $next = (int)$row['failed_attempts'] + 1;
            $willLock = $next >= $LOCKOUT_AFTER;
            $sql = $willLock
                ? 'UPDATE user_login SET failed_attempts = ?, locked = 1, locked_at = NOW() WHERE id = ?'
                : 'UPDATE user_login SET failed_attempts = ? WHERE id = ?';
            $u = $conn->prepare($sql);
            $u->bind_param('ii', $next, $row['id']);
            $u->execute();
            $u->close();

            echo json_encode([
                'status' => $willLock ? 'locked' : 'error',
                'message' => $willLock
                    ? 'Account locked after 5 failed attempts. Please request an unlock from admin.'
                    : 'Invalid email or password.',
                'locked' => $willLock,
            ]);
            exit;
        }

        $conn->query("UPDATE user_login SET failed_attempts = 0 WHERE id = " . (int)$row['id']);
    }
}

$stmt = $conn->prepare('SELECT id, email, password, role FROM user_login WHERE email = ? AND password = ? LIMIT 1');
$stmt->bind_param('ss', $email, $hashedPassword);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'employee';

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
    ]);
} else {
    if ($hasLockout) {
        $chk = $conn->prepare('SELECT id, failed_attempts FROM user_login WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($row) {
            $next = (int)$row['failed_attempts'] + 1;
            $willLock = $next >= $LOCKOUT_AFTER;
            if ($willLock) {
                $u = $conn->prepare('UPDATE user_login SET failed_attempts = ?, locked = 1, locked_at = NOW() WHERE id = ?');
                $u->bind_param('ii', $next, $row['id']);
            } else {
                $u = $conn->prepare('UPDATE user_login SET failed_attempts = ? WHERE id = ?');
                $u->bind_param('ii', $next, $row['id']);
            }
            $u->execute();
            $u->close();
            echo json_encode([
                'status' => $willLock ? 'locked' : 'error',
                'message' => $willLock
                    ? 'Account locked after 5 failed attempts. Please request an unlock from admin.'
                    : 'Invalid email or password.',
                'locked' => $willLock,
            ]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}
