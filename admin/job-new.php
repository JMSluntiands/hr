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
                <h3 class="page-title">Welcome <?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?>!</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="job">Job List</a></li>
                  <li class="breadcrumb-item active">Add New Job</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <form id="addJobForm" enctype="multipart/form-data">

          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header"><h5>Add New Job</h5></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12"><h5 class="text-center">Client Details</h5></div>

                    <!-- Reference No. (restricted) -->
                    <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
                      <div class="col-md-4 col-sm-12 mt-3">
                        <label class="form-label">Reference No.</label>
                        <input type="text" name="reference" class="form-control" placeholder="Enter Reference No." required autocomplete="off">
                      </div>
                    <?php endif; ?>

                    <!-- Client Reference (always visible) -->
                    <div class="col-md-4 col-sm-12 mt-3">
                      <label class="form-label">Client Reference</label>
                      <input type="text" name="client_ref" class="form-control" placeholder="Enter Client Reference" autocomplete="off">
                    </div>

                    <!-- Compliance -->
                    <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
                      <div class="col-md-4 col-sm-12 mt-3">
                        <label class="form-label">Compliance</label>
                        <select name="compliance" class="form-select select" required>
                          <option value="2022 (WHO)" selected>2022 (WHO)</option>
                          <option value="2019">2019</option>
                        </select>
                      </div>
                    <?php endif; ?>

                    <!-- Client -->
                    <?php if ($_SESSION['role'] === 'LBS' || $_SESSION['role'] === 'LUNTIAN'): ?>
                      <div class="col-md-12 col-sm-12 mt-3">
                        <label>Client</label>
                        <select class="form-select select" name="client_account_id">
                          <?php
                            $clientDefault = "Summit Homes Group";
                            $client_sql = "SELECT client_account_id, client_account_name FROM client_accounts ORDER BY client_account_name";
                            $client_query = mysqli_query($conn, $client_sql);
                            while ($cData = mysqli_fetch_assoc($client_query)) {
                              $selected = ($cData['client_account_name'] === $clientDefault) ? "selected" : "";
                              echo "<option value='{$cData['client_account_id']}' $selected>" . htmlspecialchars($cData['client_account_name']) . "</option>";
                            }
                          ?>
                        </select>
                      </div>
                    <?php endif; ?>

                    <div class="col-12"><hr></div>
                    <div class="col-12 mt-3"><h5 class="text-center">Job Details</h5></div>

                    <!-- Address -->
                    <div class="col-md-12 mt-3">
                      <label>Job Address</label>
                      <textarea class="form-control" name="address" rows="2" placeholder="Complete Address"></textarea>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-12 mt-3">
                      <label>Priority</label>
                      <select class="form-select select" name="priority">
                        <?php
                          $priorities = ["Top (COB)","High 1 day","Standard 2 days","Standard 3 days","Standard 4 days","Low 5 days","Low 6 days","Low 7 days"];
                          foreach ($priorities as $p) {
                            $sel = ($p === "Top (COB)") ? "selected" : "";
                            echo "<option value='".htmlspecialchars($p)."' $sel>".htmlspecialchars($p)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-12 mt-3">
                      <label>Job Type</label>
                      <select class="form-select select" name="jobRequest">
                        <?php
                          $assign = 'EA_LBS_1SDB';
                          $q = mysqli_query($conn, "SELECT * FROM job_requests");
                          while ($r = mysqli_fetch_assoc($q)) {
                            $sel = ($r['job_request_id']==$assign) ? "selected" : "";
                            echo "<option value='{$r['job_request_id']}' $sel>".htmlspecialchars($r['job_request_type'])."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-12 mt-3">
                      <label>Job Status</label>
                      <select class="form-select select" name="status">
                        <?php
                          $statuses = ["Allocated","Accepted","Processing","For Checking","Completed","Awaiting Further Information","Pending","For Discussion","Revision Requested","Revised"];
                          foreach ($statuses as $st) {
                            echo "<option value='".htmlspecialchars($st)."' $sel>".htmlspecialchars($st)."</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12 mt-3">
                      <label>Notes</label>
                      <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($job['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-6 col-sm-12 mt-3">
                      <label class="form-label d-flex justify-content-between">
                        Upload Plans 
                        <span class="badge bg-secondary" id="plansCount">0 files</span>
                      </label>
                      <input type="file" class="form-control" id="uploadPlans" multiple accept="application/pdf" name="plans[]"  style="height:30px!important">
                      <div id="plansPreview" class="mt-2"></div>
                    </div>

                    <div class="col-md-6 col-sm-12 mt-3">
                      <label class="form-label d-flex justify-content-between">
                        Upload Document
                        <span class="badge bg-secondary" id="docsCount">0 files</span>
                      </label>
                      <input type="file" class="form-control" id="uploadDocs" multiple accept="application/pdf" name="docs[]"  style="height:30px!important">
                      <div id="docsPreview" class="mt-2"></div>
                    </div>

                    <!-- Assigned / Checked -->
                    <div class="col-md-6 col-sm-12 mt-3">
                      <label>Assigned To</label>
                      <select class="form-select select" name="assigned">
                        <?php
                          $staff_default = "GM"; // default staff
                          $q = mysqli_query($conn, "SELECT staff_id, name FROM staff ORDER BY name");
                          while ($r = mysqli_fetch_assoc($q)) {
                            $sel = ($r['staff_id'] === $staff_default) ? "selected" : "";
                            echo "<option value='{$r['staff_id']}' $sel>" . htmlspecialchars($r['staff_id']) . "</option>";
                          }
                        ?>
                      </select>
                    </div>

                    <div class="col-md-6 col-sm-12 mt-3">
                      <label>Checked By</label>
                      <select class="form-select select" name="checked">
                        <?php
                          $checker_default = "GM"; // default checker
                          $q2 = mysqli_query($conn, "SELECT checker_id, name FROM checker ORDER BY name");
                          while ($r2 = mysqli_fetch_assoc($q2)) {
                            $sel = ($r2['checker_id'] === $checker_default) ? "selected" : "";
                            echo "<option value='{$r2['checker_id']}' $sel>" . htmlspecialchars($r2['checker_id']) . "</option>";
                          }
                        ?>
                      </select>
                    </div>


                    <div class="col-12 mt-4">
                      <button type="submit" class="btn btn-primary">Add Job</button>
                    </div>

                  </div><!-- row -->
                </div><!-- card-body -->
              </div><!-- card -->
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $(document).ready(function () {
      $('.select').select2({ width:'100%', minimumResultsForSearch: Infinity });

      let plansFiles = [];
      let docsFiles = [];

    function refreshPreview(fileArray, previewContainer, countContainer) {
      let $preview = $("#" + previewContainer);
      $preview.empty();

      $.each(fileArray, function (i, file) {
          let $row = $(`
            <div class="d-flex align-items-center border p-2 mb-1 rounded file-row">
              <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
              <span class="flex-grow-1">${file.name}</span>
              <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${i}" data-target="${previewContainer}">
                <i class="fa fa-times"></i>
              </button>
            </div>
          `);
          $preview.append($row);
        });

        $("#" + countContainer).text(fileArray.length + " file(s)");
    }

    $("#uploadPlans").on("change", function (e) {
      let files = Array.from(e.target.files);
      plansFiles = plansFiles.concat(files);
      refreshPreview(plansFiles, "plansPreview", "plansCount");
      $(this).val("");
    });

    $("#uploadDocs").on("change", function (e) {
      let files = Array.from(e.target.files);
      docsFiles = docsFiles.concat(files);
      refreshPreview(docsFiles, "docsPreview", "docsCount");
      $(this).val("");
    });

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

    $("#addJobForm").on("submit", function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        plansFiles.forEach((file, i) => {
          formData.append("plans[]", file);
        });

        docsFiles.forEach((file, i) => {
          formData.append("docs[]", file);
        });

        $.ajax({
          url: "../controller/job/job_save.php",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          dataType: "json",
          beforeSend: function () {
            $("#addJobForm button[type=submit]").prop("disabled", true).text("Saving...");
          },
          success: function (response) {
            if (response.status === "success") {
              toastr.success(response.message, "Success");
              $("#addJobForm")[0].reset();
              $("#plansPreview, #docsPreview").empty();
              $("#plansCount").text("0 files");
              $("#docsCount").text("0 files");
            } else {
              toastr.error(response.message, "Error");
            }
          },
          error: function (xhr, status, error) {
            toastr.error("Invalid response format from server.", "Error");
          },
          complete: function () {
            $("#addJobForm button[type=submit]").prop("disabled", false).text("Add Job");
          }
        });
      });
    });
  </script>
</body>
</html>
