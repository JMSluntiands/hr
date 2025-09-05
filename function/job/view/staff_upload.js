function loadStaffFiles(jobID) {
  $.get("../controller/job/staff_upload_list.php", { job_id: jobID }, function (data) {
    $("#staffFilesBox").html(data);
  });
}

$("#btnUploadStaffFile").on("click", function () {
  let jobID = $("#jobID").val();
  let comment = $("#staffComment").val().trim();
  let files = $("#uploadDocs")[0].files;

  if (files.length === 0) {
    toastr.warning("Please select at least one PDF file.");
    return;
  }

  let formData = new FormData($("#staffUploadForm")[0]);

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
        loadStaffFiles(jobID);
      } else {
        toastr.error(response.message || "Upload failed", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    }
  });
});

// Initial load
loadStaffFiles($("#jobID").val());
