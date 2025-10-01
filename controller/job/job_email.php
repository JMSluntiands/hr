<?php
include '../../database/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php');

session_start();

date_default_timezone_set('Asia/Manila');
$localDatetime = date("Y-m-d H:i:s");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail       = $_POST['toEmail'] ?? '';
    $reference     = $_POST['reference'] ?? '';
    $status        = $_POST['status'] ?? '';
    $assessor      = $_POST['assessor'] ?? '';
    $assessorEmail = $_POST['assessorEmail'] ?? '';

    if (!$toEmail) {
        echo json_encode(['success' => false, 'message' => 'Recipient email required']);
        exit;
    }

    // 🔎 Fetch job details
    $sql = "
      SELECT j.*, c.client_account_name 
      FROM jobs j
      LEFT JOIN client_accounts c ON j.client_account_id = c.client_account_id
      WHERE j.job_reference_no = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No job found with reference: ' . htmlspecialchars($reference)]);
        exit;
    }

    $client_account_name     = $row['client_account_name'];
    $reference               = $row['job_reference_no'];
    $client_reference_number = $row['client_reference_no'];
    $client_code             = $row['client_code'] ?? 'NOCLIENT';

    $subject = "Job Update : ".$client_account_name." ".$reference."-".$client_reference_number;

    try {
        $mail = new PHPMailer(true);

        // ✅ Generate Job Info PDF (styled)
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Luntian');
        $pdf->SetTitle('Job Information');
        $pdf->SetMargins(15, 20, 15);
        $pdf->AddPage();

        $html = '
        <h2 style="text-align:center; color:#ff9800; font-weight:bold;">
            LBS'.$row['job_id'].'_BASE_'.$row['client_code'].'
        </h2>
        <style>
          .label { font-weight:bold; color:#555; width:150px; display:inline-block; }
          .value { color:#000; }
          .row { margin-bottom:4px; }
        </style>

        <div>
          <div class="row"><span class="label">LBS Ref#:</span> <span class="value">'.$row['job_id'].'</span></div>
          <div class="row"><span class="label">Client Ref#:</span> <span class="value">'.$row['client_reference_no'].'</span></div>
          <div class="row"><span class="label">Account Client:</span> <span class="value">'.$client_account_name.'</span></div>
          <div class="row"><span class="label">NCC Compliance:</span> <span class="value">'.$row['ncc_compliance'].'</span></div>
          <div class="row"><span class="label">Job Type:</span> <span class="value">'.$row['job_type'].'</span></div>
          <div class="row"><span class="label">Storey:</span> <span class="value">'.$row['dwelling'].'</span></div>
          <div class="row"><span class="label">Priority:</span> <span class="value">'.$row['priority'].'</span></div>
          <div class="row"><span class="label">Plan Complexity:</span> <span class="value">'.$row['plan_complexity'].'</span></div>
          <div class="row"><span class="label">Job Status:</span> <span class="value">'.$status.'</span></div>
          <div class="row"><span class="label">Assessor:</span> <span class="value">'.$assessor.' ('.$assessorEmail.')</span></div>
          <div class="row"><span class="label">Staff ID:</span> <span class="value">'.$row['staff_id'].'</span></div>
          <div class="row"><span class="label">Checker ID:</span> <span class="value">'.$row['checker_id'].'</span></div>
          <div class="row"><span class="label">Run Notes:</span> <span class="value">'.$row['notes'].'</span></div>
          <div class="row"><span class="label">Start Date:</span> <span class="value">'.$row['log_date'].'</span></div>
          <div class="row"><span class="label">Completion Date:</span> <span class="value">'.$row['completion_date'].'</span></div>
          <div class="row"><span class="label">Last Update:</span> <span class="value">'.$row['last_update'].'</span></div>
          <div class="row"><span class="label">Updated By:</span> <span class="value">'.$row['updated_by'].'</span></div>
        </div>
        ';

        $pdf->writeHTML($html, true, false, true, false, '');
        $filename = $client_code.'_'.$reference.'.pdf';
        $pdfPath = __DIR__ . '/../../document/'.$filename;
        $pdf->Output($pdfPath, 'F');

        // ✅ Mail setup
        $mail->isSMTP();
        $mail->Host       = "mail.smtp2go.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "luntian4518";
        $mail->Password   = "ODZ1o6Ia4pctLUJ3";
        $mail->SMTPSecure = "tls";
        $mail->Port       = 2525;

        $mail->setFrom("admin@luntian.com.au", "Luntian");
        $mail->addAddress($toEmail);
        $mail->addCC("admin@luntian.com.au");

        // ✅ Attach generated PDF
        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        // ✅ Attach latest uploaded files
        $stmt = $conn->prepare("
            SELECT suf.files_json
            FROM jobs j
            LEFT JOIN staff_uploaded_files suf ON suf.job_id = j.job_id
            WHERE j.job_reference_no = ?
            ORDER BY suf.uploaded_at DESC, suf.file_id DESC
            LIMIT 1
        ");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $stmt->bind_result($filesJson);
        if ($stmt->fetch()) {
            $files = json_decode($filesJson, true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    $filePath = __DIR__ . "/../../document/$reference/$file";
                    if (file_exists($filePath)) {
                        $mail->addAttachment($filePath);
                    }
                }
            }
        }
        $stmt->close();

        // ✅ Embed logo
        $logoPath = "../../img/emailLOGO.png"; 
        if (file_exists($logoPath)) {
            $mail->AddEmbeddedImage($logoPath, "logo_cid");
        }

        // ✅ Email format
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
          <div style="font-family: Arial, sans-serif; text-align:center; padding:20px;">
            <img src="cid:logo_cid" alt="Logo" style="height:60px;">
            <h2 style="margin-top:20px;">Hi there!</h2>
            <p style="font-size:16px; font-weight:bold; color:#ff9800;">' . htmlspecialchars($reference) . '</p>
            <p style="margin:5px 0;">Status has been updated to</p>
            <p style="font-size:16px; font-weight:bold; color:#ff9800;">' . htmlspecialchars($status) . '</p>
            <div style="margin-top:20px;">
              <p>Assessor: <b>' . htmlspecialchars($assessor) . '</b></p>
              <p>Assessor Email: <a href="mailto:' . htmlspecialchars($assessorEmail) . '">' . htmlspecialchars($assessorEmail) . '</a></p>
            </div>
          </div>
        ';
        $mail->AltBody = "Job Reference: $reference\nStatus: $status\nAssessor: $assessor\nEmail: $assessorEmail";

        // ✅ Update job status
        $update = $conn->prepare("UPDATE jobs SET job_status = 'Completed', completion_date = ? WHERE job_reference_no = ?");
        $update->bind_param("ss", $localDatetime, $reference);
        $update->execute();

        // ✅ Send email
        $mail->send();

        if ($update->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully! Job marked as Completed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email sent, but job status not updated.']);
        }

        $update->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email failed: {$mail->ErrorInfo}"]);
    }
}
?>
