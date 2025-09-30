<?php
session_start();
session_unset();
session_destroy();

header('Content-Type: application/json');
echo json_encode(["status" => "success", "message" => "You have been logged out due to inactivity."]);
exit();
