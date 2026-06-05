<?php

/**
 * Migrated to Laravel: GET /inventory
 * This stub only redirects; the dashboard lives in resources/views/inventory/dashboard/
 */
$target = '/inventory';
if (defined('HR_APP_URL')) {
    $target = HR_APP_URL.'/inventory';
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $target = $scheme.'://'.$_SERVER['HTTP_HOST'].'/inventory';
}

header('Location: '.$target, true, 302);
exit;
