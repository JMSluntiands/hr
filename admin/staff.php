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
                  <h3 class="page-title">Welcome <?php echo $_SESSION['role'] ?>!</h3>
                  <ul class="breadcrumb">
                    <li class="breadcrumb-item">
                      <a href="index">Home</a>
                    </li>
                    <li class="breadcrumb-item active">Client List</li>
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
                      <h5 class="card-title mb-2">Staff List</h5>
                      <span id="jobCount" class="text-muted">Total Records: 0</span>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="datatable table table-stripped" id="jobTable">
                      <thead>
                        <tr>
                          <th>Action</th>
                          <th>Staff Code</th>
                          <th>Staff Name</th>
                          <th>Staff Email</th>
                        </tr>
                      </thead>
                      <tbody id="clientBody">
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
  </body>
  <script src="../function/staff/staff-list.js?v=<?php echo time(); ?>"></script>
</html>
