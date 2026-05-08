<?php
session_start();
include '../../database/db.php';
if (file_exists(__DIR__ . '/../../config/slack.php')) {
    require __DIR__ . '/../../config/slack.php';
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function sendSlackTimeInNotification($webhookUrl, $name)
{
    if (empty($webhookUrl)) {
        return false;
    }

    try {
        $timezone = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $timezone);
        $dateValue = $now->format('Y-m-d');
        $timeInValue = $now->format('h:i A');
        $safeName = trim((string)$name);
        if ($safeName === '') {
            $safeName = 'Unknown';
        }

        $payload = [
            'text' => "*Time In Alert*\nName: {$safeName}\nDate: {$dateValue}\nTime In: {$timeInValue}",
        ];

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_exec($ch);
        curl_close($ch);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function ensureTimeInNotificationsTable($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS time_in_notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        time_in_date DATE NOT NULL,
        time_in_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_date (user_id, time_in_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

function hasTimeInForDate($conn, $userId, $dateValue)
{
    $stmt = $conn->prepare('SELECT id FROM time_in_notifications WHERE user_id = ? AND time_in_date = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $userId, $dateValue);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row);
}

function saveTimeInForDate($conn, $userId, $dateValue, $dateTimeValue)
{
    $stmt = $conn->prepare('INSERT INTO time_in_notifications (user_id, time_in_date, time_in_at) VALUES (?, ?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iss', $userId, $dateValue, $dateTimeValue);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function logLoginActivity($conn, $userId, $userName, $action, $description)
{
    if (!$conn || $userId <= 0) {
        return false;
    }
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $entityType = 'auth';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isssiss', $userId, $userName, $action, $entityType, $userId, $description, $ipAddress);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$loginAction = strtolower(trim((string)($_POST['action'] ?? 'login')));
$slackWebhookUrl = '';
if (defined('SLACK_TIMEIN_WEBHOOK_URL')) {
    $slackWebhookUrl = (string)SLACK_TIMEIN_WEBHOOK_URL;
}
if ($loginAction !== 'timein') {
    $loginAction = 'login';
}

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
    exit;
}

// Only allow @luntiands.com email addresses
$allowedDomain = 'luntiands.com';
$emailLower = strtolower($email);
if (substr($emailLower, -strlen($allowedDomain) - 1) !== '@' . $allowedDomain) {
    echo json_encode(['status' => 'error', 'message' => 'Access is restricted to @luntiands.com email addresses only.']);
    exit;
}

$hashedPassword = md5($password);

$stmt = $conn->prepare('SELECT id, email, password, role FROM user_login WHERE email = ? AND password = ? LIMIT 1');
$stmt->bind_param('ss', $email, $hashedPassword);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $userId = (int)($user['id'] ?? 0);
    $userEmail = $user['email'] ?? '';
    if ($userEmail !== '') {
        $empStmt = $conn->prepare('SELECT status FROM employees WHERE email = ? LIMIT 1');
        if ($empStmt) {
            $empStmt->bind_param('s', $userEmail);
            $empStmt->execute();
            $empRow = $empStmt->get_result()->fetch_assoc();
            $empStmt->close();
            if ($empRow && strtolower((string)($empRow['status'] ?? '')) !== 'active') {
                echo json_encode(['status' => 'error', 'message' => 'Your account is inactive. Please contact HR.']);
                exit;
            }
        }
    }

    $displayName = 'Unknown';
    if ($userEmail !== '') {
        $nameStmt = $conn->prepare('SELECT full_name FROM employees WHERE email = ? LIMIT 1');
        if ($nameStmt) {
            $nameStmt->bind_param('s', $userEmail);
            $nameStmt->execute();
            $nameRow = $nameStmt->get_result()->fetch_assoc();
            $nameStmt->close();
            if ($nameRow && !empty(trim((string)($nameRow['full_name'] ?? '')))) {
                $displayName = trim($nameRow['full_name']);
            } else {
                $displayName = $userEmail;
            }
        }
    }
    if (
        $loginAction === 'timein' &&
        strtolower((string)($user['role'] ?? '')) === 'employee'
    ) {
        $timezone = new DateTimeZone('Asia/Manila');
        $now = new DateTime('now', $timezone);
        $today = $now->format('Y-m-d');
        $nowDateTime = $now->format('Y-m-d H:i:s');

        ensureTimeInNotificationsTable($conn);

        if (hasTimeInForDate($conn, $userId, $today)) {
            $nextReset = (new DateTime('tomorrow', $timezone))->format('Y-m-d 00:00:00');
            echo json_encode([
                'status' => 'error',
                'message' => 'You have already timed in today. Please try again after 12:00 AM.',
                'action' => 'timein',
                'already_timed_in' => true,
                'next_reset' => $nextReset,
            ]);
            exit;
        }

        if (!saveTimeInForDate($conn, $userId, $today, $nowDateTime)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Could not save your Time In. Please try again.',
                'action' => 'timein',
            ]);
            exit;
        }

        logLoginActivity($conn, $userId, $displayName, 'Time In', "Recorded daily time in for $today");

        // Non-blocking notification: time-in should still succeed even if Slack is down.
        sendSlackTimeInNotification($slackWebhookUrl, $displayName);

        echo json_encode([
            'status' => 'success',
            'message' => 'Time In recorded. Notification sent to Slack.',
            'action' => 'timein',
            'already_timed_in' => false,
            'time_in_date' => $today,
            'disable_until' => $now->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
        ]);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? 'employee';
    $_SESSION['last_activity'] = time();
    $_SESSION['name'] = $displayName;
    unset($_SESSION['admin_module']);
    unset($_SESSION['employee_module']);
    $_SESSION['login_cache_buster'] = bin2hex(random_bytes(8));

    // Update last login timestamp (if column exists)
    $cc = $conn->query("SHOW COLUMNS FROM user_login LIKE 'last_login'");
    if ($cc && $cc->num_rows > 0) {
        $conn->query("UPDATE user_login SET last_login = NOW() WHERE id = " . (int)$user['id']);
    }

    $defaultPasswords = ['password123', '123456789', 'password', 'admin123', '123456'];
    $isDefaultPassword = in_array(strtolower($password), array_map('strtolower', $defaultPasswords));
    if (!$isDefaultPassword && (preg_match('/^[0-9]{6,}$/', $password) || strlen($password) <= 6)) {
        $isDefaultPassword = true;
    }
    $_SESSION['is_default_password'] = $isDefaultPassword;
    logLoginActivity($conn, $userId, $displayName, 'Login', 'User logged in successfully');

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'role' => $_SESSION['role'],
        'is_default_password' => $isDefaultPassword,
        'cache_buster' => $_SESSION['login_cache_buster'],
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}
