<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h5 class="card-title">Staff Upload Plan/Document</h5>
      </div>
    </div>
  </div>
  <div class="card-body">
    <form id="staffUploadForm" enctype="multipart/form-data">
      <input type="hidden" name="job_id" id="jobID" value="<?php echo $jobID; ?>">

      <div class="form-group">
        <label>Upload Files</label>
        <input type="file" name="docs[]" id="uploadDocs" multiple accept="application/pdf" class="form-control" style="height:30px!important">
      </div>

      <div class="form-group mt-2">
        <textarea name="comment" id="staffComment" class="form-control" placeholder="Add comment..."></textarea>
      </div>

      <button type="button" id="btnUploadStaffFile" class="btn btn-primary mt-3">Upload</button>
    </form>

  </div>
</div>