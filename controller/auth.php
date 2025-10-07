<?php
session_start();

// Check kung may naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}
