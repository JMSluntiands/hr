<?php

// Get latest active announcement
$sql = "SELECT message FROM announcement 
        WHERE status='active' 
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY start_date DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$announcement = '';

if ($row = mysqli_fetch_assoc($result)) {
    $announcement = $row['message'];
}
?>

<div class="announcement-bar-fixed">
  <div class="announcement-text">
    <span>
      📢 <strong>Announcement:</strong> <?php echo $announcement ?>
    </span>
  </div>
</div>
