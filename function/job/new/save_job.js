$("#addJobForm").on("submit", function (e) {
  e.preventDefault();

  let now = new Date();
  let localDatetime = now.getFullYear() + "-" +
    ("0" + (now.getMonth() + 1)).slice(-2) + "-" +
    ("0" + now.getDate()).slice(-2) + " " +
    ("0" + now.getHours()).slice(-2) + ":" +
    ("0" + now.getMinutes()).slice(-2) + ":" +
    ("0" + now.getSeconds()).slice(-2);

  $("#log_date").val(localDatetime);

  let formData = new FormData(this);

  plansFiles.forEach((file, i) => {
    formData.append("plans[]", file);
  });

  docsFiles.forEach((file, i) => {
    formData.append("docs[]", file);
  });

  $.ajax({
    url: "../controller/job/job_save.php",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    dataType: "json",
    beforeSend: function () {
      $("#addJobForm button[type=submit]").prop("disabled", true).text("Saving...");
    },
    success: function (response) {
      if (response.status === "success") {
        toastr.success(response.message, "Success");
        $("#addJobForm")[0].reset();
        $("#plansPreview, #docsPreview").empty();
        $("#plansCount").text("0 files");
        $("#docsCount").text("0 files");
      } else {
        toastr.error(response.message, "Error");
      }
    },
    error: function (xhr, status, error) {
      toastr.error("Invalid response format from server.", "Error");
    },
    complete: function () {
      $("#addJobForm button[type=submit]").prop("disabled", false).text("Add Job");
    }
  });
});