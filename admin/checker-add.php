<!DOCTYPE html>
<html lang="en">
<?php include_once 'include/header.php' ?>

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
                <h3 class="page-title">Welcome <?php echo $_SESSION['role'] ?>!</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="index">Home</a></li>
                  <li class="breadcrumb-item"><a href="checker.php">Checker List</a></li>
                  <li class="breadcrumb-item active">Add New Checker</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Client Form -->
        <form id="addClientForm" class="shadow p-4 rounded bg-white">
          <div class="mb-3">
            <label class="form-label fw-bold">Checker Code</label>
            <input type="text" name="client_code" class="form-control" placeholder="Enter client code" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Checker Name</label>
            <input type="text" name="client_name" class="form-control" placeholder="Enter client name" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Checker Email</label>
            <input type="email" name="client_email" class="form-control" placeholder="Enter client email" required>
          </div>

          <button type="submit" class="btn btn-primary">Add Checker</button>
          <a href="client.php" class="btn btn-secondary">Back</a>
        </form>

      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $(document).ready(function() {
      // ✅ Handle form submission
      $("#addClientForm").submit(function(e) {
        e.preventDefault();

        $.ajax({
          url: "../controller/checker/add-checker.php",
          type: "POST",
          data: $(this).serialize(),
          dataType: "json",
          success: function(res) {
            if (res.status === "success") {
              toastr.success(res.message);
              setTimeout(() => window.location.href = "checker.php", 1500);
            } else {
              toastr.error(res.message);
            }
          },
          error: function(xhr) {
            toastr.error("Error adding client: " + xhr.responseText);
          }
        });
      });
    });
  </script>
</body>
</html>
