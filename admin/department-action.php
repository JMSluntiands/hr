<?php
session_start();

if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
require_once __DIR__ . '/../include/ensure_departments_performance_column.php';
include 'include/activity-logger.php';

$redirect = 'department.php';

$hasPerfReviewCol = false;
if ($conn) {
    ensure_departments_performance_column($conn);
    $colChk = $conn->query("SHOW COLUMNS FROM `departments` LIKE 'additional_performance_review'");
    $hasPerfReviewCol = ($colChk && $colChk->num_rows > 0);
}
$additionalPerf = ($hasPerfReviewCol && isset($_POST['additional_performance_review'])) ? 1 : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name   = trim($_POST['name'] ?? '');

if ($action === 'create') {
    if ($name === '') {
        $_SESSION['department_msg'] = 'Department name is required.';
        header('Location: ' . $redirect);
        exit;
    }

    if ($hasPerfReviewCol) {
        $stmt = $conn->prepare('INSERT INTO `departments` (`name`, `additional_performance_review`) VALUES (?, ?)');
    } else {
        $stmt = $conn->prepare('INSERT INTO `departments` (`name`) VALUES (?)');
    }
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    if ($hasPerfReviewCol) {
        $stmt->bind_param('si', $name, $additionalPerf);
    } else {
        $stmt->bind_param('s', $name);
    }
    if ($stmt->execute()) {
        $newDepartmentId = (int)$conn->insert_id;
        logActivity($conn, 'Create Department', 'departments', $newDepartmentId, "Created department: $name");
        $_SESSION['department_msg'] = '✓ Department added.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['department_msg'] = 'Department name already exists.';
        } else {
            $_SESSION['department_msg'] = 'Failed to add department: ' . ($conn->error ?: 'execute failed');
        }
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'update') {
    if (!$id || $name === '') {
        $_SESSION['department_msg'] = 'Invalid request.';
        header('Location: ' . $redirect);
        exit;
    }

    if ($hasPerfReviewCol) {
        $stmt = $conn->prepare('UPDATE `departments` SET `name` = ?, `additional_performance_review` = ? WHERE `id` = ?');
    } else {
        $stmt = $conn->prepare('UPDATE `departments` SET `name` = ? WHERE `id` = ?');
    }
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    if ($hasPerfReviewCol) {
        $stmt->bind_param('sii', $name, $additionalPerf, $id);
    } else {
        $stmt->bind_param('si', $name, $id);
    }
    if ($stmt->execute()) {
        logActivity($conn, 'Update Department', 'departments', $id, "Updated department #$id to: $name");
        $_SESSION['department_msg'] = '✓ Department updated.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['department_msg'] = 'Department name already exists.';
        } else {
            $_SESSION['department_msg'] = 'Failed to update department: ' . ($conn->error ?: 'execute failed');
        }
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'delete') {
    if (!$id) {
        $_SESSION['department_msg'] = 'Invalid request.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM `departments` WHERE `id` = ?');
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Delete Department', 'departments', $id, "Deleted department #$id");
        $_SESSION['department_msg'] = '✓ Department deleted.';
    } else {
        $_SESSION['department_msg'] = 'Failed to delete department: ' . ($conn->error ?: 'execute failed');
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['department_msg'] = 'Unknown action.';
header('Location: ' . $redirect);
exit;

