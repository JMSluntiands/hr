<?php
include '../../database/db.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $start_date = $_POST['start_date'] ?: date('Y-m-d H:i:s');
    $end_date = $_POST['end_date'] ?: null;
    $status = $_POST['status'] ?? 'active';
    $created_by = $_SESSION['username'] ?? 'admin';

    if (empty($title) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO announcement (title, message, start_date, end_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $title, $message, $start_date, $end_date, $status, $created_by);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Announcement added successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
