<?php
include '../../database/db.php';
session_start();

$jobID = $_GET['job_id'] ?? 0;
$offset = $_GET['offset'] ?? 0;
$limit = 5;

$sql = "SELECT username, message, created_at 
        FROM comments 
        WHERE job_id = '$jobID' 
        ORDER BY comment_id DESC 
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0){
  while($row = mysqli_fetch_assoc($result)){
    echo '<div class="mb-2">';
    echo '<div class="d-flex justify-content-between">';
    echo '<strong>'.htmlspecialchars($row['username']).'</strong>';
    echo '<small class="text-muted">'.$row['created_at'].'</small>';
    echo '</div>';
    echo '<div>'.nl2br(htmlspecialchars($row['message'])).'</div>';
    echo '<hr>';
    echo '</div>';
  }

  // check kung may next batch pa
  $countSql = "SELECT COUNT(*) as total FROM comments WHERE job_id = '$jobID'";
  $countResult = mysqli_query($conn, $countSql);
  $total = mysqli_fetch_assoc($countResult)['total'];

  if($offset + $limit < $total){
    echo '<button class="btn btn-link text-primary p-0 view-more" data-offset="'.($offset + $limit).'">View More</button>';
  }
} else {
  if ($offset == 0) {
    echo '<p class="text-muted">No comments yet.</p>';
  }
}
?>
