<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h5 class="card-title">Checker Upload Plan/Document</h5>
      </div>
    </div>
  </div>
  <div class="card-body">
    <form id="staffUploadForm" enctype="multipart/form-data">
      <input type="hidden" name="job_id" id="jobID" value="<?php echo $jobID; ?>">

      <div class="form-group">
        <label>Upload Files</label>
        <input type="file" name="docs[]" id="uploadDocs" 
          multiple 
          accept=".pdf, .zip,application/pdf,application/zip" 
          class="form-control" 
          style="height:30px!important">
      </div>

      <div class="form-group mt-2">
        <!-- Toolbar -->
        <div id="staffCommentToolbar">
          <button class="ql-bold"></button>
          <button class="ql-italic"></button>
          <button class="ql-underline"></button>
          <button class="ql-list" value="bullet"></button>
          <button class="ql-list" value="ordered"></button>
        </div>

        <!-- Quill editor -->
        <div id="staffCommentEditor" style="height:120px;"></div>

        <!-- Hidden input para ma-submit -->
        <input type="hidden" name="comment" id="staffCommentInput">
      </div>

      <button type="button" id="btnUploadStaffFile" class="btn btn-primary mt-3" <?php echo $disabled ?>>Upload</button>
    </form>
  </div>
</div>
