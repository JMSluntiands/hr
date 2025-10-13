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
                <h3 class="page-title">Welcome <?php echo $_SESSION['role'] ?>!</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="job-type.php">Job Type List</a></li>
                  <li class="breadcrumb-item active">Add New Job Type</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ Add Job Request Form -->
        <form id="addJobRequestForm" class="shadow p-4 rounded bg-white">
          <div class="mb-3">
            <label class="form-label fw-bold">Client Code</label>
            <select class="form-select select" name="client_code" required>
              <option value="">Select Client</option>
              <?php
                $q = mysqli_query($conn, "SELECT * FROM clients");
                while ($r = mysqli_fetch_assoc($q)) {
                  echo "<option value='{$r['client_code']}'>".htmlspecialchars($r['client_code'])."</option>";
                }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Job Request ID</label>
            <input type="text" name="job_request_id" class="form-control" placeholder="Enter job request ID" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Job Request Type</label>
            <input type="text" name="job_request_type" class="form-control" placeholder="Enter job request type" required>
          </div>

          <button type="submit" class="btn btn-primary">Add Job Request</button>
          <a href="job-type.php" class="btn btn-secondary">Back</a>
        </form>

      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $(document).ready(function() {
      $('.select').select2({ width: '100%', minimumResultsForSearch: 2 });

      $("#addJobRequestForm").submit(function(e) {
        e.preventDefault();

        $.ajax({
          url: "../controller/job-type/add-job-type.php",
          type: "POST",
          data: $(this).serialize(),
          dataType: "json",
          success: function(res) {
            if (res.status === "success") {
              toastr.success(res.message);
              setTimeout(() => window.location.href = "job-type.php", 1500);
            } else {
              toastr.error(res.message);
            }
          },
          error: function(xhr) {
            toastr.error("Error adding job request: " + xhr.responseText);
          }
        });
      });
    });
  </script>
</body>
</html>
