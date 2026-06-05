<?php
/**
 * Hidden CSRF field for legacy forms POSTing through Laravel routes.
 */
if (! defined('HR_LEGACY_EMBEDDED') || ! function_exists('csrf_token')) {
    return;
}

$token = csrf_token();
if ($token === '') {
    return;
}

echo '<input type="hidden" name="_token" value="'.htmlspecialchars($token, ENT_QUOTES, 'UTF-8').'">';
