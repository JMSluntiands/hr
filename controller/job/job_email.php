<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; // kung composer
// or require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/SMTP.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail   = $_POST['toEmail'] ?? '';
    $reference = $_POST['reference'] ?? '';
    $status    = $_POST['status'] ?? '';
    $assessor  = $_POST['assessor'] ?? '';
    $assessorEmail = $_POST['assessorEmail'] ?? '';

    if (!$toEmail) {
        echo json_encode(['success' => false, 'message' => 'Recipient email required']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP settings for SMTP2GO
        $mail->isSMTP();
        $mail->Host       = "mail.smtp2go.com"; 
        $mail->SMTPAuth   = true;
        $mail->Username   = "YOUR_SMTP2GO_USERNAME"; 
        $mail->Password   = "YOUR_SMTP2GO_PASSWORD"; 
        $mail->SMTPSecure = "tls"; 
        $mail->Port       = 2525; // or 587, 8025, depende sa SMTP2GO

        $mail->setFrom("no-reply@yourdomain.com", "Your System");
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Job Update - Reference #{$reference}";

        // same format as modal
        $mail->Body = '
          <div style="font-family: Arial, sans-serif; text-align:center; padding:20px;">
            <img src="https://yourdomain.com/img/logo-light.png" alt="Logo" style="height:60px;">
            <h2 style="margin-top:20px;">Hi there!</h2>
            <p style="font-size:16px; font-weight:bold; color:#ff9800;">' . htmlspecialchars($reference) . '</p>
            <p style="margin:5px 0;">status has been updated to</p>
            <p style="font-size:16px; font-weight:bold; color:#ff9800;">' . htmlspecialchars($status) . '</p>

            <div style="margin-top:20px;">
              <p>Assessor: <b>' . htmlspecialchars($assessor) . '</b></p>
              <p>Assessor Email: <a href="mailto:' . htmlspecialchars($assessorEmail) . '">' . htmlspecialchars($assessorEmail) . '</a></p>
            </div>

            <div style="margin-top:30px;">
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

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email failed: {$mail->ErrorInfo}"]);
    }
}
?>
