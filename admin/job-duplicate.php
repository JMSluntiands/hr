<?php
session_start();
include '../database/db.php';

// ✅ Function to get next reference no.
function getNextReference($conn, $table, $column, $ref) {
    // Kunin yung base (alisin yung -number kung meron)
    $baseRef = preg_replace('/-\d+$/', '', $ref);
    $baseRefEscaped = mysqli_real_escape_string($conn, $baseRef);

    // Hanapin lahat ng existing refs na nagsisimula sa baseRef
    $sql = "SELECT $column FROM $table WHERE $column LIKE '{$baseRefEscaped}%'";
    $res = mysqli_query($conn, $sql);

    $maxNum = 0;
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $existing = $row[$column];
            if (preg_match('/^' . preg_quote($baseRef, '/') . '(?:-(\d+))?$/', $existing, $m)) {
                $num = isset($m[1]) ? (int)$m[1] : 0;
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
    }

    // Next number
    $nextNum = $maxNum + 1;
    return $baseRef . "-" . $nextNum;
}

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

// ✅ Client Reference auto increment
$clientRef = $dupJob['client_reference_no'] ?? '';
if ($clientRef) {
    $clientRef = getNextReference($conn, "jobs", "client_reference_no", $clientRef);
}

// ✅ Job Reference auto increment
$jobRef = $dupJob['job_reference_no'] ?? '';
if ($jobRef) {
    $jobRef = getNextReference($conn, "jobs", "job_reference_no", $jobRef);
}
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

        <?php 
          date_default_timezone_set('Asia/Manila');
          $today = date("dm");

          $sql = "SELECT COUNT(*) as cnt FROM jobs WHERE DATE(log_date) = CURDATE()";
          $res = mysqli_query($conn, $sql);
          $row = mysqli_fetch_assoc($res);
          $count = $row['cnt'] + 1;

          $reference = "JOB" . $today . "-" . str_pad($count, 3, "0", STR_PAD_LEFT);
        ?>

        <form id="duplicateJobForm" enctype="multipart/form-data">
          <input type="hidden" name="source_job_id" value="<?= $jobID ?>">
          <!-- Hidden inputs to keep track of kept old files -->
          <input type="hidden" name="keep_plans" id="keepPlans">
          <input type="hidden" name="keep_docs" id="keepDocs">

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div>
                    <h5>Duplicate Job Form</h5>
                  </div>
                  <div>
                    <h5><?php echo $reference ?></h5>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <input type="hidden" name="log_date" id="log_date">
                    <input type="hidden" name="jreference" value="<?php echo $reference ?>">

                    <!-- Reference No. (restricted) -->
                    <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
                      <div class="col-md-4 col-sm-12 mt-3">
                        <label class="form-label">Reference No.</label>
                        <input type="text" name="reference_show" class="form-control" value="<?php echo $jobRef ?>" disabled autocomplete="off">
                        <input type="hidden" name="reference" value="<?php echo htmlspecialchars($jobRef); ?>">
                      </div>
                    <?php endif; ?>

                    <!-- Client Ref -->
                    <div class="col-md-4 mt-3">
                      <label>Client Reference</label>
                      <input type="text" name="client_show" class="form-control" value="<?= htmlspecialchars($clientRef) ?>" disabled autocomplete="off">
                      <input type="hidden" name="client_ref" value="<?= htmlspecialchars($clientRef) ?>">
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

                    <!-- Dwelling -->
                    <div class="col-md-12 mt-3">
                      <label>Unit \ Dwelling</label>
                      <input type="text" name="dwelling" class="form-control" autocomplete="off">
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
                      <input type="file" class="form-control mt-2" id="uploadPlans" multiple accept="application/pdf" name="plans[]"  style="height:30px!important">
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
                      <input type="file" class="form-control mt-2" id="uploadDocs" multiple accept="application/pdf" name="docs[]"  style="height:30px!important">
                    </div>

                    <!-- Assigned -->
                    <div class="col-md-6 mt-3">
                      <label>Assigned To</label>
                      <select name="assigned" class="form-select select">
                        <?php
                          $sQ=mysqli_query($conn,"SELECT staff_id,name FROM staff");
                          while($s=mysqli_fetch_assoc($sQ)){
                            $sel=($dupJob['staff_id']==$s['staff_id'])?"selected":"";
                            echo "<option value='{$s['staff_id']}' $sel>".htmlspecialchars($s['staff_id'])."</option>";
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
                            echo "<option value='{$ch['checker_id']}' $sel>".htmlspecialchars($ch['checker_id'])."</option>";
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
      $('.select').select2({
        width:'100%',
        minimumResultsForSearch: 2,
      });

      
    });
  </script>
  <script src="../function/job/duplicate/uploadDocs.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/duplicate/uploadPlans.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/duplicate/duplicate.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/duplicate/refresh_preview.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/duplicate/remove_existing_file.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/duplicate/remove_file.js?v=<?php echo time(); ?>"></script>
  
</body>
</html>
