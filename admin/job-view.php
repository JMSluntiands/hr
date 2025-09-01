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
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Client Details</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Client Reference</strong></span>
                    <span><?php echo $c_ref ?></span>
                  </div>

                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Job Reference</strong></span>
                    <span><?php echo $j_ref ?></span>
                  </div>

                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Compliance</strong></span>
                    <span><?php echo $ncc_compliance ?></span>
                  </div>

                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Client</strong></span>
                    <span><?php echo $client ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
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
            </div>

            <!-- Job Details -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Job Details</h5>
                    </div>
                    <div>
                      <span class="badge text-dark" style="background-color: <?php echo $badgeColor; ?>">
                        <?php echo htmlspecialchars($status); ?>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Job Address</strong></span>
                    <span><?php echo $address ?></span>
                  </div>

                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Priority</strong></span>
                    <span><?php echo $priority ?></span>
                  </div>

                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Job Type</strong></span>
                    <span style="max-width: 50%"><?php echo $type ?></span>
                  </div>
                </div>
              </div>

              <!-- Uploaded Files -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
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

                  <h5>Documents</h5>
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

            <!-- Right Column -->
            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Assigned</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Staff</strong></span>
                    <span style="max-width: 50%"><?php echo $staff ?></span>
                  </div>
                  <div class="d-flex justify-content-between items-center py-1">
                    <span><strong>Checker</strong></span>
                    <span style="max-width: 50%"><?php echo $checker ?></span>
                  </div>
                </div>
              </div>

              <!-- Activity Logs Card -->
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Activity Logs</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                  <?php
                    $log_sql = "
                      SELECT log_id, activity_date, activity_type, activity_description, updated_by 
                      FROM activity_log 
                      WHERE job_id = '$jobID' 
                      ORDER BY activity_date DESC
                    ";
                    $log_query = mysqli_query($conn, $log_sql);
                  ?>

                  <?php if (mysqli_num_rows($log_query) > 0): ?>
                    <ul class="list-group list-group-flush">
                      <?php while ($log = mysqli_fetch_assoc($log_query)): ?>
                        <li class="list-group-item">
                          <div class="d-flex justify-content-between">
                            <span class="fw-bold text-primary">
                              <?php echo htmlspecialchars($log['activity_type']); ?>
                            </span>
                            <small class="text-muted">
                              <?php echo date("Y-m-d H:i", strtotime($log['activity_date'])); ?>
                            </small>
                          </div>
                          <p class="mb-1">
                            <?php 
                              // preserve line breaks
                              echo nl2br(htmlspecialchars($log['activity_description'])); 
                            ?>
                          </p>
                          <small class="text-secondary">
                            Updated by: <strong><?php echo htmlspecialchars($log['updated_by']); ?></strong>
                          </small>
                        </li>
                      <?php endwhile; ?>
                    </ul>
                  <?php else: ?>
                    <p class="text-muted">No activity logs found.</p>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>

    <?php include_once 'include/footer.php' ?>

  </body>
</html>
