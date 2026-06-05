<?php

$mailErrorMessage = '';

if (!function_exists('setMailErrorMessage')) {
    function setMailErrorMessage(string $message): void
    {
        $GLOBALS['mailErrorMessage'] = $message;
    }
}

if (!function_exists('getLastMailError')) {
    function getLastMailError(): string
    {
        return (string)($GLOBALS['mailErrorMessage'] ?? '');
    }
}

if (!function_exists('getMailConfigValue')) {
    function getMailConfigValue(array $config, string $key, string $default = ''): string
    {
        $envValue = getenv($key);
        if ($envValue !== false && trim((string)$envValue) !== '') {
            return trim((string)$envValue);
        }
        if (!empty($config[$key])) {
            return trim((string)$config[$key]);
        }
        return $default;
    }
}

if (!function_exists('loadMailConfig')) {
    function loadMailConfig(): array
    {
        $configPath = __DIR__ . '/../../config/mail.php';
        if (file_exists($configPath)) {
            $mailConfig = include $configPath;
            if (is_array($mailConfig)) {
                return $mailConfig;
            }
        }
        return [];
    }
}

// Define once even if this file is included multiple times.
if (!function_exists('sendTemporaryPasswordEmail')) {
    function sendTemporaryPasswordEmail(string $toEmail, string $toName, string $plainPassword): bool
    {
        setMailErrorMessage('');
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            setMailErrorMessage('Composer autoload is missing. Run composer install.');
            return false;
        }
        require_once $autoloadPath;

        $cfg = loadMailConfig();
        $smtpHost = getMailConfigValue($cfg, 'MAIL_HOST', 'smtp.gmail.com');
        $smtpPort = (int)getMailConfigValue($cfg, 'MAIL_PORT', '587');
        $smtpUser = getMailConfigValue($cfg, 'MAIL_USERNAME', '');
        $smtpPass = getMailConfigValue($cfg, 'MAIL_PASSWORD', '');
        $fromEmail = getMailConfigValue($cfg, 'MAIL_FROM_EMAIL', $smtpUser);
        $fromName = getMailConfigValue($cfg, 'MAIL_FROM_NAME', 'HR System');
        $encryption = strtolower(getMailConfigValue($cfg, 'MAIL_ENCRYPTION', 'tls'));

        if ($smtpUser === '' || $smtpPass === '') {
            setMailErrorMessage('SMTP config missing. Set MAIL_USERNAME and MAIL_PASSWORD in config/mail.php.');
            return false;
        }
        if (stripos($smtpUser, 'yourgmail@gmail.com') !== false || stripos($smtpPass, 'your-app-password') !== false) {
            setMailErrorMessage('SMTP config still uses placeholder values. Update config/mail.php with real Gmail credentials.');
            return false;
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPAutoTLS = true;

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($fromEmail, $fromName);
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
            setMailErrorMessage('SMTP send failed: ' . $e->getMessage());
            error_log('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }
}

