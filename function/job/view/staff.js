
$("#staffSelect").on("change", function () {
  let jobID = $("#jobID").val();
  let staffID = $(this).val();

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
    data: { job_id: jobID, staff_id: staffID, createdAt: formattedTime },
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