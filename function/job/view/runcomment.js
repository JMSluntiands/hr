let runCommentOffset = 0;
const runCommentLimit = 5;

// Load run comments
function loadRunComments(offset = 0, append = false) {
  let jobID = $("#jobID").val();
  $.get("../controller/job/view_run_comments.php", { job_id: jobID, offset: offset }, function (data) {
    if (append) {
      $("#runCommentsBox").append(data);
    } else {
      $("#runCommentsBox").html(data);
    }
  });
}
loadRunComments();

// View more run comments
$(document).on("click", ".view-more-run", function () {
  let offset = $(this).data("offset");
  $(this).remove();
  loadRunComments(offset, true);
});

// ✅ Quill editor for RUN comments
var runQuill = new Quill('#runCommentMessage', {
  theme: 'snow',
  placeholder: 'Write a run comment...',
  modules: {
    toolbar: '#runCommentsToolbar'
  }
});

// Send run comment
$("#btnSendRunComment").on("click", function () {
  let jobID = $("#jobID").val();
  let message = runQuill.root.innerHTML.trim(); // ✅ get HTML

  if (message === "" || message === "<p><br></p>") {
    toastr.warning("Please enter a run comment.");
    return;
  }

  let createdAt = new Date();
  let options = { month: "short", day: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", hour12: true };
  let formattedTime = createdAt.toLocaleString("en-US", options);

  $.ajax({
    url: "../controller/job/view_add_run_comments.php",
    type: "POST",
    data: { job_id: jobID, message: message, created_at: formattedTime },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        runQuill.setContents([]); // ✅ clear editor correctly
        loadRunComments();
      } else {
        toastr.error(response.message || "Failed to add run comment", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    }
  });
});
