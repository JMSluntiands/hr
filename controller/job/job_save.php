<?php
include '../../database/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
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
    $clientID   = $_POST['client_account_id'] ?? '';
    $notes      = $_POST['notes'] ?? '';
    $status     = $_POST['status'] ?? 'Allocated';

    $user_role = $_SESSION['role'] ?? '';

    // 🔍 Get client code from session
    $client_code = null;
    if (!empty($user_role)) {
        $sql_client = "SELECT client_code FROM clients WHERE client_name = '".mysqli_real_escape_string($conn, $user_role)."' LIMIT 1";
        $result_client = mysqli_query($conn, $sql_client);
        if ($row = mysqli_fetch_assoc($result_client)) {
            $client_code = $row['client_code'];
        }
    }

    // 🔍 Get job request type
    $job_request_type = null;
    if (!empty($jobRequest)) {
        $sql_job_request = "SELECT job_request_type FROM job_requests WHERE job_request_id = '".mysqli_real_escape_string($conn, $jobRequest)."' LIMIT 1";
        $result_job = mysqli_query($conn, $sql_job_request);
        if ($row = mysqli_fetch_assoc($result_job)) {
            $job_request_type = $row['job_request_type'];
        } else {
            echo json_encode(["status" => "error", "message" => "No job request found for ID: $jobRequest"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No jobRequest provided"]);
        exit;
    }

    // 🚫 Validation
    if ($user_role === 'LBS' || $user_role === 'LUNTIAN') {
        if (empty($reference) || empty($assigned) || empty($checked) || empty($client_code)) {
            echo json_encode(["status"=>"error","message"=>"Required fields are missing (Reference/Assigned/Checked/Client)"]);
            exit;
        }
    } else {
        if (empty($client_ref) || empty($assigned) || empty($checked)) {
            echo json_encode(["status"=>"error","message"=>"Required fields are missing (Client Ref/Assigned/Checked)"]);
            exit;
        }
        if (empty($reference)) {
            $reference = null;
        }
        if (empty($compliance)) {
            $compliance = "2022 (WHO)";
        }
    }

    // 🔍 Normalize Address (remove spaces + lowercase para consistent)
    $normalizedAddress = strtolower(preg_replace('/\s+/', '', $address));

    // 🔁 Check duplicate address (ignore spaces)
    $sql_check_address = "
        SELECT COUNT(*) as cnt 
        FROM jobs 
        WHERE REPLACE(LOWER(address_client), ' ', '') = '" . mysqli_real_escape_string($conn, $normalizedAddress) . "'
    ";
    $result_addr = mysqli_query($conn, $sql_check_address);
    $row_addr = mysqli_fetch_assoc($result_addr);

    if ($row_addr['cnt'] > 0) {
        echo json_encode(["status" => "error", "message" => "Duplicate job address found (ignoring spaces)."]);
        exit;
    }


    // 🔁 Check for duplicates
    $sql_check = "SELECT COUNT(*) as cnt FROM jobs 
                  WHERE job_reference_no = '".mysqli_real_escape_string($conn,$reference)."'";
    $result_check = mysqli_query($conn, $sql_check);
    $row = mysqli_fetch_assoc($result_check);
    if ($row['cnt'] > 0) {
        echo json_encode(["status"=>"error","message"=>"Duplicate found: job_reference_no already exists."]);
        exit;
    }

    // ✅ File upload setup
    $folderName = $reference;
    if (empty($folderName)) {
        $folderName = !empty($client_ref) ? $client_ref : "AUTO_" . time();
    }

    $uploadDir = "../../document/" . $folderName . "/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $plansSaved = [];
    $docsSaved  = [];
    $maxFileSize = 10 * 1024 * 1024;

    // 📁 Save Plans
    if (!empty($_FILES['plans']['name'])) {
        foreach ($_FILES['plans']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['plans']['error'][$key] == 4) continue;
            if ($_FILES['plans']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"The file '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['plans']['tmp_name'][$key];
            $error   = $_FILES['plans']['error'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
            $targetFile = $uploadDir . $safeName;
            if ($error !== UPLOAD_ERR_OK) { echo json_encode(["status"=>"error","message"=>"Upload error ($filename): code $error"]); exit; }
            if (move_uploaded_file($tmpName, $targetFile)) {
                $plansSaved[] = $safeName;
            } else {
                echo json_encode(["status"=>"error","message"=>"Failed to move plan: $filename"]); exit;
            }
        }
    }

    // 📁 Save Docs
    if (!empty($_FILES['docs']['name'])) {
        foreach ($_FILES['docs']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['docs']['error'][$key] == 4) continue;
            if ($_FILES['docs']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"The file '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['docs']['tmp_name'][$key];
            $error   = $_FILES['docs']['error'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $filename);
            $targetFile = $uploadDir . $safeName;
            if ($error !== UPLOAD_ERR_OK) { echo json_encode(["status"=>"error","message"=>"Upload error ($filename): code $error"]); exit; }
            if (move_uploaded_file($tmpName, $targetFile)) {
                $docsSaved[] = $safeName;
            } else {
                echo json_encode(["status"=>"error","message"=>"Failed to move doc: $filename"]); exit;
            }
        }
    }

    // 🔁 Convert to JSON
    $plansJson = mysqli_real_escape_string($conn, json_encode($plansSaved));
    $docsJson  = mysqli_real_escape_string($conn, json_encode($docsSaved));

    // Escape other fields
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
    $log_date = $_POST['log_date'] ?? date("Y-m-d H:i:s");

    // ✅ Single insert query (NULLIF trick for clientID)
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
                '$log_date',
                '$job_request_type',
                '$status',
                NULLIF('$clientID',''),
                '$notes',
                '$plansJson',
                '$docsJson'
            )";

    $clientName = mysqli_query($conn, "SELECT * FROM client_accounts WHERE client_account_id = '$clientID'");
    $cai = mysqli_fetch_assoc($clientName);

    $client_account_name = $cai['client_account_name'];

    // $subject = "Job Update : ".$client_account_name." ".$reference."-".$client_ref;
    // $mail = new PHPMailer(true);
    // // SMTP settings
    // $mail->isSMTP();
    // $mail->Host       = "mail.smtp2go.com";
    // $mail->SMTPAuth   = true;
    // $mail->Username   = "luntian4518";
    // $mail->Password   = "ODZ1o6Ia4pctLUJ3";
    // $mail->SMTPSecure = "tls";
    // $mail->Port       = 2525;

    // $mail->setFrom(
    //     "admin@luntian.com.au",
    //     "Luntian"
    // );
    // $mail->addAddress("admin@luntian.com.au");
    // // $mail->addAddress("ronnel.navarro2020@gmail.com");
    // // Embed local logo
    // $logoPath = "../../img/emailLOGO.png"; 
    // if (file_exists($logoPath)) {
    //   $mail->AddEmbeddedImage($logoPath, "logo_cid");
    // }

    // // Email format
    // $mail->isHTML(true);
    // $mail->Subject = $subject;
    // $mail->Body = '
    //   <div style="font-family: Arial, sans-serif; text-align:center; padding:20px;">
    //     <img src="cid:logo_cid" alt="Logo" style="height:60px;">
    //     <h2 style="margin-top:20px;">Hi there!</h2>
    //     <p style="font-size:16px; font-weight:bold; color:#ff9800;">' . htmlspecialchars($reference) . '</p>
    //     <p style="margin:5px 0;">Status has been updated to</p>
    //     <p style="font-size:16px; font-weight:bold; color:#ff9800;">Allocated</p>

    //     <div style="margin-top:30px; text-align:center;">
    //       <h4>Submission Notes:</h4>
    //       <p>
    //         Click or copy & paste link to browser to access NatHERS Climate Zone Map<br>
    //         <a href="http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html" target="_blank">
    //           http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html
    //         </a>
    //       </p>
    //     </div>
    //   </div>
    // ';

    // // Send Email
    // $mail->send();

    if (mysqli_query($conn, $sql)) {
      echo json_encode(["status"=>"success","message"=>"New job has been created successfully, files uploaded."]);
    } else {
      echo json_encode(["status"=>"error","message"=>"Database error: " . mysqli_error($conn)]);
    }
}
?>
