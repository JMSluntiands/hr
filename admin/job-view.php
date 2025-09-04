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

            $badgeColor = "#6c757d"; // default gray

            switch ($priority) {
              case "Top (COB)":
                $badgeColor = "#F74639"; // red
                break;
              case "High 1 day":
                $badgeColor = "#FFA775"; // orange
                break;
              case "Standard 2 days":
                $badgeColor = "#FF71CF"; // pink
                break;
              case "Standard 3 days":
                $badgeColor = "#CF7AFA"; // violet
                break;
            }

            // decode uploaded files
            $plans = json_decode($sql_fetch['upload_files'], true) ?? [];
            $docs  = json_decode($sql_fetch['upload_project_files'], true) ?? [];
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
                      <h5 class="card-title">Run Notes</h5>
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
                      <h5 class="card-title">Comments</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body" id="commentsBox">
                  <p class="text-muted">Loading comments...</p>
                </div>
                <div class="card-footer">
                  <div class="input-group">
                    <input type="text" id="commentMessage" class="form-control" placeholder="Write a comment...">
                    <button class="btn btn-primary" id="btnSendComment">Send</button>
                  </div>
                </div>
              </div>

            </div>

            <!-- Job Details -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Job Details</h5>
                    </div>
                    <div>
                      <!-- Badge display -->
                      <span id="statusBadge" 
                            class="badge text-dark" 
                            style="background-color: <?php echo $badgeColor; ?>; font-weight:bold; cursor:pointer;">
                        <?php echo htmlspecialchars($status); ?>
                      </span>

                      <!-- Hidden dropdown -->
                      <select id="jobStatus" 
                              class="form-select form-select-sm d-none"
                              style="width:auto; display:inline-block;">
                        <option value="Allocated" <?php echo ($status == 'Allocated') ? 'selected' : ''; ?>>Allocated</option>
                        <option value="Accepted" <?php echo ($status == 'Accepted') ? 'selected' : ''; ?>>Accepted</option>
                        <option value="Processing" <?php echo ($status == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="For Checking" <?php echo ($status == 'For Checking') ? 'selected' : ''; ?>>For Checking</option>
                        <option value="Completed" <?php echo ($status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Awaiting Further Information" <?php echo ($status == 'Awaiting Further Information') ? 'selected' : ''; ?>>Awaiting Further Information</option>
                        <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="For Discussion" <?php echo ($status == 'For Discussion') ? 'selected' : ''; ?>>For Discussion</option>
                        <option value="Revision Requested" <?php echo ($status == 'Revision Requested') ? 'selected' : ''; ?>>Revision Requested</option>
                        <option value="Revised" <?php echo ($status == 'Revised') ? 'selected' : ''; ?>>Revised</option>
                      </select>

                      <input type="hidden" id="jobID" value="<?php echo $jobID; ?>">
                    </div>
                  </div>

                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Job Address</strong></span>
                    <span><?php echo $address ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Priority</strong></span>
                    <span><?php echo $priority ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Job Type</strong></span>
                    <span style="max-width: 50%"><?php echo $type ?></span>
                  </div>
                </div>
              </div>

              <!-- Uploaded Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Uploaded Files</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <h5>Plans</h5>
                  <?php if (!empty($plans)): ?>
                    <ul class="list-group mb-3">
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
                </div>
              </div>

              <!-- Uploaded Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Uploaded Files</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div>
                    <h5>Documents</h5>
                  </div>
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
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Assigned</h5>
                    </div>
                  </div>
                </div>
                <?php
                  $staffList = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY name");
                  $checkerList = mysqli_query($conn, "SELECT checker_id, name FROM checker ORDER BY name");
                ?>

                <div class="card-body">
                  <!-- Staff -->
                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Staff</strong></span>
                    <select id="staffSelect" class="form-select form-select-sm" style="width: 60%;">
                      <option value="">-- Select Staff --</option>
                      <?php while($s = mysqli_fetch_assoc($staffList)): ?>
                        <option value="<?php echo $s['staff_id']; ?>" <?php echo ($staff == $s['staff_id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($s['staff_id']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <!-- Checker -->
                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Checker</strong></span>
                    <select id="checkerSelect" class="form-select form-select-sm" style="width: 60%;">
                      <option value="">-- Select Checker --</option>
                      <?php while($c = mysqli_fetch_assoc($checkerList)): ?>
                        <option value="<?php echo $c['checker_id']; ?>" <?php echo ($checker == $c['checker_id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($c['checker_id']); ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>

                  <input type="hidden" id="jobID" value="<?php echo $jobID; ?>">
                </div>

              </div>
              <?php if ($_SESSION['role'] === 'LUNTIAN'): ?>
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Activity Logs</h5>
                    </div>
                  </div>
                </div>

                <!-- Activity Logs Card -->
                <div class="card-body" id="activityLogs" style="max-height: 400px; overflow-y: auto;">
                  <p class="text-muted">Loading activity logs...</p>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>

    <?php include_once 'include/footer.php' ?>

  </body>
  <script>
    $(document).ready(function(){

      // ✅ Function para i-load ulit ang activity logs
      function loadActivityLogs() {
        let jobID = $("#jobID").val();
        $("#activityLogs").load("../controller/job/view_activity_logs.php?job_id=" + jobID);
      }

      // Initial load
      loadActivityLogs();

      // 🔹 Badge click → show dropdown
      $("#statusBadge").on("click", function(){
        $(this).addClass("d-none");
        $("#jobStatus").removeClass("d-none").focus();
      });

      // 🔹 Change dropdown → update status via AJAX
      $("#jobStatus").on("change", function(){
        let jobID = $("#jobID").val();
        let newStatus = $(this).val();

        $.ajax({
          url: "../controller/job/view_update_status.php",
          type: "POST",
          data: { job_id: jobID, job_status: newStatus },
          dataType: "json",
          success: function(response){
            if(response.success){
              toastr.success(response.message, "Success");

              // update badge text
              $("#statusBadge")
                .text(newStatus)
                .removeClass("d-none");

              // hide dropdown again
              $("#jobStatus").addClass("d-none");

              // refresh logs
              loadActivityLogs();
            } else {
              toastr.error(response.message || "Failed to update status", "Error");
              $("#statusBadge").removeClass("d-none");
              $("#jobStatus").addClass("d-none");
            }
          },
          error: function(xhr){
            toastr.error("Error fetching data: " + xhr.responseText, "Error");
            $("#statusBadge").removeClass("d-none");
            $("#jobStatus").addClass("d-none");
          }
        });
      });


      // 🔹 Update Staff
      $("#staffSelect").on("change", function(){
        let jobID = $("#jobID").val();
        let staffID = $(this).val();

        $.ajax({
          url: "../controller/job/view_update_assigned.php",
          type: "POST",
          data: { job_id: jobID, staff_id: staffID },
          dataType: "json",
          success: function(response){
            if(response.success){
              toastr.success(response.message, "Success");
              loadActivityLogs();
            } else {
              toastr.error(response.message || "Failed to update staff", "Error");
            }
          },
          error: function(xhr){
            toastr.error("Error: " + xhr.responseText, "Error");
          }
        });
      });

      // 🔹 Update Checker
      $("#checkerSelect").on("change", function(){
        let jobID = $("#jobID").val();
        let checkerID = $(this).val();

        $.ajax({
          url: "../controller/job/view_update_assigned.php",
          type: "POST",
          data: { job_id: jobID, checker_id: checkerID },
          dataType: "json",
          success: function(response){
            if(response.success){
              toastr.success(response.message, "Success");
              loadActivityLogs();
            } else {
              toastr.error(response.message || "Failed to update checker", "Error");
            }
          },
          error: function(xhr){
            toastr.error("Error: " + xhr.responseText, "Error");
          }
        });
      });

      let commentOffset = 0;
      const commentLimit = 5;

      // 🔹 Function para mag-load ng comments (una + load more)
function loadComments(offset = 0, append = false){
  let jobID = $("#jobID").val();

  $.get("../controller/job/view_comments.php", { job_id: jobID, offset: offset }, function(data){
    if(append){
      $("#commentsBox").append(data);
    } else {
      $("#commentsBox").html(data);
    }
  });
}

// Initial load (offset 0)
loadComments();

// 🔹 Handle "View More" button click (delegate kasi dynamic sya)
$(document).on("click", ".view-more", function(){
  let offset = $(this).data("offset");
  $(this).remove(); // alisin muna yung button para hindi madoble
  loadComments(offset, true);
});


      // 🔹 Send comment
      $("#btnSendComment").on("click", function(){
        let jobID = $("#jobID").val();
        let message = $("#commentMessage").val().trim();

        if(message === ""){
          toastr.warning("Please enter a comment.");
          return;
        }

        // ⏰ Gumamit ng device time (formatted gaya ng activity logs)
        let createdAt = new Date();
        let options = { month: "short", day: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", hour12: true };
        let formattedTime = createdAt.toLocaleString("en-US", options);

        $.ajax({
          url: "../controller/job/view_add_comments.php",
          type: "POST",
          data: { job_id: jobID, message: message, created_at: formattedTime },
          dataType: "json",
          success: function(response){
            if(response.success){
              toastr.success(response.message, "Success");
              $("#commentMessage").val("");
              loadComments();
            } else {
              toastr.error(response.message || "Failed to add comment", "Error");
            }
          },
          error: function(xhr){
            toastr.error("Error: " + xhr.responseText, "Error");
          }
        });
      });

    });
  </script>
</html>
