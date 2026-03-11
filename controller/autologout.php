<?php
session_start();
session_unset();
session_destroy();

if (empty($_POST) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Location: ../index.php?timeout=1');
    exit;
}
header('Content-Type: application/json');
echo json_encode(["status" => "success", "message" => "You have been logged out due to inactivity."]);
exit();
