<?php
session_start();
include '../../database/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

$r = $conn->query("SHOW COLUMNS FROM user_login");
$cols = [];
if ($r) { while ($row = $r->fetch_assoc()) $cols[] = $row['Field']; }
if (!in_array('unlock_requested', $cols)) {
    echo json_encode(['status' => 'error', 'message' => 'Unlock request not available.']);
    exit;
}

$stmt = $conn->prepare('SELECT id, locked FROM user_login WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'No account found for this email.']);
    exit;
}

if (empty($row['locked'])) {
    echo json_encode(['status' => 'error', 'message' => 'This account is not locked.']);
    exit;
}

$u = $conn->prepare('UPDATE user_login SET unlock_requested = 1 WHERE id = ?');
$u->bind_param('i', $row['id']);
if ($u->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Unlock requested. Admin will review and unlock your account.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Request failed. Please try again.']);
}
$u->close();
