<?php
session_start();

// Check kung may naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

// OPTIONAL: role-based restriction
// Halimbawa: page na ito ay para lang sa admin
// if ($_SESSION['role'] !== 'admin') {
//     // Pwede mong i-redirect sa ibang page (e.g. dashboard)
//     header("Location: ../../unauthorized.php");
//     exit;
// }
