
$("#staffSelect").on("change", function () {
  let jobID = $("#jobID").val();
  let staffID = $(this).val();

  $.ajax({
    url: "../controller/job/view_update_assigned.php",
    type: "POST",
    data: { job_id: jobID, staff_id: staffID },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        loadActivityLogs();
      } else {
        toastr.error(response.message || "Failed to update staff", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    }
  });
});