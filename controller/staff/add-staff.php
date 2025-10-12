<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_code  = trim($_POST['client_code'] ?? '');
    $client_name  = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');

    if (empty($client_code) || empty($client_name) || empty($client_email)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if client_code already exists
    $check = $conn->prepare("SELECT id FROM staff WHERE staff_id = ?");
    $check->bind_param("s", $client_code);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Staff code already exists.']);
        exit;
    }

    // ✅ Start transaction
    $conn->begin_transaction();

    try {
        // 1️⃣ Insert into clients table
        $stmt = $conn->prepare("INSERT INTO staff (staff_id, name, username) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $client_code, $client_name, $client_email);
        $stmt->execute();

        // 2️⃣ Create login entry in user_logins
        $defaultPassword = md5('password'); // 🔐 Default MD5 password

        $stmt2 = $conn->prepare("
            INSERT INTO user_logins (unique_code, username, password, client_name)
            VALUES (?, ?, ?, 'Staff')
        ");
        $stmt2->bind_param("sss", $client_code, $client_email, $defaultPassword);
        $stmt2->execute();

        // ✅ Commit both inserts
        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Staff and user login added successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>
