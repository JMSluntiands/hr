<?php
session_start();

$configPath = dirname(__DIR__, 2) . '/config/google-oauth.php';
if (!is_file($configPath)) {
    header('Location: ../../index.php?error=google_not_configured');
    exit;
}

$config = require $configPath;
$clientId = $config['client_id'] ?? '';
$redirectUri = $config['redirect_uri'] ?? '';

if (empty($clientId) || empty($redirectUri)) {
    header('Location: ../../index.php?error=google_not_configured');
    exit;
}

$params = [
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'    => 'online',
    'prompt'        => 'select_account',
];
$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $url);
exit;
