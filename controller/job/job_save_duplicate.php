<?php
include '../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceID   = $_POST['source_job_id'] ?? 0;
    $reference  = $_POST['reference'] ?? '';
    $client_ref = $_POST['client_ref'] ?? '';
    $assigned   = $_POST['assigned'] ?? '';
    $checked    = $_POST['checked'] ?? '';
    $compliance = $_POST['compliance'] ?? '';
    $priority   = $_POST['priority'] ?? '';
    $jobRequest = $_POST['jobRequest'] ?? '';
    $address    = $_POST['address'] ?? '';
    $clientID   = $_POST['client_account_id'] ?? '';
    $notes      = $_POST['notes'] ?? '';
    $status     = $_POST['status'] ?? 'Allocated';

    $user_role = $_SESSION['role'] ?? '';

    // 🔍 Get client code
    $client_code = null;
    if (!empty($user_role)) {
        $sql_client = "SELECT client_code FROM clients WHERE client_name = '".mysqli_real_escape_string($conn, $user_role)."' LIMIT 1";
        $result_client = mysqli_query($conn, $sql_client);
        if ($row = mysqli_fetch_assoc($result_client)) {
            $client_code = $row['client_code'];
        }
    }

    // 🔍 Job request type
    $job_request_type = null;
    if (!empty($jobRequest)) {
        $sql_job_request = "SELECT job_request_type FROM job_requests WHERE job_request_id = '".mysqli_real_escape_string($conn, $jobRequest)."' LIMIT 1";
        $result_job = mysqli_query($conn, $sql_job_request);
        if ($row = mysqli_fetch_assoc($result_job)) {
            $job_request_type = $row['job_request_type'];
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid job request ID."]);
            exit;
        }
    }

    // 🚫 Validation
    if ($user_role === 'LBS' || $user_role === 'LUNTIAN') {
        if (empty($reference) || empty($assigned) || empty($checked) || empty($client_code)) {
            echo json_encode(["status"=>"error","message"=>"Required fields missing (Reference/Assigned/Checked/Client)."]);
            exit;
        }
    } else {
        if (empty($client_ref) || empty($assigned) || empty($checked)) {
            echo json_encode(["status"=>"error","message"=>"Required fields missing (Client Ref/Assigned/Checked)."]);
            exit;
        }
        if (empty($reference)) $reference = null;
        if (empty($compliance)) $compliance = "2022 (WHO)";
    }

    // 🔁 Check duplicate
    $sql_check = "SELECT COUNT(*) as cnt FROM jobs 
                  WHERE job_reference_no = '".mysqli_real_escape_string($conn,$reference)."' 
                  OR client_reference_no = '".mysqli_real_escape_string($conn,$client_ref)."'";
    $result_check = mysqli_query($conn, $sql_check);
    $row = mysqli_fetch_assoc($result_check);
    if ($row['cnt'] > 0) {
        echo json_encode(["status"=>"error","message"=>"Duplicate found: Reference or Client Ref already exists."]);
        exit;
    }

    // ✅ File upload setup
    $folderName = $reference ?: (!empty($client_ref) ? $client_ref : "AUTO_" . time());
    $uploadDir = "../../document/" . $folderName . "/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $plansSaved = [];
    $docsSaved  = [];
    $maxFileSize = 10 * 1024 * 1024;

    // 📂 Fetch old files from source job
    $sql_src = "SELECT job_reference_no, upload_files, upload_project_files FROM jobs WHERE job_id = '".intval($sourceID)."' LIMIT 1";
    $res_src = mysqli_query($conn, $sql_src);
    $oldPlans = [];
    $oldDocs = [];
    $sourceFolder = null;

    if ($res_src && mysqli_num_rows($res_src) > 0) {
        $srcData = mysqli_fetch_assoc($res_src);
        $oldPlans = json_decode($srcData['upload_files'] ?? '[]', true);
        $oldDocs  = json_decode($srcData['upload_project_files'] ?? '[]', true);
        $sourceFolder = "../../document/" . $srcData['job_reference_no'] . "/";
    }

    $keepPlans = json_decode($_POST['keep_plans'] ?? '[]', true) ?: [];
    $keepDocs  = json_decode($_POST['keep_docs'] ?? '[]', true) ?: [];

    // 📌 Copy only kept old plans
    foreach ($oldPlans as $f) {
        if (in_array($f, $keepPlans) && $sourceFolder && file_exists($sourceFolder.$f)) {
            if (copy($sourceFolder.$f, $uploadDir.$f)) {
                $plansSaved[] = $f;
            }
        }
    }

    // 📌 Copy only kept old docs
    foreach ($oldDocs as $f) {
        if (in_array($f, $keepDocs) && $sourceFolder && file_exists($sourceFolder.$f)) {
            if (copy($sourceFolder.$f, $uploadDir.$f)) {
                $docsSaved[] = $f;
            }
        }
    }

    // 📁 Save NEW uploaded Plans
    if (!empty($_FILES['plans']['name'])) {
        foreach ($_FILES['plans']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['plans']['error'][$key] == 4) continue;
            if ($_FILES['plans']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"The file '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['plans']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
            if (move_uploaded_file($tmpName, $uploadDir . $safeName)) {
                $plansSaved[] = $safeName;
            }
        }
    }

    // 📁 Save NEW uploaded Docs
    if (!empty($_FILES['docs']['name'])) {
        foreach ($_FILES['docs']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['docs']['error'][$key] == 4) continue;
            if ($_FILES['docs']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"The file '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['docs']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
            if (move_uploaded_file($tmpName, $uploadDir . $safeName)) {
                $docsSaved[] = $safeName;
            }
        }
    }

    // JSON encode final list
    $plansJson = mysqli_real_escape_string($conn, json_encode($plansSaved));
    $docsJson  = mysqli_real_escape_string($conn, json_encode($docsSaved));

    // ✅ Escape fields
    $client_code = mysqli_real_escape_string($conn, $client_code);
    $reference   = mysqli_real_escape_string($conn, $reference);
    $client_ref  = mysqli_real_escape_string($conn, $client_ref);
    $assigned    = mysqli_real_escape_string($conn, $assigned);
    $checked     = mysqli_real_escape_string($conn, $checked);
    $compliance  = mysqli_real_escape_string($conn, $compliance);
    $priority    = mysqli_real_escape_string($conn, $priority);
    $jobRequest  = mysqli_real_escape_string($conn, $jobRequest);
    $address     = mysqli_real_escape_string($conn, $address);
    $job_request_type = mysqli_real_escape_string($conn, $job_request_type);
    $status      = mysqli_real_escape_string($conn, $status);
    $notes       = mysqli_real_escape_string($conn, $notes);
    $clientID    = mysqli_real_escape_string($conn, $clientID);

    // ✅ Insert
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
            ) VALUES (
                '$client_code',
                '$reference',
                '$client_ref',
                '$assigned',
                '$checked',
                '$compliance',
                '$priority',
                '$jobRequest',
                '$address',
                NOW(),
                '$job_request_type',
                '$status',
                NULLIF('$clientID',''),
                '$notes',
                '$plansJson',
                '$docsJson'
            )";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status"=>"success","message"=>"Job duplicated successfully with selected files copied."]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Database error: " . mysqli_error($conn)]);
    }
}
?>
