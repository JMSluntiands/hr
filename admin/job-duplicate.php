<?php
session_start();
include '../database/db.php';

// ✅ Job ID to duplicate
$jobID = (int)($_GET['id'] ?? 0);
$dupJob = [];

if ($jobID > 0) {
  $sql = "SELECT * FROM jobs WHERE job_id = $jobID";
  $res = mysqli_query($conn, $sql);
  if ($res && mysqli_num_rows($res) > 0) {
    $dupJob = mysqli_fetch_assoc($res);
  } else {
    $_SESSION['error'] = "Job not found.";
    header("Location: job.php");
    exit;
  }
} else {
  $_SESSION['error'] = "Invalid Job ID.";
  header("Location: job.php");
  exit;
}

// ✅ Decode JSON files
$planFiles = json_decode($dupJob['upload_files'] ?? '[]', true);
$docFiles  = json_decode($dupJob['upload_project_files'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>
<body>
  <div class="main-wrapper">
    <?php include_once 'include/navbar.php' ?>
    <?php include_once 'include/sidebar.php' ?>

    <div class="page-wrapper">
      <div class="content container-fluid">
        <div class="page-header">
          <div class="row">
            <div class="col-sm-12">
              <div class="page-sub-header">
                <h3 class="page-title">Duplicate Job</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="job.php">Job List</a></li>
                  <li class="breadcrumb-item active">Duplicate Job</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <form id="duplicateJobForm" enctype="multipart/form-data">
          <input type="hidden" name="source_job_id" value="<?= $jobID ?>">
          <!-- Hidden inputs to keep track of kept old files -->
          <input type="hidden" name="keep_plans" id="keepPlans">
          <input type="hidden" name="keep_docs" id="keepDocs">

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header"><h5>Duplicate Job Form</h5></div>
                <div class="card-body">
                  <div class="row">

                    <!-- Reference No -->
                    <div class="col-md-4 mt-3">
                      <label>Reference No.</label>
                      <input type="text" name="reference" class="form-control"
                        value="<?= htmlspecialchars($dupJob['job_reference_no'] ?? '') ?>"
                        required>
                    </div>

                    <!-- Client Ref -->
                    <div class="col-md-4 mt-3">
                      <label>Client Reference</label>
                      <input type="text" name="client_ref" class="form-control"
                        value="<?= htmlspecialchars($dupJob['client_reference_no'] ?? '') ?>">
                    </div>

                    <!-- Compliance -->
                    <div class="col-md-4 mt-3">
                      <label>Compliance</label>
                      <select name="compliance" class="form-select select">
                        <option value="2022 (WHO)" <?= ($dupJob['ncc_compliance']=="2022 (WHO)"?"selected":"") ?>>2022 (WHO)</option>
                        <option value="2019" <?= ($dupJob['ncc_compliance']=="2019"?"selected":"") ?>>2019</option>
                      </select>
                    </div>

                    <!-- Client -->
                    <div class="col-md-12 mt-3">
                      <label>Client</label>
                      <select class="form-select select" name="client_account_id">
                        <?php
                          $cQ = mysqli_query($conn,"SELECT client_account_id,client_account_name FROM client_accounts");
                          while($c=mysqli_fetch_assoc($cQ)){
                            $sel = ($dupJob['client_account_id']==$c['client_account_id'])?"selected":"";
                            echo "<option value='{$c['client_account_id']}' $sel>".htmlspecialchars($c['client_account_name'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Address -->
                    <div class="col-md-12 mt-3">
                      <label>Job Address</label>
                      <textarea name="address" class="form-control"><?= htmlspecialchars($dupJob['address_client'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-12 mt-3">
                      <label>Job Type</label>
                      <select class="form-select select" name="jobRequest">
                        <?php
                          $assign = $dupJob['job_request_id'];
                          $q = mysqli_query($conn, "SELECT * FROM job_requests");
                          while ($r = mysqli_fetch_assoc($q)) {
                            $sel = ($r['job_request_id']==$assign) ? "selected" : "";
                            echo "<option value='{$r['job_request_id']}' $sel>".htmlspecialchars($r['job_request_type'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-12 mt-3">
                      <label>Priority</label>
                      <select name="priority" class="form-select select">
                        <?php
                          $priorities=["Top (COB)","High 1 day","Standard 2 days","Standard 3 days","Standard 4 days","Low 5 days","Low 6 days","Low 7 days"];
                          foreach($priorities as $p){
                            $sel = ($dupJob['priority']==$p)?"selected":"";
                            echo "<option $sel>".htmlspecialchars($p)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-12 mt-3">
                      <label>Status</label>
                      <select name="status" class="form-select select">
                        <?php
                          $statuses=["Allocated","Accepted","Processing","For Checking","Completed","Awaiting Further Information","Pending","For Discussion","Revision Requested","Revised"];
                          foreach($statuses as $s){
                            $sel = ($dupJob['job_status']==$s)?"selected":"";
                            echo "<option $sel>".htmlspecialchars($s)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12 mt-3">
                      <label>Notes</label>
                      <textarea name="notes" class="form-control"><?= htmlspecialchars($dupJob['notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Existing Plans -->
                    <div class="col-md-6 mt-3">
                      <label>Existing Plans <span class="badge bg-secondary" id="plansCount"><?= count($planFiles) ?> file(s)</span></label>
                      <div id="plansPreview" class="mt-2">
                        <?php foreach ($planFiles as $i => $pf): ?>
                          <div class="d-flex align-items-center border p-2 mb-1 rounded existing-file-row" data-filename="<?= htmlspecialchars($pf) ?>">
                            <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
                            <span class="flex-grow-1"><?= htmlspecialchars($pf) ?></span>
                            <button type="button" class="btn btn-sm btn-danger remove-existing-file" data-file="<?= htmlspecialchars($pf) ?>">
                              <i class="fa fa-times"></i>
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <input type="file" class="form-control mt-2" id="uploadPlans" multiple accept="application/pdf" name="plans[]">
                    </div>

                    <!-- Existing Docs -->
                    <div class="col-md-6 mt-3">
                      <label>Existing Documents <span class="badge bg-secondary" id="docsCount"><?= count($docFiles) ?> file(s)</span></label>
                      <div id="docsPreview" class="mt-2">
                        <?php foreach ($docFiles as $i => $df): ?>
                          <div class="d-flex align-items-center border p-2 mb-1 rounded existing-file-row" data-filename="<?= htmlspecialchars($df) ?>">
                            <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
                            <span class="flex-grow-1"><?= htmlspecialchars($df) ?></span>
                            <button type="button" class="btn btn-sm btn-danger remove-existing-file" data-file="<?= htmlspecialchars($df) ?>">
                              <i class="fa fa-times"></i>
                            </button>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <input type="file" class="form-control mt-2" id="uploadDocs" multiple accept="application/pdf" name="docs[]">
                    </div>

                    <!-- Assigned -->
                    <div class="col-md-6 mt-3">
                      <label>Assigned To</label>
                      <select name="assigned" class="form-select select">
                        <?php
                          $sQ=mysqli_query($conn,"SELECT staff_id,name FROM staff");
                          while($s=mysqli_fetch_assoc($sQ)){
                            $sel=($dupJob['staff_id']==$s['staff_id'])?"selected":"";
                            echo "<option value='{$s['staff_id']}' $sel>".htmlspecialchars($s['name'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Checker -->
                    <div class="col-md-6 mt-3">
                      <label>Checked By</label>
                      <select name="checked" class="form-select select">
                        <?php
                          $chQ=mysqli_query($conn,"SELECT checker_id,name FROM checker");
                          while($ch=mysqli_fetch_assoc($chQ)){
                            $sel=($dupJob['checker_id']==$ch['checker_id'])?"selected":"";
                            echo "<option value='{$ch['checker_id']}' $sel>".htmlspecialchars($ch['name'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-12 mt-4">
                      <button type="submit" class="btn btn-primary">Save Duplicate</button>
                    </div>

                  </div><!-- row -->
                </div><!-- card-body -->
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>
  <script>
    $(function(){
      $('.select').select2({width:'100%'});

      let plansFiles = [];   // new uploaded plans
      let docsFiles  = [];   // new uploaded docs
      let removedPlans = []; // removed old plans
      let removedDocs  = []; // removed old docs
      const MAX_SIZE = 10 * 1024 * 1024; // 10MB

      // ✅ Refresh preview for NEW files
      function refreshPreview(fileArray, previewContainer, countContainer) {
        let $preview = $("#" + previewContainer);
        $preview.find(".new-file-row").remove(); // wag galawin yung existing
        $.each(fileArray, function (i, file) {
          let $row = $(`
            <div class="d-flex align-items-center border p-2 mb-1 rounded new-file-row">
              <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
              <span class="flex-grow-1">${file.name}</span>
              <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${i}" data-target="${previewContainer}">
                <i class="fa fa-times"></i>
              </button>
            </div>
          `);
          $preview.append($row);
        });
        $("#" + countContainer).text(
          $preview.find(".existing-file-row").length + fileArray.length + " file(s)"
        );
      }

      // ✅ New Upload (with size check)
      $("#uploadPlans").on("change", function (e) {
        let files = Array.from(e.target.files);
        files.forEach(f => {
          if (f.size > MAX_SIZE) {
            toastr.error(`${f.name} exceeds 10MB limit.`);
          } else {
            plansFiles.push(f);
          }
        });
        refreshPreview(plansFiles, "plansPreview", "plansCount");
        $(this).val("");
      });

      $("#uploadDocs").on("change", function (e) {
        let files = Array.from(e.target.files);
        files.forEach(f => {
          if (f.size > MAX_SIZE) {
            toastr.error(`${f.name} exceeds 10MB limit.`);
          } else {
            docsFiles.push(f);
          }
        });
        refreshPreview(docsFiles, "docsPreview", "docsCount");
        $(this).val("");
      });

      // ✅ Remove NEW file
      $(document).on("click", ".remove-file", function () {
        let index = $(this).data("index");
        let target = $(this).data("target");
        if (target === "plansPreview") {
          plansFiles.splice(index, 1);
          refreshPreview(plansFiles, "plansPreview", "plansCount");
        } else if (target === "docsPreview") {
          docsFiles.splice(index, 1);
          refreshPreview(docsFiles, "docsPreview", "docsCount");
        }
      });

      // ✅ Remove EXISTING file (mark only, not delete from folder)
      $(document).on("click", ".remove-existing-file", function () {
        let fileName = $(this).data("file");
        let $row = $(this).closest(".existing-file-row");
        if ($row.parent().attr("id") === "plansPreview") {
          removedPlans.push(fileName);
        } else if ($row.parent().attr("id") === "docsPreview") {
          removedDocs.push(fileName);
        }
        $row.remove();
        $("#plansCount").text($("#plansPreview .existing-file-row").length + plansFiles.length + " file(s)");
        $("#docsCount").text($("#docsPreview .existing-file-row").length + docsFiles.length + " file(s)");
      });

      // ✅ Submit
      $("#duplicateJobForm").on("submit", function (e) {
        e.preventDefault();
        let fd = new FormData(this);

        // Append NEW files manually (wag iwan sa serialize)
        plansFiles.forEach(f => fd.append("plans[]", f));
        docsFiles.forEach(f => fd.append("docs[]", f));

        // Append removed files
        fd.append("removedPlans", JSON.stringify(removedPlans));
        fd.append("removedDocs", JSON.stringify(removedDocs));

        $.ajax({
          url: "../controller/job/job_save_duplicate.php",
          type: "POST",
          data: fd,
          processData: false,
          contentType: false,
          dataType: "json",
          success: function (res) {
            if (res.status === "success") {
              toastr.success("Job duplicated successfully!");
              setTimeout(() => window.location.href = "job", 1200);
            } else {
              toastr.error(res.message || "Failed to duplicate job.");
            }
          },
          error: function () {
            toastr.error("Server error.");
          }
        });
      });

      function updateKeepInputs() {
        $("#keepPlans").val(JSON.stringify(plansFiles));
        $("#keepDocs").val(JSON.stringify(docsFiles));
      }

      // Call this every refresh
      function refreshPreview(fileArray, previewContainer, countContainer) {
        let $preview = $("#" + previewContainer);
        $preview.empty();
        $.each(fileArray, function (i, file) {
          let filename = (typeof file === "string") ? file : file.name;
          let $row = $(`
            <div class="d-flex align-items-center border p-2 mb-1 rounded file-row">
              <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
              <span class="flex-grow-1">${filename}</span>
              <button type="button" class="btn btn-sm btn-danger remove-file" 
                data-index="${i}" data-target="${previewContainer}">
                <i class="fa fa-times"></i>
              </button>
            </div>
          `);
          $preview.append($row);
        });
        $("#" + countContainer).text(fileArray.length + " file(s)");
        updateKeepInputs();
      }
    });
  </script>
</body>
</html>
