<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = intval($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $message     = trim($_POST['message'] ?? '');
    $start_date  = $_POST['start_date'] ?: date('Y-m-d H:i:s');
    $end_date    = $_POST['end_date'] ?: null;
    $status      = $_POST['status'] ?? 'active';
    $updated_by  = $_SESSION['username'] ?? 'admin';

    if (empty($title) || empty($message) || $id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 🔹 Check if announcement exists
        $check = $conn->prepare("SELECT id FROM announcement WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $checkRes = $check->get_result();

        if ($checkRes->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Announcement not found.']);
            exit;
        }

        // 🔸 Update announcement table
        $stmt = $conn->prepare("
            UPDATE announcement 
            SET title = ?, 
                message = ?, 
                start_date = ?, 
                end_date = ?, 
                status = ?, 
                created_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $title, $message, $start_date, $end_date, $status, $updated_by, $id);
        $stmt->execute();

        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Announcement updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error updating announcement: ' . $e->getMessage()]);
    }
}
?>
