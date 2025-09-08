<?php
include '../../database/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

session_start();

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

    $sql = "
      SELECT j.*, c.client_account_name
      FROM jobs j
      LEFT JOIN client_accounts c 
            ON j.client_account_id = c.client_account_id
      WHERE j.job_reference_no = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $client_account_name     = $row['client_account_name'];
    $reference               = $row['job_reference_no'];
    $client_reference_number = $row['client_reference_no'];
    $subject = "Job Update : ".$client_account_name." ".$reference."-".$client_reference_number;
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = "mail.smtp2go.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "luntian4518";
        $mail->Password   = "ODZ1o6Ia4pctLUJ3";
        $mail->SMTPSecure = "tls";
        $mail->Port       = 2525;

        $mail->setFrom(
            "admin@luntian.com.au",
            "Luntian"
        );
        $mail->addAddress($toEmail);

        // Embed local logo
        $logoPath = "../../img/emailLOGO.png"; 
        if (file_exists($logoPath)) {
            $mail->AddEmbeddedImage($logoPath, "logo_cid");
        }

        // // Attach latest PDF file
        // $stmt = $conn->prepare("
        //     SELECT suf.files_json
        //     FROM jobs j
        //     LEFT JOIN staff_uploaded_files suf 
        //            ON suf.job_id = j.job_id
        //     WHERE j.job_reference_no = ?
        //     ORDER BY suf.uploaded_at DESC, suf.file_id DESC
        //     LIMIT 1
        // ");
        // $stmt->bind_param("s", $reference);
        // $stmt->execute();
        // $stmt->bind_result($filesJson);
        // if ($stmt->fetch()) {
        //     $files = json_decode($filesJson, true);
        //     if (is_array($files)) {
        //         foreach ($files as $file) {
        //             $filePath = __DIR__ . "/../../document/$reference/$file";
        //             if (file_exists($filePath)) {
        //                 $mail->addAttachment($filePath);
        //             }
        //         }
        //     }
        // }
        // $stmt->close();

        // Email format
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

            <div style="margin-top:30px; text-align:center;">
              <h4>Submission Notes:</h4>
              <p>
                Click or copy & paste link to browser to access NatHERS Climate Zone Map<br>
                <a href="http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html" target="_blank">
                  http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html
                </a>
              </p>
            </div>
          </div>
        ';

        $mail->AltBody = "Job Reference: $reference\nStatus: $status\nAssessor: $assessor\nEmail: $assessorEmail";

        // Send Email
        $mail->send();

        // ✅ Update job status to Completed
        $update = $conn->prepare("UPDATE jobs SET job_status = 'Completed' WHERE job_reference_no = ?");
        $update->bind_param("s", $reference);
        $update->execute();
        $update->close();

        echo json_encode(['success' => true, 'message' => 'Email sent successfully! Job marked as Completed.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email failed: {$mail->ErrorInfo}"]);
    }
}
?>
