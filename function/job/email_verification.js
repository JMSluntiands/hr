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
                      data-assessor="SB"
                      data-assessor-email="${item.client_email}">
                <i class="fa fa-paper-plane"></i> Send
              </button>
            </div>
          `,
          item.log_date,
          item.job_reference_no,
          item.client_email,
          `<button class="btn btn-sm btn-info email-preview" data-id="${item.job_id}" data-reference="${item.job_reference_no}" data-status="Completed" data-assessor="SB" data-assessor-email="${item.client_email}"> Preview </button>
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
  const toEmail = $(this).data("to");
  const reference = $(this).data("reference");
  const status = $(this).data("status");
  const assessor = $(this).data("assessor");
  const assessorEmail = $(this).data("assessor-email");

  console.log("DEBUG SEND:", { toEmail, reference, status, assessor, assessorEmail });

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
      } else {
        toastr.error(res.message);
        console.log("Backend debug:", res.debug); // para makita kung ano dumating
      }
    },
    error: function (xhr) {
      toastr.error("Something went wrong: " + xhr.responseText);
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
        loadTrashJob(); // reload table
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