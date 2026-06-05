<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Admin access only.']);
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/mysqli-stmt-fetch.php';
require_once __DIR__ . '/../include/admin-permissions.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database connection failed. Check database/db.php on the server.']);
    exit;
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'load' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Invalid user']);
        exit;
    }
    $matrix = adminGetUserPermissionsMatrix($conn, $userId);
    $configured = adminUserHasConfiguredPermissions($conn, $userId);
    echo json_encode([
        'ok' => true,
        'configured' => $configured,
        'permissions' => $matrix,
    ]);
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Please select an admin user.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM user_login WHERE id = ? AND LOWER(role) = 'admin' LIMIT 1");
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $userRow = hr_stmt_fetch_one_assoc($stmt);
    $stmt->close();
    if (!$userRow) {
        echo json_encode(['ok' => false, 'message' => 'Selected user is not an admin account.']);
        exit;
    }

    $raw = $_POST['permissions'] ?? '';
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
    } else {
        $decoded = $raw;
    }
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $permissionsByDept = [];
    foreach ($decoded as $deptId => $keys) {
        $deptId = (int)$deptId;
        if ($deptId <= 0) {
            continue;
        }
        if (!is_array($keys)) {
            continue;
        }
        $permissionsByDept[$deptId] = array_values(array_unique(array_map('strval', $keys)));
    }

    if (!adminSaveUserPermissions($conn, $userId, $permissionsByDept)) {
        echo json_encode(['ok' => false, 'message' => 'Failed to save permissions. Check database connection and admin_user_permissions table.']);
        exit;
    }

    echo json_encode(['ok' => true, 'message' => 'Permissions saved successfully.']);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Invalid action']);
