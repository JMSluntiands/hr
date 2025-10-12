$(document).ready(function () {
  loadClients();
});

function loadClients() {
  $.ajax({
    url: "../controller/checker/fetch-checker.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
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
            <a href="checker-edit.php?id=${item.id}" class="btn btn-sm btn-primary me-1">
              <i class="fa fa-edit"></i> Edit
            </a>
          </div>`,
          item.checker_id,
          item.name,
          item.username
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