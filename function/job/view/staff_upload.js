// 🔹 Function para i-load lahat ng staff files
function loadStaffFiles(jobID) {
  $.get("../controller/job/staff_upload_list.php", { job_id: jobID }, function (data) {
    $("#staffFilesBox").html(data);
  });
}

window.staffQuill = new Quill('#staffCommentEditor', {
  theme: 'snow',
  placeholder: 'Add comment...',
  modules: {
    toolbar: '#staffCommentToolbar'
  }
});

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

  // 📝 kunin Quill editor content
  let quillContent = staffQuill.root.innerHTML.trim();
  $("#staffCommentInput").val(quillContent);

  let files = $("#uploadDocs")[0].files;

  if (files.length === 0) {
    toastr.warning("Please select at least one PDF file.");
    return;
  }

  if (quillContent === "" || quillContent === "<p><br></p>") {
    toastr.warning("Please add a comment.");
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
    beforeSend: function () {
      btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
    },
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        $("#uploadDocs").val("");
        staffQuill.root.innerHTML = ""; // reset editor
        loadStaffFiles(jobID); // refresh list
        loadActivityLogs();
      } else {
        toastr.error(response.message || "Upload failed", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    },
    complete: function () {
      setTimeout(function () {
        btn.prop("disabled", false).text("Upload");
      }, 1500);
    }
  });
});
