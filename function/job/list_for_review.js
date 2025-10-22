$(document).ready(function () {
  let table = $("#jobTable").DataTable({
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: "Bfrtip",
    data: [],
    buttons: [],
    columnDefs: [
      { responsivePriority: 1, targets: 0 },
      { responsivePriority: 2, targets: -1 }
    ]
  });

  function loadJob() {
    $.ajax({
      url: "../controller/job/list_for_review.php",
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (!response.data || !Array.isArray(response.data)) {
          toastr.error("Invalid response format from server.", "Error");
          return;
        }

        let staffList = response.staffList || [];
        let checkerList = response.checkerList || [];

        function formatDateTime(datetimeStr) {
          if (!datetimeStr) return "";
          let dateObj = new Date(datetimeStr);
          let optionsDate = { year: "numeric", month: "long", day: "numeric" };
          let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
          return (
            dateObj.toLocaleDateString("en-US", optionsDate) +
            "<br>" +
            dateObj.toLocaleTimeString("en-US", optionsTime)
          );
        }

        table.clear().draw();

        response.data.forEach((item) => {
          // ✅ Dynamic Status Dropdown Logic
          let specialStatuses = ["Cancelled", "For Email Confirmation"];
          let statusOptions = [...specialStatuses];

          // If current status is not in the special list, include it
          if (item.job_status && !specialStatuses.includes(item.job_status)) {
            statusOptions.unshift(item.job_status);
          }

          // Default selected value (Cancelled if empty)
          let selectedStatus =
            item.job_status && item.job_status.trim() !== ""
              ? item.job_status
              : "Cancelled";

          table.row
            .add([
              // 🔹 Action buttons
              `
              <div class="d-flex justify-content-center align-items-center gap-1">
                <a class="btn btn-sm btn-info text-white rounded-0" href="job-view?id=${item.job_id}">
                  <i class="si si-eye"></i>
                </a>
                <a class="btn btn-sm btn-dark rounded-0" href="job-duplicate?id=${item.job_id}">
                  <i class="si si-docs"></i>
                </a>
              </div>
              `,
              `<div class="text-center">${item.log_date}</div>`,
              `<div class="text-center"><span style="font-size:12px">${item.client_account_name}</span><br><small>${item.ncc_compliance}</small></div>`,
              `<div class="text-center"><span style="font-size:12px">${item.client_code}</span></div>`,
              `<div class="text-center"><strong>${item.job_reference_no}</strong></div>`,
              `<div class="text-center"><span>${item.job_type}</span></div>`,
              `<div class="text-center"><span class="badge text-dark">${item.priority}</span></div>`,

              // 🔹 Staff
              userRole !== "LUNTIAN"
                ? `<span><strong>${item.staff_name || ""}</strong></span>`
                : `
                  <select class="form-select form-select-sm update-field" data-id="${item.job_id}" data-field="staff_id">
                    ${staffList
                  .map(
                    (st) =>
                      `<option value="${st.staff_id}" ${item.staff_id === st.staff_id ? "selected" : ""
                      }>${st.name}</option>`
                  )
                  .join("")}
                  </select>
                `,

              // 🔹 Checker
              userRole !== "LUNTIAN"
                ? `<span><strong>${item.checker_name || ""}</strong></span>`
                : `
                  <select class="form-select form-select-sm update-field" data-id="${item.job_id}" data-field="checker_id">
                    ${checkerList
                  .map(
                    (ch) =>
                      `<option value="${ch.checker_id}" ${item.checker_id === ch.checker_id ? "selected" : ""
                      }>${ch.name}</option>`
                  )
                  .join("")}
                  </select>
                `,

              // 🔹 Status Dropdown (FINAL)
              userRole !== "LUNTIAN"
                ? `<span class="badge bg-secondary">${item.job_status}</span>`
                : `
                  <select class="form-select form-select-sm update-field" data-id="${item.job_id}" data-field="job_status">
                    ${statusOptions
                  .map(
                    (st) =>
                      `<option value="${st}" ${selectedStatus === st ? "selected" : ""
                      }>${st}</option>`
                  )
                  .join("")}
                  </select>
                `,

              `<div class="text-center">${item.due_date || ""}</div>`,
              `<div class="text-center">${formatDateTime(item.completion_date)}</div>`,

              // 🔹 Complexity stars
              `<div class="text-center">
                ${[1, 2, 3, 4, 5]
                .map(
                  (i) =>
                    `<i class="fa fa-star ${i <= item.plan_complexity
                      ? "text-warning"
                      : "text-secondary"
                    }"></i>`
                )
                .join("")}
              </div>`,

              `<div class="text-center">${item.notes || ""}</div>`
            ])
            .draw(false);
        });

        $("#jobCount").text("Total Records: " + response.count);
      },
      error: function (xhr) {
        table.clear().draw();
        toastr.error("Error fetching data: " + xhr.responseText, "Error");
      }
    });
  }

  loadJob();

  // 🔹 Auto-save when dropdown changes
  $("#jobTable").on("change", ".update-field", function () {
    let jobId = $(this).data("id");
    let field = $(this).data("field");
    let value = $(this).val();

    $.ajax({
      url: "../controller/job/update_job.php",
      type: "POST",
      data: { job_id: jobId, field: field, value: value },
      success: function () {
        toastr.success("Updated successfully.");
        loadJob();
      },
      error: function (xhr) {
        toastr.error("Update failed: " + xhr.responseText);
      }
    });
  });
});
