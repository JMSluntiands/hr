<div>
  <?php if (!empty($docs)): ?>
    <ul class="list-group">
      <?php foreach ($docs as $d): ?>
        <li class="list-group-item d-flex align-items-center">
          <i class="fa fa-file-pdf text-danger me-2"></i>
          <a href="../document/<?php echo $ref; ?>/<?php echo htmlspecialchars($d); ?>" target="_blank">
            <?php echo htmlspecialchars($d); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-muted">No documents uploaded.</p>
  <?php endif; ?>
</div>