<!-- <h5>Plans</h5> -->
<?php if (!empty($plans)): ?>
  <ul class="list-group">
    <?php foreach ($plans as $p): ?>
      <li class="list-group-item d-flex align-items-center">
        <i class="fa fa-file-pdf text-danger me-2"></i>
        <a href="../document/<?php echo $ref; ?>/<?php echo htmlspecialchars($p); ?>" target="_blank">
          <?php echo htmlspecialchars($p); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="text-muted">No plans uploaded.</p>
<?php endif; ?>