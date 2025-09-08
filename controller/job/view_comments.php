<?php
include '../../database/db.php';
session_start();

$jobID  = intval($_GET['job_id'] ?? 0);
$offset = intval($_GET['offset'] ?? 0);
$limit  = 5;

if (!$jobID) {
    echo "<p class='text-muted'>Invalid job.</p>";
    exit;
}

$stmt = $conn->prepare("
    SELECT username, message, created_at 
    FROM comments 
    WHERE job_id = ? 
    ORDER BY comment_id DESC 
    LIMIT ?, ?
");
$stmt->bind_param("iii", $jobID, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        ?>
        <div class="comment border-bottom pb-2 mb-2">
          <div class="d-flex justify-content-between">
            <strong class="text-primary">
              <?= htmlspecialchars($row['username']) ?>
            </strong>
            <small class="text-muted">
              <?= htmlspecialchars($row['created_at']) ?>
            </small>
          </div>
          <div class="comment-body mt-1">
            <!-- ✅ Output raw HTML (safe kasi ikaw ang nag-save galing Quill) -->
            <?= $row['message'] ?>
          </div>
        </div>
        <?php
    }

    // View More button
    if ($result->num_rows == $limit) {
        ?>
        <div class="text-center mt-2">
          <button class="btn btn-outline-primary btn-sm view-more" data-offset="<?= $offset + $limit ?>">
            View More
          </button>
        </div>
        <?php
    }
} else {
    if ($offset == 0) {
        echo "<p class='text-muted'>No comments yet.</p>";
    }
}
?>
