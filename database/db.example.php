<?php
/**
 * Copy this file to db.php on the server and set real credentials.
 * File db.php is gitignored so production passwords are not committed.
 *
 * XAMPP local default:
 *   host 127.0.0.1, user root, password empty, database hr
 *
 * Typical shared hosting / VPS:
 *   host often localhost or 127.0.0.1, user and DB from cPanel / provider
 */

$db_host = '127.0.0.1';
$db_user = 'your_mysql_username';
$db_pass = 'your_mysql_password';
$db_name = 'your_database_name';
$db_port = 3306;

// Philippine time for PHP and DB timestamps (activity logs, NOW(), etc.)
date_default_timezone_set('Asia/Manila');

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_errno) {
    error_log('HR database connection failed: ' . $conn->connect_error);
    $conn = false;
} else {
    $conn->set_charset('utf8mb4');
    if (!$conn->query("SET time_zone = '+08:00'")) {
        error_log('MySQL SET time_zone failed: ' . $conn->error);
    }
}
