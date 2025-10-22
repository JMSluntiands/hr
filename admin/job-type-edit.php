<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>

<?php
include '../database/db.php';
// session_start();

// Kunin yung ID ng job request sa URL
$job_request_id = $_GET['id'] ?? '';
if (empty($job_request_id)) {
  die("<div class='alert alert-danger m-3'>Invalid Job Request ID.</div>");
}

// Kunin details ng job request
$stmt = $conn->prepare("SELECT * FROM job_requests WHERE id = ?");
$stmt->bind_param("s", $job_request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  die("<div class='alert alert-danger m-3'>Job Request not found.</div>");
}

$data = $result->fetch_assoc();
?>

<body>
  <div class="main-wrapper">
    <?php include_once 'include/navbar.php' ?>
    <?php include_once 'include/announcement.php' ?>
    <?php include_once 'include/sidebar.php' ?>

    <div class="page-wrapper" style="padding-top: 105px;">
      <div class="content container-fluid">

        <div class="page-header">
          <div class="row">
            <div class="col-sm-12">
              <div class="page-sub-header">
                <h3 class="page-title">Edit Job Request</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="job-type.php">Job Type List</a></li>
                  <li class="breadcrumb-item active">Edit Job Request</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ Edit Job Request Form -->
        <form id="editJobRequestForm" class="shadow p-4 rounded bg-white">
          <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($data['job_request_id']); ?>">

          <div class="mb-3">
            <label class="form-label fw-bold">Client Code</label>
            <select class="form-select select" name="client_code" required>
              <option value="">Select Client</option>
              <?php
                $q = mysqli_query($conn, "SELECT * FROM clients");
                while ($r = mysqli_fetch_assoc($q)) {
                  $selected = ($r['client_code'] == $data['client_code']) ? 'selected' : '';
                  echo "<option value='{$r['client_code']}' $selected>".htmlspecialchars($r['client_code'])."</option>";
                }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Job Request ID</label>
            <input type="text" name="job_request_id" class="form-control"
              value="<?php echo htmlspecialchars($data['job_request_id']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Job Request Type</label>
            <input type="text" name="job_request_type" class="form-control"
              value="<?php echo htmlspecialchars($data['job_request_type']); ?>" required>
          </div>

          <button type="submit" class="btn btn-primary">Update Job Request</button>
          <a href="job-type.php" class="btn btn-secondary">Back</a>
        </form>

      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $(document).ready(function() {
      $('.select').select2({ width: '100%', minimumResultsForSearch: 2 });

      $("#editJobRequestForm").submit(function(e) {
        e.preventDefault();

        $.ajax({
          url: "../controller/job-type/update-job-type.php",
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
            toastr.error("Error updating job request: " + xhr.responseText);
          }
        });
      });
    });
  </script>
</body>
</html>
