<!-- <h5>Plans</h5> -->
<?php if (!empty($plans)): ?>
  <ul class="list-group" id="plans-list">
    <?php foreach ($plans as $p): ?>
      <li class="list-group-item d-flex align-items-center justify-content-between">
        <div>
          <i class="fa fa-file-pdf text-danger me-2"></i>
          <a href="../document/<?php echo $ref; ?>/<?php echo htmlspecialchars($p); ?>" target="_blank">
            <?php echo htmlspecialchars($p); ?>
          </a>
        </div>

        <?php if ($status === "Allocated"): ?>
          <button 
            class="btn btn-sm btn-danger delete-plan" 
            data-filename="<?php echo htmlspecialchars($p); ?>" 
            data-ref="<?php echo htmlspecialchars($ref); ?>">
            <i class="fa fa-trash"></i>
          </button>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p class="text-muted">No plans uploaded.</p>
<?php endif; ?>
