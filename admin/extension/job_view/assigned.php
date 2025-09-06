<?php
  $staffList = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY name");
  $checkerList = mysqli_query($conn, "SELECT checker_id, name FROM checker ORDER BY name");
?>

<div class="card-body">
  <!-- Staff -->
  <div class="d-flex justify-content-between align-items-center py-1">
    <span><strong>Staff</strong></span>
    <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
      <select id="staffSelect" class="form-select form-select-sm" style="width: 60%;">
        <option value="">-- Select Staff --</option>
        <?php while($s = mysqli_fetch_assoc($staffList)): ?>
          <option value="<?php echo $s['staff_id']; ?>" <?php echo ($staff == $s['staff_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($s['staff_id']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    <?php else: ?>
      <span><?php echo htmlspecialchars($staff); ?></span>
    <?php endif; ?>
  </div>

  <!-- Checker -->
  <div class="d-flex justify-content-between align-items-center py-1">
    <span><strong>Checker</strong></span>
    <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
      <select id="checkerSelect" class="form-select form-select-sm" style="width: 60%;">
        <option value="">-- Select Checker --</option>
        <?php while($c = mysqli_fetch_assoc($checkerList)): ?>
          <option value="<?php echo $c['checker_id']; ?>" <?php echo ($checker == $c['checker_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($c['checker_id']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    <?php else: ?>
      <span><?php echo htmlspecialchars($checker); ?></span>
    <?php endif; ?>
  </div>

  <input type="hidden" id="jobID" value="<?php echo $jobID; ?>">
</div>