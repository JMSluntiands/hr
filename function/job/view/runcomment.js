let runCommentOffset = 0;
const runCommentLimit = 5;

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

$(document).on("click", ".view-more-run", function () {
  let offset = $(this).data("offset");
  $(this).remove();
  loadRunComments(offset, true);
});

var quill = new Quill('#commentMessage', {
  theme: 'snow',
  placeholder: 'Write a comment...',
  modules: {
    toolbar: '#runCommentsToolbar'
  }
});

$("#btnSendRunComment").on("click", function () {
  let jobID = $("#jobID").val();
  let message = $("#runCommentMessage").val().trim();

  if (message === "") {
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
        $("#runCommentMessage").val("");
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