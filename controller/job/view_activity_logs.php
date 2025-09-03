<?php
include_once '../../database/db.php';
session_start();

$jobID = $_GET['job_id'] ?? 0;

$sql = "
  SELECT log_id, activity_date, activity_type, activity_description, updated_by 
  FROM activity_log 
  WHERE job_id = '" . mysqli_real_escape_string($conn, $jobID) . "' 
  ORDER BY activity_date DESC
";

$res = mysqli_query($conn, $sql);

if (mysqli_num_rows($res) > 0): ?>
  <ul class="list-group list-group-flush">
    <?php while ($log = mysqli_fetch_assoc($res)): ?>
      <li class="list-group-item">
        <div class="d-flex justify-content-between">
          <span class="fw-bold text-primary">
            <?php echo htmlspecialchars($log['activity_type']); ?>
          </span>
          <small class="text-muted">
            <?php echo date("M d, Y h:i A", strtotime($log['activity_date'])); ?>
          </small>
        </div>
        <p class="mb-1">
          <?php echo nl2br(htmlspecialchars($log['activity_description'])); ?>
        </p>
        <small class="text-secondary">
          Updated by: <strong><?php echo htmlspecialchars($log['updated_by']); ?></strong>
        </small>
      </li>
    <?php endwhile; ?>
  </ul>
<?php else: ?>
  <p class="text-muted">No activity logs found.</p>
<?php endif; ?>
