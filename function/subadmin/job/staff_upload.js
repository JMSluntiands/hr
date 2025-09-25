// 🔹 Function para i-load lahat ng staff files
function loadStaffFiles(jobID) {
  $.get("../controller/subdomain/job/staff_upload_list.php", { job_id: jobID }, function (data) {
    $("#staffFilesBox").html(data);
  });
}

// 🔹 Initial load on page ready
$(document).ready(function () {
  let jobID = $("#jobID").val();
  if (jobID) {
    loadStaffFiles(jobID);
  }
});

$("#btnUploadStaffFile").on("click", function () {
  let btn = $(this);
  let jobID = $("#jobID").val();
  let comment = $("#staffComment").val().trim();
  let files = $("#uploadDocs")[0].files;

  if (files.length === 0) {
    toastr.warning("Please select at least one PDF file.");
    return;
  }

  let formData = new FormData($("#staffUploadForm")[0]);

  // 🕒 dagdag timestamp
  let createdAt = new Date();
  let formattedTime = createdAt.getFullYear() + "-" +
    String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
    String(createdAt.getDate()).padStart(2, "0") + " " +
    String(createdAt.getHours()).padStart(2, "0") + ":" +
    String(createdAt.getMinutes()).padStart(2, "0") + ":" +
    String(createdAt.getSeconds()).padStart(2, "0");

  formData.append("createdAt", formattedTime);
  console.log("Uploading with job_id:", formData.get("job_id"));

  $.ajax({
    url: "../controller/subdomain/job/staff_upload.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    beforeSend: function () {
      // 🔹 show loading state
      btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
    },
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        $("#uploadDocs").val("");
        $("#staffComment").val("");
        loadStaffFiles(jobID); // refresh list
      } else {
        toastr.error(response.message || "Upload failed", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    },
    complete: function () {
      // 🔹 reset button after 1.5s
      setTimeout(function () {
        btn.prop("disabled", false).text("Upload");
      }, 1500);
    }
  });
});

