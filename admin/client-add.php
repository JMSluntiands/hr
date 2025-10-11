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
                  <li class="breadcrumb-item"><a href="client">Client List</a></li>
                  <li class="breadcrumb-item active">Add New Client</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <form id="addClientForm">
          <div class="mb-3">
            <label>Client Code</label>
            <input type="text" name="client_code" class="form-control" placeholder="Enter client code" required>
          </div>

          <div class="mb-3">
            <label>Client Name</label>
            <input type="text" name="client_name" class="form-control" placeholder="Enter client name" required>
          </div>

          <div class="mb-3">
            <label>Client Email</label>
            <input type="email" name="client_email" class="form-control" placeholder="Enter client email" required>
          </div>

          <button type="submit" class="btn btn-primary">Add Client</button>
          <a href="client.php" class="btn btn-secondary">Back</a>
        </form>

      </div>
    </div>
  </div>

  <?php include_once 'include/footer.php' ?>

  <script>
    $("#addClientForm").submit(function(e) {
      e.preventDefault();

      $.ajax({
        url: "../controller/client/add-client.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res) {
          if (res.status === "success") {
            toastr.success(res.message);
            setTimeout(() => window.location.href = "client.php", 1500);
          } else {
            toastr.error(res.message);
          }
        },
        error: function(xhr) {
          toastr.error("Error adding client: " + xhr.responseText);
        }
      });
    });
  </script>
</body>
</html>
