
$("#checkerSelect").on("change", function () {
  let jobID = $("#jobID").val();
  let checkerID = $(this).val();

  $.ajax({
    url: "../controller/job/view_update_assigned.php",
    type: "POST",
    data: { job_id: jobID, checker_id: checkerID },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        loadActivityLogs();
      } else {
        toastr.error(response.message || "Failed to update checker", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    }
  });
});