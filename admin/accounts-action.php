<?php
session_start();

if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/activity-logger.php';

$adminName = $_SESSION['name'] ?? 'Admin';
$redirect = 'accounts';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ' . $redirect);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if (!$id || !$action) {
    $_SESSION['accounts_msg'] = 'Invalid request.';
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'unlock') {
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM user_login");
    if ($r) { while ($row = $r->fetch_assoc()) $cols[] = $row['Field']; }
    $hasLocked = in_array('locked', $cols);

    if (!$hasLocked) {
        $_SESSION['accounts_msg'] = 'Unlock not available. Run database setup.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare("UPDATE user_login SET locked = 0, failed_attempts = 0, locked_at = NULL, unlock_requested = 0 WHERE id = ?");
    if (!$stmt) {
        $_SESSION['accounts_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Unlock Account', 'user_login', $id, "Unlocked account id $id");
        $_SESSION['accounts_msg'] = '✓ Account unlocked.';
    } else {
        $_SESSION['accounts_msg'] = 'Failed to unlock.';
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'edit_role') {
    $role = trim($_POST['role'] ?? '');
    if (!in_array(strtolower($role), ['admin', 'employee'])) {
        $_SESSION['accounts_msg'] = 'Invalid role.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare("UPDATE user_login SET role = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['accounts_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('si', $role, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Edit Role', 'user_login', $id, "Role set to $role for account id $id");
        $_SESSION['accounts_msg'] = '✓ Role updated.';
    } else {
        $_SESSION['accounts_msg'] = 'Failed to update role.';
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['accounts_msg'] = 'Unknown action.';
header('Location: ' . $redirect);
exit;
