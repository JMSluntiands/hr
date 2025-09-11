$("#statusBadge").on("click", function () {
  $(this).addClass("d-none");
  $("#jobStatus").removeClass("d-none").focus();
});

// store old value bago magbago
$("#jobStatus").on("focus", function () {
  $(this).data("old-status", $(this).val());
});

$("#jobStatus").on("change", function () {
  let jobID = $("#jobID").val();
  let newStatus = $(this).val();
  let oldStatus = $(this).data("old-status");

  // 🕒 Device time -> MySQL format
  let createdAt = new Date();
  let formattedTime = createdAt.getFullYear() + "-" +
    String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
    String(createdAt.getDate()).padStart(2, "0") + " " +
    String(createdAt.getHours()).padStart(2, "0") + ":" +
    String(createdAt.getMinutes()).padStart(2, "0") + ":" +
    String(createdAt.getSeconds()).padStart(2, "0");

  $.ajax({
    url: "../controller/job/view_update_status.php",
    type: "POST",
    data: { job_id: jobID, job_status: newStatus, createdAt: formattedTime },
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

        // ❌ reload after toast
        setTimeout(function () {
          location.reload();
        }, 1500);
      }
    },
    error: function (xhr) {
      toastr.error("Error fetching data: " + xhr.responseText, "Error");

      // ❌ reload after toast
      setTimeout(function () {
        location.reload();
      }, 1500);
    }
  });
});
