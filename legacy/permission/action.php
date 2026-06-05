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

ensureDepartmentPermissionsTable($conn);

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'load' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $departmentId = (int)($_GET['department_id'] ?? 0);
    if ($departmentId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Please select a department.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id FROM departments WHERE id = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $deptRow = hr_stmt_fetch_one_assoc($stmt);
    $stmt->close();
    if (!$deptRow) {
        echo json_encode(['ok' => false, 'message' => 'Invalid department.']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'configured' => departmentHasConfiguredPermissions($conn, $departmentId),
        'permissions' => adminGetDepartmentPermissionKeys($conn, $departmentId),
    ]);
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentId = (int)($_POST['department_id'] ?? 0);
    if ($departmentId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Please select a department.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id FROM departments WHERE id = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $deptRow = hr_stmt_fetch_one_assoc($stmt);
    $stmt->close();
    if (!$deptRow) {
        echo json_encode(['ok' => false, 'message' => 'Invalid department.']);
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

    $permissionKeys = array_values(array_unique(array_map('strval', $decoded)));

    if (!adminSaveDepartmentPermissions($conn, $departmentId, $permissionKeys)) {
        echo json_encode(['ok' => false, 'message' => 'Failed to save permissions. Check department_permissions table.']);
        exit;
    }

    echo json_encode(['ok' => true, 'message' => 'Department permissions saved successfully.']);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => 'Invalid action']);
