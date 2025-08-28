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

          <div class="row">
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

                </div>
              </div>
            </div>

            <div class="col-sm-12 col-md-4">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Job Details</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">

                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Uploaded Files</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">

                </div>
              </div>
            </div>

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

                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <div class="d-flex justify-content-between items-center">
                    <div>
                      <h5 class="card-title">Activity Logs</h5>
                    </div>
                  </div>
                </div>
                <div class="card-body">

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
