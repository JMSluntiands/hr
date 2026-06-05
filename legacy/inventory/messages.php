<?php

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/inventory/messages'.($query !== '' ? '?'.$query : '');
if (defined('HR_APP_URL')) {
    $target = HR_APP_URL.$target;
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $target = $scheme.'://'.$_SERVER['HTTP_HOST'].$target;
}
header('Location: '.$target, true, 302);
exit;
