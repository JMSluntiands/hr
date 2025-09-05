<?php
include '../../database/db.php';

$job_id = intval($_GET['job_id'] ?? 0);

$sql = "SELECT * FROM staff_uploaded_files WHERE job_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): 
  while ($row = $result->fetch_assoc()):
    $files = json_decode($row['files_json'], true);
?>
  <div class="card mb-2">
    <div class="card-body">
      <?php foreach ($files as $f): ?>
        <div>
          <i class="fa fa-file-pdf text-danger me-2"></i>
          <a href="../document/<?php echo $job_id; ?>/<?php echo htmlspecialchars($f); ?>" target="_blank">
            <?php echo htmlspecialchars($f); ?>
          </a>
        </div>
      <?php endforeach; ?>
      <p class="mt-2 mb-0"><strong>Notes:</strong> <br><?php echo htmlspecialchars($row['comment']); ?></p>
      <small class="text-muted float-end">
        <?php echo date("M d, Y h:i A", strtotime($row['uploaded_at'])); ?>
      </small>
    </div>
  </div>
<?php 
  endwhile; 
else: 
?>
  <p class="text-muted">No staff files uploaded yet.</p>
<?php endif; ?>
