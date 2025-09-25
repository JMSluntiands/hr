<?php
include '../../database/db.php';
session_start();

$unique_id = $_SESSION['unique_id'];
$role = $_SESSION['role'];

$mailCount = 0;
$listCount = 0;
$reviewCount = 0;

// Mailbox count
if ($role === 'Staff') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Email Confirmation' AND staff_id = ?");
    $stmt->bind_param("s", $unique_id);
} elseif ($role === 'Checker') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Email Confirmation' AND checker_id = ?");
    $stmt->bind_param("s", $unique_id);
} else {
    // default global
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Email Confirmation'");
}
$stmt->execute();
$stmt->bind_result($mailCount);
$stmt->fetch();
$stmt->close();

if ($role === 'LUNTIAN') {
    // LUNTIAN → lahat
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'Allocated'");
    $stmt->execute();
    $stmt->bind_result($listCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Review'");
    $stmt->execute();
    $stmt->bind_result($reviewCount);
    $stmt->fetch();
    $stmt->close();
} elseif ($role === 'Staff') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'Allocated' AND staff_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $stmt->bind_result($listCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Review' AND staff_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $stmt->bind_result($reviewCount);
    $stmt->fetch();
    $stmt->close();
} elseif ($role === 'Checker') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'Allocated' AND checker_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $stmt->bind_result($listCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE job_status = 'For Review' AND checker_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $stmt->bind_result($reviewCount);
    $stmt->fetch();
    $stmt->close();
} else {
    // Clients → client_name = role
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM jobs j 
        LEFT JOIN clients c ON j.client_code = c.client_code 
        WHERE c.client_name = ? AND job_status = 'Allocated'
    ");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $stmt->bind_result($listCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM jobs j 
        LEFT JOIN clients c ON j.client_code = c.client_code 
        WHERE c.client_name = ? AND job_status = 'For Review'
    ");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $stmt->bind_result($reviewCount);
    $stmt->fetch();
    $stmt->close();
}

echo json_encode([
    "mailCount" => $mailCount,
    "listCount" => $listCount,
    "reviewCount" => $reviewCount
]);
