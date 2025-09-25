let table = $("#jobTable").DataTable({
  responsive: true,
  autoWidth: false,
  destroy: true
});

function loadTrashJob() {
  $.ajax({
    url: "../controller/subadmin/job/job_list.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      console.log("Response from server:", response);

      if (!response.data || !Array.isArray(response.data)) {
        toastr.error("Invalid response format from server.", "Error");
        return;
      }

      table.clear().draw();

      response.data.forEach(item => {
        table.row.add([
          `<div class="d-flex justify-content-center">
             <button class="btn btn-sm btn-success restore-btn" data-id="${item.job_id}">
               <i class="fa fa-undo"></i> Restore
             </button>
           </div>`,
          item.log_date,
          item.client_account_name,
          item.job_reference_no,
          item.client_reference_no,
          `Staff: ${item.staff_name ?? ""}<br>Checker: ${item.checker_name ?? ""}`,
          `<span class="badge bg-danger">Deleted</span>`,
          item.completion_date ?? ""
        ]).draw(false);
      });

      $("#jobCount").text("Total Records: " + response.count);
    },
    error: function (xhr) {
      toastr.error("Error fetching data: " + xhr.responseText, "Error");
    }
  });
}

loadTrashJob();


// 🔹 Handle Restore button click
$(document).on("click", ".restore-btn", function () {
  let jobId = $(this).data("id");

  $.ajax({
    url: "../controller/subadmin/job/job_restore.php",
    type: "POST",
    data: { job_id: jobId },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        toastr.success(res.message, "Restored");
        loadTrashJob(); // reload table
      } else {
        toastr.error(res.message, "Error");
      }
    },
    error: function () {
      toastr.error("Something went wrong. Please try again.", "Error");
    }
  });
});
