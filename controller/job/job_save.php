<?php
include '../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference  = $_POST['reference'] ?? '';
    $client_ref = $_POST['client_ref'] ?? '';
    $assigned   = $_POST['assigned'] ?? '';
    $checked    = $_POST['checked'] ?? '';
    $compliance = $_POST['compliance'] ?? '';
    $priority   = $_POST['priority'] ?? '';
    $jobRequest = $_POST['jobRequest'] ?? '';
    $address    = $_POST['address'] ?? '';
    $clientID   = $_POST['clientID'] ?? '';

    // Kunin client name/role sa session
    $user_client = $_SESSION['role'] ?? '';

    // Kunin client_code mula sa client_accounts
    $client_code = null;
    if (!empty($user_client)) {
        $sql_client = "SELECT client_code FROM clients WHERE client_name = ? LIMIT 1";
        $stmt_client = $conn->prepare($sql_client);
        $stmt_client->bind_param("s", $user_client);
        $stmt_client->execute();
        $result_client = $stmt_client->get_result();
        if ($row = $result_client->fetch_assoc()) {
            $client_code = $row['client_code'];
        }
        $stmt_client->close();
    }

    // Kunin job_request_type mula sa job_requests
    $job_request_type = null;
    if (!empty($jobRequest)) {
        $sql_job_request = "SELECT job_request_type FROM job_requests WHERE job_request_id = ? LIMIT 1";
        $stmt_job = $conn->prepare($sql_job_request);
        $stmt_job->bind_param("s", $jobRequest);
        $stmt_job->execute();
        $result_job = $stmt_job->get_result();
        if ($row = $result_job->fetch_assoc()) {
            $job_request_type = $row['job_request_type'];
        } else {
            echo json_encode(["status" => "error", "message" => "No job request found for ID: $jobRequest"]);
            exit;
        }
        $stmt_job->close();
    } else {
        echo json_encode(["status" => "error", "message" => "No jobRequest provided"]);
        exit;
    }

    // Simple validation
    if (empty($reference) || empty($assigned) || empty($checked) || empty($client_code)) {
        echo json_encode([
            "status"  => "error",
            "message" => "Required fields are missing or client not found"
        ]);
        exit;
    }

    // ✅ Duplicate check
    $sql_check = "SELECT COUNT(*) as cnt FROM jobs WHERE job_reference_no = ? OR client_reference_no = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $reference, $client_ref);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($row['cnt'] > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Duplicate found: job_reference_no or client_reference_no already exists."
        ]);
        exit;
    }

    // Insert Job
    $sql = "INSERT INTO jobs (
                client_code,
                job_reference_no,
                client_reference_no,
                staff_id,
                checker_id,
                ncc_compliance,
                priority,
                job_request_id,
                address_client,
                log_date,
                job_type,
                job_status,
                client_account_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Pending', ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssi",
        $client_code,
        $reference,
        $client_ref,
        $assigned,
        $checked,
        $compliance,
        $priority,
        $jobRequest,
        $address,
        $job_request_type,
        $clientID
    );

    if ($stmt->execute()) {
        echo json_encode([
            "status"  => "success",
            "message" => "New job has been created successfully"
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Database error: " . $stmt->error
        ]);
    }

    $stmt->close();
}
