<div>
  <?php if (!empty($docs)): ?>
    <ul class="list-group" id="docs-list">
      <?php foreach ($docs as $d): ?>
        <li class="list-group-item d-flex align-items-center justify-content-between">
          <div>
            <i class="fa fa-file-pdf text-danger me-2"></i>
            <a href="../document/<?php echo $ref; ?>/<?php echo htmlspecialchars($d); ?>" target="_blank">
              <?php echo htmlspecialchars($d); ?>
            </a>
          </div>

          <?php if ($status === "Allocated"): ?>
            <button 
              class="btn btn-sm btn-danger delete-doc" 
              data-filename="<?php echo htmlspecialchars($d); ?>" 
              data-ref="<?php echo htmlspecialchars($ref); ?>">
              <i class="fa fa-trash"></i>
            </button>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-muted">No documents uploaded.</p>
  <?php endif; ?>
</div>
