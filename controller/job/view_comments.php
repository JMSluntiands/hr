<?php
include '../../database/db.php';
session_start();

$jobID = $_GET['job_id'] ?? 0;

// kunin lahat ng comments ng job
$sql = "SELECT username, message, created_at 
        FROM comments 
        WHERE job_id = '$jobID' 
        ORDER BY comment_id ASC";
$result = mysqli_query($conn, $sql);

// header para mag-download ng RTF file
header("Content-Type: application/rtf");
header("Content-Disposition: attachment; filename=job_{$jobID}_comments.rtf");

// start RTF document
echo "{\\rtf1\\ansi\\deff0\n";
echo "{\\fonttbl{\\f0 Arial;}}\n";
echo "\\fs24 Job ID: {$jobID}\\par\\par\n"; 

if(mysqli_num_rows($result) > 0){
  while($row = mysqli_fetch_assoc($result)){
    $username = htmlspecialchars($row['username']);
    $created  = htmlspecialchars($row['created_at']);
    $message  = htmlspecialchars($row['message']);

    // format per comment
    echo "{\\b {$username}} ({$created})\\par\n";
    echo nl2br($message) . "\\par\n";
    echo "\\par\n";
  }
} else {
  echo "No comments found.\\par\n";
}

// end RTF
echo "}";
?>
