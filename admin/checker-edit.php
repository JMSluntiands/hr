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
                <h3 class="page-title">Edit Checker</h3>
                <ul class="breadcrumb">
                  <li class="breadcrumb-item"><a href="checker.php">Checker List</a></li>
                  <li class="breadcrumb-item active">Edit Checker</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <?php
        include '../database/db.php';

        $client_code = $_GET['id'] ?? '';
        // echo "SELECT * FROM clients WHERE id = $client_code";
        $query = $conn->prepare("SELECT * FROM checker WHERE id = ?");
        $query->bind_param("i", $client_code);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 0) {
          echo "<p class='text-danger'>Checker not found.</p>";
          exit;
        }

        $client = $result->fetch_assoc();
        ?>

        <!-- Edit Client Form -->
        <form id="updateClientForm" class="shadow p-4 rounded bg-white">
          <input type="hidden" name="original_code" value="<?php echo htmlspecialchars($client['checker_id']); ?>">

          <div class="mb-3">
            <label class="form-label fw-bold">Client Code</label>
            <input type="text" name="client_code" class="form-control" 
                   value="<?php echo htmlspecialchars($client['checker_id']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Client Name</label>
            <input type="text" name="client_name" class="form-control" 
                   value="<?php echo htmlspecialchars($client['name']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Client Email</label>
            <input type="email" name="client_email" class="form-control" 
                   value="<?php echo htmlspecialchars($client['username']); ?>" required>
          </div>

          <button type="submit" class="btn btn-primary">Update Client</button>
          <a href="client.php" class="btn btn-secondary">Cancel</a>
        </form>

      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $(document).ready(function() {
      $("#updateClientForm").submit(function(e) {
        e.preventDefault();

        $.ajax({
          url: "../controller/checker/update-checker.php",
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
            toastr.error("Error updating checker: " + xhr.responseText);
          }
        });
      });
    });
  </script>
</body>
</html>
