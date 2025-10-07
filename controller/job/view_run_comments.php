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
    SELECT name, message, created_at
    FROM run_comments
    WHERE job_id = ?
    ORDER BY run_comment_id DESC
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
              <?= htmlspecialchars($row['name']) ?>
            </strong>
            <small class="text-muted">
              <?= htmlspecialchars($row['created_at']) ?>
            </small>
          </div>
          <div class="comment-body mt-1">
            <!-- ✅ Raw HTML output from Quill (with bullets/lists) -->
            <?= $row['message'] ?>
          </div>
        </div>
        <?php
    }

    // check kung may next batch pa
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM run_comments WHERE job_id = ?");
    $countStmt->bind_param("i", $jobID);
    $countStmt->execute();
    $countRes = $countStmt->get_result()->fetch_assoc();
    $total = $countRes['total'] ?? 0;

    if ($offset + $limit < $total) {
        ?>
        <div class="text-center mt-2">
          <button class="btn btn-outline-primary btn-sm view-more-run" 
                  data-offset="<?= $offset + $limit ?>">
            View More
          </button>
        </div>
        <?php
    }
} else {
    if ($offset == 0) {
        echo "<p class='text-muted'>No run comments yet.</p>";
    }
}
?>

<!-- 🔥 Ensure bullets are visible -->
<style>
.comment-body ul {
  list-style-type: disc;
  margin-left: 20px;
}
.comment-body ol {
  list-style-type: decimal;
  margin-left: 20px;
}
</style>
