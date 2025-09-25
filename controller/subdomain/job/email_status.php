<?php
include '../../../database/db.php';
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = intval($_POST['job_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $role = $_SESSION['role'] ?? '';
    $unique_id = $_SESSION['unique_id'] ?? '';

    if ($job_id <= 0 || $status === '') {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
        exit;
    }

    // ✅ Role-based security check
    $allowed = false;
    if ($role === "LUNTIAN") {
        $allowed = true; // lahat pwede
    } elseif ($role === "Staff") {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM jobs WHERE job_id = ? AND staff_id = ?");
        $stmt->bind_param("is", $job_id, $unique_id);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        if ($cnt > 0) $allowed = true;
    } elseif ($role === "Checker") {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM jobs WHERE job_id = ? AND checker_id = ?");
        $stmt->bind_param("is", $job_id, $unique_id);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        if ($cnt > 0) $allowed = true;
    } else {
        // Clients: check if job belongs to their client_code
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS cnt
            FROM jobs j
            LEFT JOIN clients c ON j.client_code = c.client_code
            WHERE j.job_id = ? AND c.client_name = ?
        ");
        $stmt->bind_param("is", $job_id, $role);
        $stmt->execute();
        $stmt->bind_result($cnt);
        $stmt->fetch();
        $stmt->close();
        if ($cnt > 0) $allowed = true;
    }

    if (!$allowed) {
        echo json_encode(["status" => "error", "message" => "You are not allowed to update this job."]);
        exit;
    }

    // ✅ Prevent marking as Completed if no uploaded files exist
    if ($status === "Completed") {
        $check = $conn->prepare("SELECT COUNT(*) as total FROM staff_uploaded_files WHERE job_id = ?");
        $check->bind_param("i", $job_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();

        if ($result['total'] == 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Cannot mark as Completed. No files uploaded for this job."
            ]);
            exit;
        }
    }

    // ✅ Update status
    $stmt = $conn->prepare("UPDATE jobs SET job_status = ? WHERE job_id = ?");
    $stmt->bind_param("si", $status, $job_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
