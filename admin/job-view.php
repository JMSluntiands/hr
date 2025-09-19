<!DOCTYPE html>
<html lang="en">
  <?php include_once 'include/header.php' ?>
  <body>
    <div class="main-wrapper">
      <!-- Navbar -->
      <?php include_once 'include/navbar.php' ?>
      <?php 
        $jobID = $_GET['id'];
      ?>

      <!-- Sidebar -->
      <?php include_once 'include/sidebar.php' ?>
      
      <div class="page-wrapper">
        <div class="content container-fluid">

          <div class="page-header">
            <div class="row">
              <div class="col-sm-12">
                <div class="page-sub-header">
                  <h3 class="page-title">Welcome <?php echo $users_name ?>!</h3>
                  <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item"><a href="job">Job List</a></li>
                    <li class="breadcrumb-item active">Job Details ID : <?php echo $jobID ?></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <?php
            $sql = "
                    SELECT 
                      j.ncc_compliance, 
                      ca.client_account_name,
                      j.job_reference_no,
                      j.client_reference_no,
                      j.notes,
                      j.address_client,
                      j.priority,
                      j.job_type,
                      j.job_status,
                      j.upload_files,
                      j.upload_project_files,
                      j.job_reference_no,
                      j.staff_id,
                      j.checker_id
                    FROM jobs j
                    INNER JOIN client_accounts ca 
                      ON j.client_account_id = ca.client_account_id 
                    WHERE j.job_id = '$jobID';
                    ";
            $sql_query = mysqli_query($conn, $sql);
            $sql_fetch = mysqli_fetch_array($sql_query);

            $ncc_compliance = $sql_fetch['ncc_compliance'];
            $client = $sql_fetch['client_account_name'];
            $j_ref = $sql_fetch['job_reference_no'];
            $c_ref = $sql_fetch['client_reference_no'];
            $notes = $sql_fetch['notes'];
            $address = $sql_fetch['address_client'];
            $priority = $sql_fetch['priority'];
            $type = $sql_fetch['job_type'];
            $status = $sql_fetch['job_status'];
            $ref = $sql_fetch['job_reference_no'];
            $staff = $sql_fetch['staff_id'];
            $checker = $sql_fetch['checker_id'];

            // decode uploaded files
            $plans = json_decode($sql_fetch['upload_files'], true) ?? [];
            $docs  = json_decode($sql_fetch['upload_project_files'], true) ?? [];
         
            $disabled = '';
            if ($status === 'Completed'):
              $disabled = 'disabled';
            endif;
          ?>
          <div class="row">
            <!-- Client Details -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Client Details</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Client Reference</strong></span>
                    <span><?php echo $c_ref ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Job Reference</strong></span>
                    <span><?php echo $j_ref ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Compliance</strong></span>
                    <span><?php echo $ncc_compliance ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Client</strong></span>
                    <span><?php echo $client ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Notes</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="py-1">
                    <span><?php echo $notes ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Run Comments</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body" id="runCommentsBox">
                  <p class="text-muted">Loading run comments...</p>
                </div>
                <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
                <div class="card-footer">
                  <div class="input-group">
                    <input type="text" id="runCommentMessage" class="form-control" placeholder="Write a run comment...">
                    <button class="btn btn-primary" id="btnSendRunComment" <?php echo $disabled ?>>Send</button>
                  </div>
                </div>
                <?php endif; ?>
              </div>

              <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
              <!-- Comments -->
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Comments</h5>
                </div>

                <div class="card-body" id="commentsBox">
                  <p class="text-muted">Loading comments...</p>
                </div>

                <div class="card-footer">
                  <!-- Quill Editor Toolbar -->
                  <div id="toolbar">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-list" value="bullet"></button>
                    <button class="ql-list" value="ordered"></button>
                  </div>

                  <!-- Rich Text Editor -->
                  <div id="commentMessage" style="height:100px;"></div>

                  <div class="mt-2 text-end">
                    <button class="btn btn-primary" id="btnSendComment" <?php echo $disabled ?>>Send</button>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>            

            <!-- Job Details -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Job Details</h5>
                    </div>

                    <?php include_once 'extension/job_view/job_status.php' ?>

                  </div>
                </div>
                <div class="card-body">
                  <?php include_once 'extension/job_view/job_details.php' ?>
                </div>
              </div>

              <!-- Uploaded Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Client Uploaded Plans</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <?php include_once 'extension/job_view/plans.php' ?>
                </div>
              </div>

              <!-- Uploaded Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Client Uploaded Documents</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <?php include_once 'extension/job_view/document.php' ?>
                </div>
              </div>

              
              <!-- Staff Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Checker Uploaded Files</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <!-- Uploaded Files List -->
                  <div id="staffFilesBox" class="mt-3"></div>
                </div>
              </div>
              <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
              <!-- ADMIN ONLY -->
              <?php include_once 'extension/job_view/staff_upload_files.php' ?>
              <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-sm-12 col-md-4">
              <!-- Assigned -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Assigned</h5>
                    </div>
                  </div>
                </div>
                <?php include_once 'extension/job_view/assigned.php' ?>
              </div>

              <!-- Activity Logs -->
              <?php if ($_SESSION['role'] === 'LUNTIAN' || $_SESSION['role'] === 'LBS'): ?>
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Activity Logs</h5>
                    </div>
                  </div>
                </div>

                <?php include_once 'extension/job_view/activity_log.php' ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>

    <?php include_once 'include/footer.php' ?>

  </body>
  <script src="../function/job/view/activity.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/checker.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/comment.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/runcomment.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/staff.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/statusBadge.js?v=<?php echo time(); ?>"></script>
  <script src="../function/job/view/staff_upload.js?v=<?php echo time(); ?>"></script>
  <script>
    function getBadgeColor(status) {
      switch (status) {
        case "Pending": return "#F86C62";
        case "For Discussion": return "#6AB9CC";
        case "Revision Requested": return "#FAE2D4";
        case "For Email Confirmation": return "#7DB9E3";
        case "Allocated": return "#FFA775";
        case "Accepted": return "#FFD2B8";
        case "Processing": return "#FF8AD8";
        case "For Checking": return "#CF7AFA";
        case "Cancelled": return "#C4C4C4";
        case "Completed": return "#69F29B";
        case "Awaiting Further Information": return "#EDE59A";
        default: return "#6c757d";
      }
    }

    function applyBadgeColor(el, status) {
      $(el).css("background-color", getBadgeColor(status));
    }

    // Initial load
    $(document).ready(function () {
      let badge = $("#statusBadge");
      let currentStatus = badge.data("status");
      applyBadgeColor(badge, currentStatus);

      // When dropdown changes
      $("#jobStatus").on("change", function () {
        let newStatus = $(this).val();
        $("#statusBadge").text(newStatus).data("status", newStatus);
        applyBadgeColor("#statusBadge", newStatus);
      });
    });

  </script>
</html>
