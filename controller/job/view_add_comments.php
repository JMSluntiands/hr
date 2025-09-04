<?php
include '../../database/db.php';
session_start();

$username = $_SESSION['role'] ?? 'Guest';
$jobID = $_POST['job_id'] ?? 0;
$message = trim($_POST['message'] ?? '');
$createdAt = $_POST['created_at'] ?? ''; // ⏰ kukunin sa device

if($jobID && $message && $createdAt){
    $stmt = $conn->prepare("INSERT INTO comments (job_id, username, message, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $jobID, $username, $message, $createdAt);

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Comment added"]);
    } else {
        echo json_encode(["success"=>false,"message"=>"Failed to add comment"]);
    }
} else {
    echo json_encode(["success"=>false,"message"=>"Invalid input"]);
}
?>
