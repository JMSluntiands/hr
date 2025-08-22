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
    $notes      = $_POST['notes'] ?? '';
    $status     = $_POST['status'] ?? 'Allocated';

    $user_client = $_SESSION['role'] ?? '';

    // 🔍 Get client code from session
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

    // 🔍 Get job request type
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

    // 🚫 Validation
    if (empty($reference) || empty($assigned) || empty($checked) || empty($client_code)) {
        echo json_encode([
            "status"  => "error",
            "message" => "Required fields are missing or client not found"
        ]);
        exit;
    }

    // 🔁 Check for duplicates
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

    // ✅ File upload setup
    $uploadDir = "../../document/" . $reference . "/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $plansSaved = [];
    $docsSaved  = [];

    // Maximum file size allowed (10MB)
$maxFileSize = 10 * 1024 * 1024; // 10MB in bytes

// 📁 Save Plans
if (!empty($_FILES['plans']['name'])) {
    foreach ($_FILES['plans']['name'] as $key => $filename) {
        if (empty($filename) || $_FILES['plans']['error'][$key] == 4) continue;

        // Get the file size
        $fileSize = $_FILES['plans']['size'][$key];

        if ($fileSize > $maxFileSize) {
            echo json_encode(["status" => "error", "message" => "The file '$filename' exceeds the maximum allowed size of 10MB."]);
            exit;
        }

        $tmpName = $_FILES['plans']['tmp_name'][$key];
        $error   = $_FILES['plans']['error'][$key];

        $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
        $targetFile = $uploadDir . $safeName;

        if ($error !== UPLOAD_ERR_OK) {
            echo json_encode(["status" => "error", "message" => "Upload error ($filename): code $error"]);
            exit;
        }

        if (move_uploaded_file($tmpName, $targetFile)) {
            $plansSaved[] = $safeName;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to move plan: $filename"]);
            exit;
        }
    }
}

// 📁 Save Docs
if (!empty($_FILES['docs']['name'])) {
    foreach ($_FILES['docs']['name'] as $key => $filename) {
        if (empty($filename) || $_FILES['docs']['error'][$key] == 4) continue;

        // Get the file size
        $fileSize = $_FILES['docs']['size'][$key];

        if ($fileSize > $maxFileSize) {
            echo json_encode(["status" => "error", "message" => "The file '$filename' exceeds the maximum allowed size of 10MB."]);
            exit;
        }

        $tmpName = $_FILES['docs']['tmp_name'][$key];
        $error   = $_FILES['docs']['error'][$key];

        $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
        $targetFile = $uploadDir . $safeName;

        if ($error !== UPLOAD_ERR_OK) {
            echo json_encode(["status" => "error", "message" => "Upload error ($filename): code $error"]);
            exit;
        }

        if (move_uploaded_file($tmpName, $targetFile)) {
            $docsSaved[] = $safeName;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to move doc: $filename"]);
            exit;
        }
    }
}


    // 🔁 Convert to JSON
    $plansJson = json_encode($plansSaved);
    $docsJson  = json_encode($docsSaved);

    // ✅ Insert into DB
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
                client_account_id,
                notes,
                upload_files,
                upload_project_files
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssssssssisss",
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
        $status,
        $clientID,
        $notes,
        $plansJson,
        $docsJson
    );

    if ($stmt->execute()) {
        echo json_encode([
            "status"  => "success",
            "message" => "New job has been created successfully, files uploaded."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Database error: " . $stmt->error
        ]);
    }

    $stmt->close();
}
