<?php
/**
 * Google OAuth config. Copy this file to google-oauth.php and fill in your values.
 * Do not commit google-oauth.php (it contains the client secret).
 */
return [
    'client_id'     => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri'  => 'http://localhost/hr/controller/login/google-callback.php', // Change to your app URL
];
