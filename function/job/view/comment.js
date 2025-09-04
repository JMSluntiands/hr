let commentOffset = 0;
const commentLimit = 5;

function loadComments(offset = 0, append = false) {
  let jobID = $("#jobID").val();

  $.get("../controller/job/view_comments.php", { job_id: jobID, offset: offset }, function (data) {
    if (append) {
      $("#commentsBox").append(data);
    } else {
      $("#commentsBox").html(data);
    }
  });
}

loadComments();

$(document).on("click", ".view-more", function () {
  let offset = $(this).data("offset");
  $(this).remove();
  loadComments(offset, true);
});

$("#btnSendComment").on("click", function () {
  let jobID = $("#jobID").val();
  let message = $("#commentMessage").val().trim();

  if (message === "") {
    toastr.warning("Please enter a comment.");
    return;
  }

  let createdAt = new Date();
  let options = { month: "short", day: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", hour12: true };
  let formattedTime = createdAt.toLocaleString("en-US", options);

  $.ajax({
    url: "../controller/job/view_add_comments.php",
    type: "POST",
    data: { job_id: jobID, message: message, created_at: formattedTime },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        toastr.success(response.message, "Success");
        $("#commentMessage").val("");
        loadComments();
      } else {
        toastr.error(response.message || "Failed to add comment", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText, "Error");
    }
  });
});