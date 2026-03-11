<?php
session_start();

if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';

$redirect = 'department.php';

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

    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        $_SESSION['department_msg'] = '✓ Department added.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['department_msg'] = 'Department name already exists.';
        } else {
            $_SESSION['department_msg'] = 'Failed to add department.';
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

    $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('si', $name, $id);
    if ($stmt->execute()) {
        $_SESSION['department_msg'] = '✓ Department updated.';
    } else {
        if ($conn->errno === 1062) {
            $_SESSION['department_msg'] = 'Department name already exists.';
        } else {
            $_SESSION['department_msg'] = 'Failed to update department.';
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

    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
    if (!$stmt) {
        $_SESSION['department_msg'] = 'Database error.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['department_msg'] = '✓ Department deleted.';
    } else {
        $_SESSION['department_msg'] = 'Failed to delete department.';
    }
    $stmt->close();
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['department_msg'] = 'Unknown action.';
header('Location: ' . $redirect);
exit;

