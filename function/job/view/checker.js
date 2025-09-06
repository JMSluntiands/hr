$("#checkerSelect").on("change", function () {
  let jobID = $("#jobID").val();
  let checkerID = $(this).val();

  // 🕒 Device local time -> MySQL format (PH time kung device mo naka PH timezone)
  let createdAt = new Date();
  let formattedTime = createdAt.getFullYear() + "-" +
    String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
    String(createdAt.getDate()).padStart(2, "0") + " " +
    String(createdAt.getHours()).padStart(2, "0") + ":" +
    String(createdAt.getMinutes()).padStart(2, "0") + ":" +
    String(createdAt.getSeconds()).padStart(2, "0");

  $.ajax({
    url: "../controller/job/view_update_assigned.php",
    type: "POST",
    data: { job_id: jobID, checker_id: checkerID, createdAt: formattedTime },
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
