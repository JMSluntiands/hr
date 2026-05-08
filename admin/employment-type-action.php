<?php
session_start();

if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
require_once __DIR__ . '/../include/ensure_employment_types_table.php';
include 'include/activity-logger.php';
if ($conn) {
    ensure_employment_types_table($conn);
}

$redirect = 'employment-type.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name   = trim($_POST['name'] ?? '');

if ($action === 'create') {
    if ($name === '') {
        $_SESSION['employment_type_msg'] = 'Employment type name is required.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO `employment_types` (`name`) VALUES (?)');
    if (!$stmt) {
        $_SESSION['employment_type_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        $newEmploymentTypeId = (int)$conn->insert_id;
        logActivity($conn, 'Create Employment Type', 'employment_types', $newEmploymentTypeId, "Created employment type: $name");
        $_SESSION['employment_type_msg'] = '✓ Employment type added.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['employment_type_msg'] = 'Employment type name already exists.';
        } else {
            $_SESSION['employment_type_msg'] = 'Failed to add employment type: ' . ($conn->error ?: 'execute failed');
        }
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'update') {
    if (!$id || $name === '') {
        $_SESSION['employment_type_msg'] = 'Invalid request.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare('UPDATE `employment_types` SET `name` = ? WHERE `id` = ?');
    if (!$stmt) {
        $_SESSION['employment_type_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('si', $name, $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Update Employment Type', 'employment_types', $id, "Updated employment type #$id to: $name");
        $_SESSION['employment_type_msg'] = '✓ Employment type updated.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['employment_type_msg'] = 'Employment type name already exists.';
        } else {
            $_SESSION['employment_type_msg'] = 'Failed to update employment type: ' . ($conn->error ?: 'execute failed');
        }
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'delete') {
    if (!$id) {
        $_SESSION['employment_type_msg'] = 'Invalid request.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM `employment_types` WHERE `id` = ?');
    if (!$stmt) {
        $_SESSION['employment_type_msg'] = 'Database error: ' . ($conn->error ?: 'prepare failed');
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logActivity($conn, 'Delete Employment Type', 'employment_types', $id, "Deleted employment type #$id");
        $_SESSION['employment_type_msg'] = '✓ Employment type deleted.';
    } else {
        $_SESSION['employment_type_msg'] = 'Failed to delete employment type: ' . ($conn->error ?: 'execute failed');
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['employment_type_msg'] = 'Unknown action.';
header('Location: ' . $redirect);
exit;

