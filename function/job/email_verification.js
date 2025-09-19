let table = $("#jobTable").DataTable({
  responsive: true,
  autoWidth: false,
  destroy: true
});



function loadTrashJob() {
  $.ajax({
    url: "../controller/job/mailbox.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      console.log("Response from server:", response);

      if (!response.data || !Array.isArray(response.data)) {
        toastr.error("Invalid response format from server.", "Error");
        return;
      }

      table.clear().draw();

      response.data.forEach(item => {
        // parse files_json kung hindi empty
        let filesHtml = "";
        try {
          let files = JSON.parse(item.files_json || "[]");
          if (Array.isArray(files)) {
            filesHtml = files.map(file => {
              // gawin clickable link, target="_blank" para new tab
              return `<a href="../document/${item.job_reference_no}/${file}" target="_blank" class="d-block text-primary">
                        Uploaded Files
                      </a>`;
            }).join("");
          }
        } catch (e) {
          filesHtml = `<span class="text-muted">Invalid file data</span>`;
        }

        table.row.add([
          `<div class="d-flex justify-content-center gap-1">
              <button class="btn btn-sm btn-danger restore-btn d-flex align-items-center gap-1" data-id="${item.job_id}">
                <i class="fa fa-undo"></i> Revert
              </button>
              <button class="btn btn-sm btn-primary send-email d-flex align-items-center gap-1"
                      data-id="${item.job_id}"
                      data-to="${item.client_email}"
                      data-reference="${item.job_reference_no}"
                      data-status="For Email Confirmation"
                      data-assessor="${item.staff_id}"
                      data-assessor-email="${item.client_email}">
                <i class="fa fa-paper-plane"></i> Send
              </button>
            </div>
          `,
          item.log_date,
          item.job_reference_no,
          item.client_email,
          `<button class="btn btn-sm btn-info email-preview" data-id="${item.job_id}" data-reference="${item.job_reference_no}" data-status="Completed" data-assessor="${item.staff_id}" data-assessor-email="${item.client_email}"> Preview </button>
          `,
          filesHtml
        ]).draw(false);

      });


      $("#jobCount").text("Total Records: " + response.count);
    },
    error: function (xhr) {
      toastr.error("Error fetching data: " + xhr.responseText, "Error");
    }
  });
}

$(document).on("click", ".email-preview", function () {
  const reference = $(this).data("reference");
  const status = $(this).data("status");
  const assessor = $(this).data("assessor");
  const assessorEmail = $(this).data("assessor-email");

  $("#emailReference").text(reference);
  $("#emailStatus").text(status);
  $("#emailAssessor").text(assessor);
  $("#emailAssessorEmail").text(assessorEmail).attr("href", "mailto:" + assessorEmail);

  $("#emailFormatModal").modal("show");
});

$(document).on("click", ".send-email", function () {
  const $btn = $(this); // reference sa button
  const toEmail = $btn.data("to");
  const reference = $btn.data("reference");
  const status = $btn.data("status");
  const assessor = $btn.data("assessor");
  const assessorEmail = $btn.data("assessor-email");

  // console.log("DEBUG SEND:", { toEmail, reference, status, assessor, assessorEmail });

  // 🌀 disable button at lagyan ng loading spinner
  $btn.prop("disabled", true).html(`
    <span class="spinner-border spinner-border-sm me-1"></span> Sending...
  `);

  $.ajax({
    url: "../controller/job/job_email.php",
    type: "POST",
    data: {
      toEmail,
      reference,
      status,
      assessor,
      assessorEmail
    },
    dataType: "json",
    success: function (res) {
      if (res.success) {
        toastr.success(res.message);
        loadTrashJob();
      } else {
        toastr.error(res.message);
        console.log("Backend debug:", res.debug);
      }
    },
    error: function (xhr) {
      toastr.error("Something went wrong: " + xhr.responseText);
    },
    complete: function () {
      // ibalik yung button sa normal state
      $btn.prop("disabled", false).html(`
        <i class="fa fa-paper-plane"></i> Send
      `);
    }
  });
});


// Revert button click
$(document).on("click", ".restore-btn", function () {
  const jobId = $(this).data("id");

  $.ajax({
    url: "../controller/job/email_status.php",
    type: "POST",
    data: { job_id: jobId, status: "For Review" },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        toastr.success("Job reverted to For Review", "Success");
        // Reload table muna
        loadTrashJob();

        // 🔄 After a short delay, refresh page
        setTimeout(function () {
          location.reload();
        }, 1500); // depende sa tagal ng toast
      } else {
        toastr.error(response.message || "Failed to update job.", "Error");
      }
    },
    error: function (xhr) {
      toastr.error("Error updating job: " + xhr.responseText, "Error");
    }
  });
});


loadTrashJob();