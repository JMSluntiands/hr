<?php
// Legacy wrapper for old Time Off URL.
// Redirect all requests to the new timekeeping timesheet module.
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
header('Location: timekeeping/index.php' . $qs);
exit;

