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
                      <h5 class="card-title mb-2">Client List</h5>
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
                          <th>Client Code</th>
                          <th>Client Name</th>
                          <th>Client Email</th>
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
  <script src="../function/client/client_list.js?v=<?php echo time(); ?>"></script>
  <script>
    $(document).ready(function () {
      loadClients();
    });

    function loadClients() {
      $.ajax({
        url: "../controller/client/fetch-clients.php",
        type: "GET",
        dataType: "json",
        success: function (response) {
          console.log("Response from server:", response);

          if (!response.data || !Array.isArray(response.data)) {
            toastr.error("Invalid response format from server.", "Error");
            return;
          }

          // Assuming you are using DataTables
          let table = $("#jobTable").DataTable();
          table.clear().draw();

          response.data.forEach(item => {
            table.row.add([
              `<div class="d-flex justify-content-center">
                <a href="client-edit.php?id=${item.id}" class="btn btn-sm btn-primary me-1">
                  <i class="fa fa-edit"></i> Edit
                </a>
              </div>`,
              formatDate(item.log_date),
              item.client_code,
              item.client_name,
              item.client_email
            ]).draw(false);
          });

          $("#jobCount").text("Total Records: " + response.data.length);
        },
        error: function (xhr) {
          toastr.error("Error fetching data: " + xhr.responseText, "Error");
        }
      });
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
      });
    }

    // function deleteClient(id) {
    //   if (!confirm("Are you sure you want to delete this client?")) return;

    //   $.ajax({
    //     url: "../controller/client/delete-client.php",
    //     type: "POST",
    //     data: { id },
    //     dataType: "json",
    //     success: function (res) {
    //       if (res.status === "success") {
    //         toastr.success(res.message);
    //         loadClients(); // refresh table
    //       } else {
    //         toastr.error(res.message);
    //       }
    //     },
    //     error: function (xhr) {
    //       toastr.error("Error deleting client: " + xhr.responseText);
    //     }
    //   });
    // }

  </script>
</html>
