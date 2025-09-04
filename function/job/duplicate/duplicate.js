$("#duplicateJobForm").on("submit", function (e) {
  e.preventDefault();
  let fd = new FormData(this);

  plansFiles.forEach(f => fd.append("plans[]", f));
  docsFiles.forEach(f => fd.append("docs[]", f));

  fd.append("removedPlans", JSON.stringify(removedPlans));
  fd.append("removedDocs", JSON.stringify(removedDocs));

  $.ajax({
    url: "../controller/job/job_save_duplicate.php",
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        toastr.success("Job duplicated successfully!");
        setTimeout(() => window.location.href = "job", 1200);
      } else {
        toastr.error(res.message || "Failed to duplicate job.");
      }
    },
    error: function () {
      toastr.error("Server error.");
    }
  });
});