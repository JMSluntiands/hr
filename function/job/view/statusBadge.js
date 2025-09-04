
$("#statusBadge").on("click", function () {
  $(this).addClass("d-none");
  $("#jobStatus").removeClass("d-none").focus();
});

$("#jobStatus").on("change", function () {
  let jobID = $("#jobID").val();
  let newStatus = $(this).val();

  $.ajax({
    url: "../controller/job/view_update_status.php",
    type: "POST",
    data: { job_id: jobID, job_status: newStatus },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");

        $("#statusBadge")
          .text(newStatus)
          .removeClass("d-none");

        $("#jobStatus").addClass("d-none");

        loadActivityLogs();
      } else {
        toastr.error(response.message || "Failed to update status", "Error");
        $("#statusBadge").removeClass("d-none");
        $("#jobStatus").addClass("d-none");
      }
    },
    error: function (xhr) {
      toastr.error("Error fetching data: " + xhr.responseText, "Error");
      $("#statusBadge").removeClass("d-none");
      $("#jobStatus").addClass("d-none");
    }
  });
});