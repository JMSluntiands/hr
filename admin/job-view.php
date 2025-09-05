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
                      <h5 class="card-title">Run Comments</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body" id="runCommentsBox">
                  <p class="text-muted">Loading run comments...</p>
                </div>
                <div class="card-footer">
                  <div class="input-group">
                    <input type="text" id="runCommentMessage" class="form-control" placeholder="Write a run comment...">
                    <button class="btn btn-primary" id="btnSendRunComment">Send</button>
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
                    <span style="max-width: 50%"><?php echo $address ?></span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center py-1">
                    <span><strong>Priority</strong></span>
                    <span style="max-width: 50%"><?php echo $priority ?></span>
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
                      <h5 class="card-title">Uploaded Plans</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <!-- <h5>Plans</h5> -->
                  <?php if (!empty($plans)): ?>
                    <ul class="list-group">
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
                      <h5 class="card-title">Uploaded Documents</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div>
                    <!-- <h5>Documents</h5> -->
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

              <!-- Staff Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h5 class="card-title">Staff Uploaded Files</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <!-- Uploaded Files List -->
                  <div id="staffFilesBox" class="mt-3"></div>
                </div>
              </div>

              <!-- STAFF ONLY -->
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
  <script src="../function/job/view/activity.js"></script>
  <script src="../function/job/view/checker.js"></script>
  <script src="../function/job/view/comment.js"></script>
  <script src="../function/job/view/runcomment.js"></script>
  <script src="../function/job/view/staff.js"></script>
  <script src="../function/job/view/statusBadge.js"></script>
  <script src="../function/job/view/staff_upload.js"></script>
</html>
