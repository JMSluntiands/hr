<!DOCTYPE html>
<html lang="en">
  <?php include_once 'include/header.php' ?>
  <body>
    <div class="main-wrapper">
      <!-- Navbar -->
      <?php include_once 'include/navbar.php' ?>
      

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
                    <li class="breadcrumb-item">
                      <a href="index">Home</a>
                    </li>
                    <li class="breadcrumb-item active">Job Management</li>                                                                       
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title mb-2">Job List</h5>
                      <span id="jobCount" class="text-muted">Total Records: 0</span>
                    </div>
                    <div class="">
                      <a class="btn text-white btn-danger" href="job-new">New Job</a>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="datatable table table-stripped" id="jobTable">
                      <thead>
                        <tr>
                          <th>Action</th>
                          <th>Log Date</th>
                          <th>Client</th>
                          <th>Client Name</th>
                          <th>Reference</th>
                          <th>Job Type</th>
                          <th>Priority</th>
                          <!-- <th>Client Ref</th> -->
                          <th>Staff</th>
                          <th>Checker</th>
                          <th>Status</th>
                          <th>Due Date</th>
                          <th>Completion Date</th>
                          <th>Complexity</th>
                        </tr>
                      </thead>
                      <tbody id="jobBody">
                        <!-- Data will be inserted here -->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <?php include_once 'include/footer.php' ?>
    <?php include_once '../modal/job/new_job.php' ?>
  </body>
  <script src="../function/job/list_completed.js?v=<?php echo time(); ?>"></script>
</html>
