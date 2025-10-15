<?php
include '../../database/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require '../../vendor/autoload.php';
session_start();

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jreference  = $_POST['jreference'] ?? '';
    $reference   = $_POST['reference'] ?? '';
    $client_account  = $_POST['client_account'] ?? '';
    $client_ref  = $_POST['client_ref'] ?? '';
    $assigned    = $_POST['assigned'] ?? '';
    $checked     = $_POST['checked'] ?? '';
    $compliance  = $_POST['compliance'] ?? '';
    $priority    = $_POST['priority'] ?? '';
    $jobRequest  = $_POST['jobRequest'] ?? '';
    $address     = $_POST['address'] ?? '';
    $clientID    = $_POST['client_account_id'] ?? '';
    $notes       = $_POST['notes'] ?? '';
    $status      = $_POST['status'] ?? 'Allocated';
    $log_date    = $_POST['log_date'] ?? date("Y-m-d H:i:s");
    $user_role   = $_SESSION['role'] ?? '';

    // 🔍 Get client info
    $client_code = null;
    $client_email = null;
    if (!empty($user_role)) {
        $sql_client = "SELECT client_code, client_email FROM clients WHERE client_name = '".mysqli_real_escape_string($conn, $user_role)."' LIMIT 1";
        $result_client = mysqli_query($conn, $sql_client);
        if ($row = mysqli_fetch_assoc($result_client)) {
            $client_code = $row['client_code'];
            $client_email = $row['client_email'];
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
            echo json_encode(["status"=>"error","message"=>"No job request found for ID: $jobRequest"]);
            exit;
        }
    } else {
        echo json_encode(["status"=>"error","message"=>"No jobRequest provided"]);
        exit;
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

    // 🔍 Normalize address for duplicate checking
    $normalizedAddress = strtolower(preg_replace('/\s+/', '', $address));
    $sql_check_address = "
        SELECT COUNT(*) as cnt 
        FROM jobs 
        WHERE REPLACE(LOWER(address_client), ' ', '') = '" . mysqli_real_escape_string($conn, $normalizedAddress) . "'
        AND job_status != 'Completed'
    ";
    $result_addr = mysqli_query($conn, $sql_check_address);
    $row_addr = mysqli_fetch_assoc($result_addr);

    if ($row_addr['cnt'] > 0) {
        echo json_encode(["status" => "error", "message" => "Duplicate job address found (ignoring spaces) for active/incomplete job."]);
        exit;
    }


    // 🔁 Duplicate reference check
    $sql_check = "SELECT COUNT(*) as cnt FROM jobs WHERE job_reference_no = '".mysqli_real_escape_string($conn, $reference)."'";
    $result_check = mysqli_query($conn, $sql_check);
    $row = mysqli_fetch_assoc($result_check);
    if ($row['cnt'] > 0) {
        echo json_encode(["status"=>"error","message"=>"Duplicate found: job_reference_no already exists."]);
        exit;
    }

    // 📁 File upload setup
    $folderName = $reference ?: (!empty($client_ref) ? $client_ref : "AUTO_" . time());
    $uploadDir = "../../document/" . $folderName . "/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $plansSaved = [];
    $docsSaved  = [];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    // 📄 Save Plans
    if (!empty($_FILES['plans']['name'])) {
        foreach ($_FILES['plans']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['plans']['error'][$key] == 4) continue;
            if ($_FILES['plans']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"File '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['plans']['tmp_name'][$key];
            $safeName = $filename;
            $targetFile = $uploadDir . $safeName;
            if (move_uploaded_file($tmpName, $targetFile)) {
                $plansSaved[] = $safeName;
            } else {
                echo json_encode(["status"=>"error","message"=>"Failed to move plan: $filename"]); exit;
            }
        }
    }

    // 📄 Save Docs
    if (!empty($_FILES['docs']['name'])) {
        foreach ($_FILES['docs']['name'] as $key => $filename) {
            if (empty($filename) || $_FILES['docs']['error'][$key] == 4) continue;
            if ($_FILES['docs']['size'][$key] > $maxFileSize) {
                echo json_encode(["status"=>"error","message"=>"File '$filename' exceeds 10MB."]); exit;
            }
            $tmpName = $_FILES['docs']['tmp_name'][$key];
            $safeName = $filename;
            $targetFile = $uploadDir . $safeName;
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

    // Escape fields
    $client_code = mysqli_real_escape_string($conn, $client_code);
    $reference   = mysqli_real_escape_string($conn, $reference);
    $jreference  = mysqli_real_escape_string($conn, $jreference);
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
    $client_account = mysqli_real_escape_string($conn, $client_account);
    $clientID    = mysqli_real_escape_string($conn, $clientID);

    if (!empty($client_account)) $client_code = $client_account;

    // ✅ Insert job
    $sql = "INSERT INTO jobs (
        reference, client_code, job_reference_no, client_reference_no, 
        staff_id, checker_id, ncc_compliance, priority, job_request_id, 
        address_client, log_date, job_type, job_status, client_account_id, 
        notes, upload_files, upload_project_files
    ) VALUES (
        '$jreference', '$client_code', '$reference', '$client_ref', 
        '$assigned', '$checked', '$compliance', '$priority', '$jobRequest', 
        '$address', '$log_date', '$job_request_type', '$status', NULLIF('$clientID',''), 
        '$notes', '$plansJson', '$docsJson'
    )";

    // 🔍 Client account name
    $clientName = mysqli_query($conn, "SELECT * FROM client_accounts WHERE client_account_id = '$clientID'");
    $cai = mysqli_fetch_assoc($clientName);
    $client_account_name = $cai['client_account_name'] ?? 'Unknown';

    // 🧾 PDF Layout
    $pdfHTML = '
    <!DOCTYPE html>
    <html><head>
      <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { padding: 20px; }
        .header { display:flex; justify-content:space-between; align-items:center; }
        .title { font-size:20px; font-weight:bold; }
        .section-title { font-size:16px; font-weight:bold; margin-top:20px; color:#333; }
        .field-label { font-weight:bold; width:200px; vertical-align:top; }
        .field-value { color:#555; }
        table { width:100%; border-collapse:collapse; }
        td { padding:6px 0; }
        .badge { background:#e0e0e0; padding:4px 10px; border-radius:6px; font-size:13px; }
        .upload-box { display:flex; align-items:center; border:1px solid #ccc; border-radius:6px; padding:8px; margin-top:6px; }
        .upload-icon { width:20px; height:20px; background:#6c63ff; color:white; text-align:center; line-height:20px; border-radius:4px; font-weight:bold; margin-right:8px; }
      </style>
    </head>
    <body>
      <div class="container">
        <div class="header">
          <div class="title">Customer Details</div>
          <div style="font-size:13px;">'.date("l, F j, Y").'</div>
        </div>

        <div class="section-title">Customer Details</div>
        <table>
          <tr><td class="field-label">LBS Ref#</td><td class="field-value">'.$reference.'</td></tr>
          <tr><td class="field-label">Client Ref#</td><td class="field-value">'.$client_ref.'</td></tr>
          <tr><td class="field-label">Account Client</td><td class="field-value"><span class="badge">'.$client_account_name.'</span></td></tr>
          <tr><td class="field-label">NCC Compliance</td><td class="field-value"><span class="badge">'.$compliance.'</span></td></tr>
        </table>

        <div class="section-title">Job Details</div>
        <table>
          <tr><td class="field-label">Job Address</td><td class="field-value">'.$address.'</td></tr>
          <tr><td class="field-label">Job Type</td><td class="field-value"><span class="badge">'.$job_request_type.'</span></td></tr>
          <tr><td class="field-label">Priority</td><td class="field-value"><span class="badge">'.$priority.'</span></td></tr>
          <tr><td class="field-label">Upload Plans</td>
              <td class="field-value"><div class="upload-box"><div class="upload-icon">📄</div>'.
              (!empty($plansSaved) ? htmlspecialchars($plansSaved[0]) : 'No file uploaded').'</div></td></tr>
          <tr><td class="field-label">Staff Initials</td><td class="field-value">'.$assigned.'</td></tr>
          <tr><td class="field-label">Job Status</td><td class="field-value"><span class="badge">'.$status.'</span></td></tr>
        </table>
      </div>
    </body></html>';

    // 🧾 Generate PDF
    $dompdf->loadHtml($pdfHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    // ✅ Ensure temp folder exists
    $tempDir = "../../temp/";
    if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

    // Save PDF
    $pdfPath = $tempDir . "job_summary_" . $reference . ".pdf";
    file_put_contents($pdfPath, $pdfOutput);

    // 📧 Email setup
    $subject = $user_role." Job Submission: ".$client_account_name." ".$user_role.$reference."-".$client_ref."-".$compliance;
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = "mail.smtp2go.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "luntian4518";
        $mail->Password   = "ODZ1o6Ia4pctLUJ3";
        $mail->SMTPSecure = "tls";
        $mail->Port       = 2525;

        $mail->setFrom("admin@luntian.com.au", "Luntian");
        $mail->addAddress($client_email);

        $logoPath = "../../img/emailLOGO.png"; 
        if (file_exists($logoPath)) $mail->AddEmbeddedImage($logoPath, "logo_cid");

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
          <div style="font-family: Arial; background-color:#f8f9fa; padding:30px;">
            <div style="max-width:600px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:8px;">
              <div style="background-color:#f57c00; color:#fff; padding:15px; text-align:center; font-size:18px; font-weight:bold;">
                '.htmlspecialchars($reference).'_BASE_'.htmlspecialchars($client_ref).'
              </div>
              <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">LBS Reff</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($reference).'</td></tr>
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Client Reff</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($client_ref).'</td></tr>
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Account Client</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($client_account_name).'</td></tr>
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">NCC Compliance</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($compliance).'</td></tr>
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Job Type</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($job_request_type).'</td></tr>
                <tr><td style="padding:8px; border:1px solid #ddd; font-weight:bold;">Priority</td><td style="padding:8px; border:1px solid #ddd;">'.htmlspecialchars($priority).'</td></tr>
              </table>
            </div>
          </div>';

        $mail->addAttachment($pdfPath, "Job_Summary_".$reference.".pdf");
        $mail->send();

        // 🧹 Delete temp file after send
        if (file_exists($pdfPath)) unlink($pdfPath);
    } catch (Exception $e) {
        echo json_encode(["status"=>"error","message"=>"Mailer Error: ".$mail->ErrorInfo]);
        exit;
    }

    // ✅ Save job record
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["status"=>"success","message"=>"Job created, email sent successfully."]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Database error: ".mysqli_error($conn)]);
    }
}
?>
