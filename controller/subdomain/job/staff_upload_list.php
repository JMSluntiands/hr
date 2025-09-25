<?php
session_start();
include '../../../database/db.php';

$job_id = intval($_GET['job_id'] ?? 0);

// Kunin reference number at status
$ref = '';
$status = '';
$stmt = $conn->prepare("SELECT job_reference_no, job_status FROM jobs WHERE job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$stmt->bind_result($ref, $status);
$stmt->fetch();
$stmt->close();

$sql = "SELECT * FROM staff_uploaded_files WHERE job_id = ? ORDER BY uploaded_at DESC LIMIT 1";


if ($sql) {
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
      <?php foreach ($files as $f): 
        $filePath = "../document/" . $ref . "/" . $f;
      ?>
        <div class="d-flex justify-content-between align-items-center mb-1">
          <div>
            <?php if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf'): ?>
              <i class="fa fa-file-pdf text-danger me-2"></i>
            <?php elseif (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'zip'): ?>
              <i class="fa fa-file-archive text-warning me-2"></i>
            <?php else: ?>
              <i class="fa fa-file text-secondary me-2"></i>
            <?php endif; ?>

            <!-- filename lang clickable, open sa tab -->
            <a href="<?php echo $filePath; ?>" target="_blank">
              <?php echo htmlspecialchars($f); ?>
            </a>
          </div>

          <!-- download icon na forced download -->
          <a href="<?php echo $filePath; ?>" 
             download="<?php echo htmlspecialchars($f); ?>" 
             class="text-decoration-none ms-2">
            <i class="fa fa-download fa-lg text-primary"></i>
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
<?php 
    endif; 
} else {
    echo '<p class="text-muted">No staff files available.</p>';
}
?>
