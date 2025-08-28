<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>
<body>
  <div class="main-wrapper">
    <?php include_once 'include/navbar.php' ?>
    <?php include_once 'include/sidebar.php' ?>

    <?php
      session_start();
      include '../database/db.php';

      $jobID = (int)($_GET['id'] ?? 0);

      $sql = "
        SELECT j.job_id, j.log_date, j.client_code, j.job_reference_no, j.client_reference_no,
               j.ncc_compliance, j.client_account_id, j.job_request_id, j.job_type, j.priority,
               j.address_client, j.notes, j.plan_complexity, j.last_update, j.job_status,
               j.upload_files, j.upload_project_files, j.staff_id, j.job_request_id, j.checker_id
        FROM jobs j
        WHERE j.job_id = ?
      ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $jobID);
      $stmt->execute();
      $result = $stmt->get_result();
      $job = $result->fetch_assoc();

      if (!$job) {
        echo "<script>alert('Job not found'); window.location='job';</script>";
        exit;
      }

      $job_ref = $job['job_reference_no'];
      $plans = json_decode($job['upload_files'], true);
      $docs  = json_decode($job['upload_project_files'], true);
      if (!is_array($plans)) $plans = [];
      if (!is_array($docs))  $docs = [];
    ?>

    <div class="page-wrapper">
      <div class="content container-fluid">
        <div class="page-header">
          <div class="row">
            <div class="col-sm-12">
              <div class="page-sub-header">
                <h3 class="page-title">Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>!</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="job">Job List</a></li>
                  <li class="breadcrumb-item active">Edit Job ID : <?php echo $jobID ?></li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <form id="editJobForm" enctype="multipart/form-data">
          <input type="hidden" name="job_id" value="<?php echo $jobID; ?>">

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header"><h5>Edit Job</h5></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12"><h5 class="text-center">Client Details</h5></div>

                    <!-- Reference No -->
                    <div class="col-md-4 col-sm-12 mt-3">
                      <label>Reference</label>
                      <input type="text" class="form-control" name="reference"
                             value="<?php echo htmlspecialchars($job['job_reference_no']); ?>" autocomplete="off">
                    </div>

                    <!-- Client Reference -->
                    <div class="col-md-4 col-sm-12 mt-3">
                      <label>Client Reference</label>
                      <input type="text" class="form-control" name="client_ref"
                             value="<?php echo htmlspecialchars($job['client_reference_no']); ?>" autocomplete="off">
                    </div>

                    <!-- Compliance -->
                    <div class="col-md-4 col-sm-12 mt-3">
                      <label>Compliance</label>
                      <select class="form-select select" name="compliance">
                        <option value="2022 (WHO)" <?php if($job['ncc_compliance']=="2022 (WHO)") echo "selected"; ?>>2022 (WHO)</option>
                        <option value="2019" <?php if($job['ncc_compliance']=="2019") echo "selected"; ?>>2019</option>
                      </select>
                    </div>

                    <!-- Client -->
                    <div class="col-md-12 col-sm-12 mt-3">
                      <label>Client</label>
                      <select class="form-select select" name="client_account_id">
                        <?php
                          $client_account_id = (int)$job['client_account_id'];
                          $client_sql = "SELECT client_account_id, client_account_name FROM client_accounts ORDER BY client_account_name";
                          $client_query = mysqli_query($conn, $client_sql);
                          while ($cData = mysqli_fetch_assoc($client_query)) {
                            $selected = ($cData['client_account_id'] == $client_account_id) ? "selected" : "";
                            echo "<option value='{$cData['client_account_id']}' $selected>".htmlspecialchars($cData['client_account_name'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 mt-3"><h5 class="text-center">Job Details</h5></div>

                    <!-- Address -->
                    <div class="col-md-12 mt-3">
                      <label>Job Address</label>
                      <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($job['address_client'] ?? ''); ?></textarea>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-12 mt-3">
                      <label>Job Priority</label>
                      <select class="form-select select" name="priority">
                        <?php
                          $priorities = ["Top (COB)","High 1 day","Standard 2 days","Standard 3 days","Standard 4 days","Low 5 days","Low 6 days","Low 7 days"];
                          foreach ($priorities as $p) {
                            $sel = ($job['priority']===$p) ? "selected" : "";
                            echo "<option value='".htmlspecialchars($p)."' $sel>".htmlspecialchars($p)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-12 col-sm-12 mt-3">
                      <label>Job Type</label>
                      <select class="form-select select" name="job_type">
                        <?php
                          $assign = $job['job_request_id'];
                          $q = mysqli_query($conn, "SELECT * FROM job_requests");
                          while ($r = mysqli_fetch_assoc($q)) {
                            $sel = ($r['job_request_id']==$assign) ? "selected" : "";
                            echo "<option value='{$r['job_request_id']}' $sel>".htmlspecialchars($r['job_request_type'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-12 mt-3">
                      <label>Job Status</label>
                      <select class="form-select select" name="status">
                        <?php
                          $statuses = ["Allocated","Accepted","Processing","For Checking","Completed","Awaiting Further Information","Pending","For Discussion","Revision Requested","Revised"];
                          foreach ($statuses as $st) {
                            $sel = ($job['job_status']===$st) ? "selected" : "";
                            echo "<option value='".htmlspecialchars($st)."' $sel>".htmlspecialchars($st)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12 mt-3">
                      <label>Notes</label>
                      <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($job['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Plans -->
                    <div class="col-sm-12 col-md-6 mt-3">
                      <label class="form-label d-flex justify-content-between">
                        Plans
                        <span class="badge bg-secondary" id="plansCount"><?php echo count($plans); ?> files</span>
                      </label>
                      <input type="file" class="form-control" id="uploadPlans" name="plans[]" multiple accept="application/pdf" style="height:30px!important">
                      <div id="plansPreview" class="mt-2">
                        <?php foreach ($plans as $file): ?>
                          <div class="d-flex align-items-center border p-2 mb-1 rounded file-row">
                            <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
                            <a href="<?php echo '../document/'.rawurlencode($job_ref).'/'.rawurlencode($file); ?>" target="_blank" class="flex-grow-1">
                              <?php echo htmlspecialchars($file); ?>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-danger btn-remove-file"
                                    data-id="<?php echo $job['job_id']; ?>"
                                    data-type="plans"
                                    data-file="<?php echo htmlspecialchars($file); ?>">
                              <i class="fa fa-times"></i>
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <!-- Documents -->
                    <div class="col-sm-12 col-md-6 mt-3">
                      <label class="form-label d-flex justify-content-between">
                        Documents
                        <span class="badge bg-secondary" id="docsCount"><?php echo count($docs); ?> files</span>
                      </label>
                      <input type="file" class="form-control" id="uploadDocs" name="docs[]" multiple accept="application/pdf" style="height:30px!important">
                      <div id="docsPreview" class="mt-2">
                        <?php foreach ($docs as $file): ?>
                          <div class="d-flex align-items-center border p-2 mb-1 rounded file-row">
                            <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
                            <a href="<?php echo '../document/'.rawurlencode($job_ref).'/'.rawurlencode($file); ?>" target="_blank" class="flex-grow-1">
                              <?php echo htmlspecialchars($file); ?>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-danger btn-remove-file"
                                    data-id="<?php echo $job['job_id']; ?>"
                                    data-type="docs"
                                    data-file="<?php echo htmlspecialchars($file); ?>">
                              <i class="fa fa-times"></i>
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    

                    <!-- Assigned / Checked -->
                    <div class="col-md-6 col-sm-12 mt-3">
                      <label>Assigned To</label>
                      <select class="form-select select" name="staff_id">
                        <?php
                          $assign = $job['staff_id'];
                          $q = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY name");
                          while ($r = mysqli_fetch_assoc($q)) {
                            $sel = ($r['staff_id']==$assign) ? "selected" : "";
                            echo "<option value='{$r['staff_id']}' $sel>".htmlspecialchars($r['staff_id'])."</option>";
                          }
                        ?>
                      </select>
                    </div>
                    <div class="col-md-6 col-sm-12 mt-3">
                      <label>Checked By</label>
                      <select class="form-select select" name="checker_id">
                        <?php
                          $checker = $job['checker_id'];
                          $q2 = mysqli_query($conn, "SELECT checker_id, name FROM checker ORDER BY name");
                          while ($r2 = mysqli_fetch_assoc($q2)) {
                            $sel = ($r2['checker_id']==$checker) ? "selected" : "";
                            echo "<option value='{$r2['checker_id']}' $sel>".htmlspecialchars($r2['checker_id'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-12 mt-4 text-center">
                      <button type="submit" class="btn btn-primary">Update Job</button>
                    </div>

                  </div><!-- row -->
                </div><!-- card-body -->
              </div><!-- card -->
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $('.select').select2({ width:'100%', minimumResultsForSearch: Infinity });

    $(document).on('click', '.btn-remove-file', function(){
      const job_id = $(this).data('id');
      const type   = $(this).data('type'); // 'plans' or 'docs'
      const file   = $(this).data('file');
      const row    = $(this).closest('.file-row');

      if(!confirm('Remove this file?')) return;

      $.ajax({
        url: '../controller/job/job_file_remove.php',
        type: 'POST',
        dataType: 'json',
        data: { job_id, type, file },
        success: function(res){
          if(res.status === 'success'){
            toastr.warning(res.message, 'Removed');
            row.remove();
            if (type === 'plans') {
              const n = parseInt($('#plansCount').text())-1;
              $('#plansCount').text((n<0?0:n)+' files');
            } else {
              const n = parseInt($('#docsCount').text())-1;
              $('#docsCount').text((n<0?0:n)+' files');
            }
          } else {
            toastr.error(res.message || 'Failed to remove file', 'Error');
          }
        },
        error: function(){
          toastr.error('Something went wrong.', 'Error');
        }
      });
    });

    $('#editJobForm').on('submit', function(e){
      e.preventDefault();

      const fd = new FormData(this);

      const pdfOk = (f) => f && f.type === 'application/pdf';
      const sizeOk = (f) => f && f.size <= (10*1024*1024);

      const plans = $('#uploadPlans')[0].files;
      const docs  = $('#uploadDocs')[0].files;

      for (let f of plans) {
        if (!pdfOk(f)) { toastr.error('All Plans must be PDF.', 'Invalid File'); return; }
        if (!sizeOk(f)) { toastr.error('Plans file exceeds 10MB.', 'Too Large'); return; }
      }
      for (let f of docs) {
        if (!pdfOk(f)) { toastr.error('All Documents must be PDF.', 'Invalid File'); return; }
        if (!sizeOk(f)) { toastr.error('Document file exceeds 10MB.', 'Too Large'); return; }
      }

      $.ajax({
        url: '../controller/job/job_update.php',
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res){
          if(res.status === 'success'){
            toastr.success(res.message || 'Job updated.', 'Success');
            setTimeout(() => {
              window.location.href = "job";
            }, 900);
          } else {
            toastr.error(res.message || 'Update failed.', 'Error');
          }
        },
        error: function(xhr){
          toastr.error('Something went wrong.', 'Error');
          console.error(xhr.responseText);
        }
      });
    });
  </script>
</body>
</html>
