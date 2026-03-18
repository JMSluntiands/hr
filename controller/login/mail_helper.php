<?php

// Define once even if this file is included multiple times.
if (!function_exists('sendTemporaryPasswordEmail')) {
    /**
     * Send temporary password email.
     * - If Composer/PHPMailer is available, use SMTP via PHPMailer.
     * - Otherwise fall back to PHP mail() so the app does not crash.
     */
    function sendTemporaryPasswordEmail(string $toEmail, string $toName, string $plainPassword): bool
    {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';

        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;

            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'yourgmail@gmail.com';      // TODO: palitan sa tunay na SMTP account
                $mail->Password = 'your-app-password';        // TODO: gamitin ang Gmail App Password o SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@yourdomain.com', 'HR System');
                $mail->addAddress($toEmail, $toName);

                $mail->Subject = 'Your HR system login credentials';
                $body  = "Dear {$toName},\n\n";
                $body .= "Your HR system account has been created.\n\n";
                $body .= "Login email: {$toEmail}\n";
                $body .= "Temporary password: {$plainPassword}\n\n";
                $body .= "For security, please log in and change this password immediately after your first login.\n\n";
                $body .= "Thank you.";
                $mail->Body = $body;

                $mail->send();
                return true;
            } catch (\Throwable $e) {
                // If PHPMailer fails, fall through to mail() fallback below.
            }
        }

        // Fallback: built-in mail()
        $subject = 'Your HR system login credentials';
        $body  = "Dear {$toName},\n\n";
        $body .= "Your HR system account has been created.\n\n";
        $body .= "Login email: {$toEmail}\n";
        $body .= "Temporary password: {$plainPassword}\n\n";
        $body .= "For security, please log in and change this password immediately after your first login.\n\n";
        $body .= "Thank you.";

        $headers = "From: no-reply@yourdomain.com\r\n"
                 . "Reply-To: no-reply@yourdomain.com\r\n"
                 . "X-Mailer: PHP/" . phpversion();

        @mail($toEmail, $subject, $body, $headers);
        return true;
    }
}

