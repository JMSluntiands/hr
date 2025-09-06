// 🔹 Function para i-load lahat ng staff files
function loadStaffFiles(jobID) {
  $.get("../controller/job/staff_upload_list.php", { job_id: jobID }, function (data) {
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

// 🔹 Upload handler (di ko ginalaw, meron ka na)
$("#btnUploadStaffFile").on("click", function () {
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

  $.ajax({
    url: "../controller/job/staff_upload.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
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
    }
  });
});
