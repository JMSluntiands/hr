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
$employeeId = (int)($_POST['employee_id'] ?? $_GET['employee_id'] ?? 0);

if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }
        return $password;
    }
}

if (!$action) {
    $_SESSION['accounts_msg'] = 'Invalid request.';
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'edit_role') {
    if (!$id) {
        $_SESSION['accounts_msg'] = 'Invalid account id.';
        header('Location: ' . $redirect);
        exit;
    }

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

if ($action === 'create_employee_account') {
    if (!$employeeId) {
        $_SESSION['accounts_msg'] = 'Invalid employee id.';
        header('Location: ' . $redirect);
        exit;
    }

    $empStmt = $conn->prepare("SELECT id, full_name, email FROM employees WHERE id = ? LIMIT 1");
    if (!$empStmt) {
        $_SESSION['accounts_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $empStmt->bind_param('i', $employeeId);
    $empStmt->execute();
    $employee = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();

    if (!$employee) {
        $_SESSION['accounts_msg'] = 'Employee not found.';
        header('Location: ' . $redirect);
        exit;
    }

    $email = trim((string)($employee['email'] ?? ''));
    if ($email === '') {
        $_SESSION['accounts_msg'] = 'Employee email is missing.';
        header('Location: ' . $redirect);
        exit;
    }

    $checkStmt = $conn->prepare("SELECT id FROM user_login WHERE email = ? LIMIT 1");
    if (!$checkStmt) {
        $_SESSION['accounts_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($exists) {
        $_SESSION['accounts_msg'] = 'Account already exists for this employee.';
        header('Location: ' . $redirect);
        exit;
    }

    $plainPassword = generateRandomPassword(10);
    $hashedPassword = md5($plainPassword);
    $employeeRole = 'employee';

    $ins = $conn->prepare("INSERT INTO user_login (email, password, role) VALUES (?, ?, ?)");
    if (!$ins) {
        $_SESSION['accounts_msg'] = 'Database error while creating account.';
        header('Location: ' . $redirect);
        exit;
    }
    $ins->bind_param('sss', $email, $hashedPassword, $employeeRole);
    if (!$ins->execute()) {
        $ins->close();
        $_SESSION['accounts_msg'] = 'Failed to create account.';
        header('Location: ' . $redirect);
        exit;
    }
    $newAccountId = (int)$conn->insert_id;
    $ins->close();

    $_SESSION['accounts_generated_password'] = $plainPassword;
    $_SESSION['accounts_generated_email'] = $email;
    $_SESSION['accounts_generated_mode'] = 'created';
    logActivity($conn, 'Create Account', 'user_login', $newAccountId, "Created employee login account for $email");
    $_SESSION['accounts_msg'] = '✓ Employee account created. Copy the generated password below.';
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'reset_password') {
    if (!$id) {
        $_SESSION['accounts_msg'] = 'Invalid account id.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, email, role FROM user_login WHERE id = ? LIMIT 1");
    if (!$stmt) {
        $_SESSION['accounts_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account) {
        $_SESSION['accounts_msg'] = 'Account not found.';
        header('Location: ' . $redirect);
        exit;
    }

    if (strtolower((string)($account['role'] ?? '')) !== 'employee') {
        $_SESSION['accounts_msg'] = 'Credentials email is only available for employee accounts.';
        header('Location: ' . $redirect);
        exit;
    }

    $email = trim((string)($account['email'] ?? ''));
    if ($email === '') {
        $_SESSION['accounts_msg'] = 'Employee email is missing.';
        header('Location: ' . $redirect);
        exit;
    }

    $plainPassword = generateRandomPassword(10);
    $hashedPassword = md5($plainPassword);

    $upd = $conn->prepare("UPDATE user_login SET password = ? WHERE id = ?");
    if (!$upd) {
        $_SESSION['accounts_msg'] = 'Database error while updating password.';
        header('Location: ' . $redirect);
        exit;
    }
    $upd->bind_param('si', $hashedPassword, $id);
    if (!$upd->execute()) {
        $upd->close();
        $_SESSION['accounts_msg'] = 'Failed to update password.';
        header('Location: ' . $redirect);
        exit;
    }
    $upd->close();

    $_SESSION['accounts_generated_password'] = $plainPassword;
    $_SESSION['accounts_generated_email'] = $email;
    $_SESSION['accounts_generated_mode'] = 'reset';
    logActivity($conn, 'Reset Password', 'user_login', $id, "Generated new random password for $email");
    $_SESSION['accounts_msg'] = '✓ Password reset complete. Copy the generated password below.';
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['accounts_msg'] = 'Unknown action.';
header('Location: ' . $redirect);
exit;
