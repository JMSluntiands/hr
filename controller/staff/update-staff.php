<?php
include '../../database/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_code = trim($_POST['original_code'] ?? '');
    $client_code   = trim($_POST['client_code'] ?? '');
    $client_name   = trim($_POST['client_name'] ?? '');
    $client_email  = trim($_POST['client_email'] ?? '');

    if (empty($client_code) || empty($client_name) || empty($client_email)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 🔹 Check if client_code exists in user_logins (before updating)
        $check = $conn->prepare("SELECT unique_code FROM user_logins WHERE unique_code = ?");
        $check->bind_param("s", $original_code);
        $check->execute();
        $checkRes = $check->get_result();
        $hasLogin = $checkRes->num_rows > 0;

        // 🔸 Update client table
        $stmt = $conn->prepare("
            UPDATE staff 
            SET staff_id = ?, name = ?, username = ?
            WHERE staff_id = ?
        ");
        $stmt->bind_param("ssss", $client_code, $client_name, $client_email, $original_code);
        $stmt->execute();

        // 🔸 Update login table (if exists)
        if ($hasLogin) {
            $stmt2 = $conn->prepare("
                UPDATE user_logins 
                SET unique_code = ?, username = ?, client_name = 'Staff'
                WHERE unique_code = ?
            ");
            $stmt2->bind_param("sss", $client_code, $client_email, $original_code);
            $stmt2->execute();
        }

        $conn->commit();

        echo json_encode(['status' => 'success', 'message' => 'Staff updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error updating staff: ' . $e->getMessage()]);
    }
}
?>
