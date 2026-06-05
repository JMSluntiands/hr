<?php

return [
    'allowed_email_domain' => env('HR_ALLOWED_EMAIL_DOMAIN', 'luntiands.com'),
    'session_timeout_seconds' => (int) env('HR_SESSION_TIMEOUT', 300),
    'legacy_path' => base_path('legacy'),
    'uploads_path' => base_path('uploads'),
    'google_oauth' => base_path('legacy/config/google-oauth.php'),
    'slack_config' => base_path('legacy/config/slack.php'),
    'privacy_policy_version' => env('HR_PRIVACY_POLICY_VERSION', '1'),
    'privacy_policy_last_updated' => env('HR_PRIVACY_POLICY_LAST_UPDATED', 'June 2026'),
];
