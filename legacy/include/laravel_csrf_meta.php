<?php
/**
 * CSRF meta tag for legacy pages that use fetch/AJAX through Laravel routes.
 */
if (! defined('HR_LEGACY_EMBEDDED') || ! function_exists('csrf_token')) {
    return;
}

$token = csrf_token();
if ($token === '') {
    return;
}

echo '<meta name="csrf-token" content="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
