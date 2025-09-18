<div>
  <?php if ($_SESSION['role'] === 'LUNTIAN' && $status !== 'Completed'): ?>
    <!-- Editable dropdown for LUNTIAN -->
    <span id="statusBadge" 
          class="badge text-dark" 
          style="background-color: <?php echo $badgeColor; ?>; font-weight:bold; cursor:pointer;">
      <?php echo htmlspecialchars($status); ?>
    </span>
    
    <select id="jobStatus" 
            class="form-select form-select-sm d-none"
            style="width:auto; display:inline-block;">
            <?php if ($status != 'For Review') { ?>
            <option value="Allocated" <?php echo ($status == 'Allocated') ? 'selected' : ''; ?>>Allocated</option>
            <option value="Accepted" <?php echo ($status == 'Accepted') ? 'selected' : ''; ?>>Accepted</option>
            <option value="Processing" <?php echo ($status == 'Processing') ? 'selected' : ''; ?>>Processing</option>
            <option value="For Checking" <?php echo ($status == 'For Checking') ? 'selected' : ''; ?>>For Checking</option>
            <option value="Awaiting Further Information" <?php echo ($status == 'Awaiting Further Information') ? 'selected' : ''; ?>>Awaiting Further Information</option>
            <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="For Discussion" <?php echo ($status == 'For Discussion') ? 'selected' : ''; ?>>For Discussion</option>
            <option value="Revision Requested" <?php echo ($status == 'Revision Requested') ? 'selected' : ''; ?>>Revision Requested</option>
            <option value="Revised" <?php echo ($status == 'Revised') ? 'selected' : ''; ?>>Revised</option>
            <?php } ?>
            <option value="For Review" <?php echo ($status == 'For Review') ? 'selected' : ''; ?>>For Review</option>
            <option value="For Email Confirmation" <?php echo ($status == 'For Email Confirmation') ? 'selected' : ''; ?>>For Email Confirmation</option>
    </select>
    <?php else: ?>
      <!-- Read-only badge -->
      <span id="statusBadge" 
        class="badge text-dark fw-bold status-badge" 
        data-status="<?php echo htmlspecialchars($status); ?>"
        style="cursor:pointer;">
        <?php echo htmlspecialchars($status); ?>
      </span>

    <?php endif; ?>
</div>