<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$configPath = dirname(__DIR__, 2) . '/config/google-oauth.php';
if (!is_file($configPath)) {
    header('Location: ../../index.php?error=google_not_configured');
    exit;
}

$config = require $configPath;
$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    header('Location: ../../index.php?error=google_denied');
    exit;
}

if (empty($code)) {
    header('Location: ../../index.php?error=google_no_code');
    exit;
}

// Exchange code for tokens
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code'          => $code,
    'client_id'     => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri'  => $config['redirect_uri'],
    'grant_type'    => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST       => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: ../../index.php?error=google_token_failed');
    exit;
}

$tokenJson = json_decode($tokenResponse, true);
$accessToken = $tokenJson['access_token'] ?? '';
if (empty($accessToken)) {
    header('Location: ../../index.php?error=google_token_failed');
    exit;
}

// Get user info
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);
$email = isset($userInfo['email']) ? trim(strtolower($userInfo['email'])) : '';

if (empty($email)) {
    header('Location: ../../index.php?error=google_no_email');
    exit;
}

// Only allow @luntiands.com
$allowedDomain = 'luntiands.com';
if (substr($email, -strlen($allowedDomain) - 1) !== '@' . $allowedDomain) {
    header('Location: ../../index.php?error=domain_restricted');
    exit;
}

include dirname(__DIR__, 2) . '/database/db.php';

$stmt = $conn->prepare('SELECT id, email, role FROM user_login WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: ../../index.php?error=no_hr_account');
    exit;
}

$userEmail = $user['email'] ?? '';
if ($userEmail !== '') {
    $empStmt = $conn->prepare('SELECT status FROM employees WHERE email = ? LIMIT 1');
    if ($empStmt) {
        $empStmt->bind_param('s', $userEmail);
        $empStmt->execute();
        $empRow = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();
        if ($empRow && strtolower((string)($empRow['status'] ?? '')) !== 'active') {
            header('Location: ../../index.php?error=account_inactive');
            exit;
        }
    }
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'] ?? 'employee';
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
$_SESSION['name'] = $displayName;
$_SESSION['is_default_password'] = false;
unset($_SESSION['admin_module']);
session_regenerate_id(true);
$_SESSION['login_cache_buster'] = bin2hex(random_bytes(8));

if (strtolower((string)$_SESSION['role']) === 'admin') {
    header('Location: ../../admin/module-select.php?cb=' . urlencode($_SESSION['login_cache_buster']));
    exit;
}

header('Location: ../../employee/index.php?cb=' . urlencode($_SESSION['login_cache_buster']));
exit;
