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
        // toastr.success(response.message, "Success");

        // reset form & previews
        $("#addJobForm")[0].reset();
        $("#plansPreview, #docsPreview").empty();
        $("#plansCount").text("0 files");
        $("#docsCount").text("0 files");

        // 🔹 Custom toast with OK & Cancel
        let $toast = toastr.info(
          `
  <div class="mt-2">
    <p>Do you want to log a new job?</p>
    <a href="job-new" type="button" class="btn btn-sm btn-primary me-2" id="confirmLog">OK</a>
    <a href="job" type="button" class="btn btn-sm btn-secondary" id="cancelLog">Cancel</a>
  </div>
  `,
          "Confirmation",
          {
            timeOut: 0,         // ⏱ wag auto-hide
            extendedTimeOut: 0, // wag mawala pag hover
            closeButton: true,
            tapToDismiss: false
          }
        );

        // 🔹 Button event listeners
        $(document).off("click", "#confirmLog").on("click", "#confirmLog", function () {
          toastr.clear($toast); // remove confirmation toast
          toastr.success("Job successfully logged!", "Done ✅");
          // 👉 dito ka pwede mag-redirect or call another function
        });

        $(document).off("click", "#cancelLog").on("click", "#cancelLog", function () {
          toastr.clear($toast); // remove confirmation toast
          toastr.warning("Job logging cancelled.", "Cancelled");
        });

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