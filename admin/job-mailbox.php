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
                    <li class="breadcrumb-item active">Job Trash</li>
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
                      <h5 class="card-title mb-2">Job Mailbox</h5>
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
                          <th>Log Date</th>
                          <th>Job Reference</th>
                          <th>To</th>
                          <th>Email Format</th>
                          <th>Files</th>
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
    
    <div class="modal fade" id="emailFormatModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
          <div class="modal-header border-0">
            <h5 class="modal-title">Email Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="text-center">
              <img src="../img/logo-light.png" alt="Logo" style="height:60px;">
              <h4 class="mt-3">Hi there!</h4>
              <p class="fs-5 fw-bold text-warning" id="emailReference"></p>
              <p class="mb-1">status has been updated to</p>
              <p class="fs-5 fw-bold text-warning" id="emailStatus"></p>

              <div class="mt-3">
                <p class="mb-0">Assessor: <span id="emailAssessor"></span></p>
                <p>Assessor Email: <a href="#" id="emailAssessorEmail"></a></p>
              </div>

              <div class="mt-4 text-center">
                <h6 class="fw-bold">Submission Notes:</h6>
                <p>
                  Click or copy & paste link to browser to access NatHERS Climate Zone Map  
                  <a href="http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html" target="_blank">
                    http://www.nathers.gov.au/sites/all/themes/custom//climate-map/index.html
                  </a>
                </p>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

  </body>
  <script src="../function/job/email_verification.js?v=<?php echo time(); ?>"></script>
</html>
