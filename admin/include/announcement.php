<?php
include '../database/db.php';

// ✅ Set timezone to Manila
date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d'); // ✅ Date only (no time)

// ✅ Query: Show announcement if current date is between start_date and end_date
$sql = "
    SELECT message, start_date, end_date 
    FROM announcement 
    WHERE status = 'active'
      AND DATE(start_date) <= '$current_date'
      AND (end_date IS NULL OR DATE(end_date) >= '$current_date')
    ORDER BY start_date DESC 
    LIMIT 1
";

$result = mysqli_query($conn, $sql);
$announcement = '';

if ($row = mysqli_fetch_assoc($result)) {
    $announcement = trim($row['message']);
}

// ✅ Display only if there’s an active announcement
if (!empty($announcement)) {
    echo '
    <div class="announcement-bar-fixed" id="announcementBar">
        <div class="announcement-text">
            <span>📢 <strong>Announcement:</strong> ' . htmlspecialchars($announcement) . '</span>
        </div>
    </div>
    ';
} else {
    // ✅ Hide bar if none active
    echo '
    <style>
        .announcement-bar-fixed { display: none !important; }
    </style>
    ';
}
?>
