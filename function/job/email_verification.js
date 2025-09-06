let table = $("#jobTable").DataTable({
  responsive: true,
  autoWidth: false,
  destroy: true
});

function loadTrashJob() {
  $.ajax({
    url: "../controller/job/mailbox.php",
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
          `<span class="badge bg-danger">For Email Confirmation</span>`,
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