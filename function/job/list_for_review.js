$(document).ready(function () {
  // 🔹 Init DataTable
  let table = $("#jobTable").DataTable({
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    data: [], // manual loading
    buttons: [

    ],
    columnDefs: [
      { responsivePriority: 1, targets: 0 },
      { responsivePriority: 2, targets: -1 }
    ]
  });

  function loadJob() {
    $.ajax({
      url: "../controller/job/list_for_review",
      type: "GET",
      dataType: "json",
      success: function (response) {

        if (!response.data || !Array.isArray(response.data)) {
          toastr.error("Invalid response format from server.", "Error");
          return;
        }

        function formatDateTime(datetimeStr) {
          if (!datetimeStr) return "";
          let dateObj = new Date(datetimeStr);
          let optionsDate = { year: "numeric", month: "long", day: "numeric" };
          let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
          let datePart = dateObj.toLocaleDateString("en-US", optionsDate);
          let timePart = dateObj.toLocaleTimeString("en-US", optionsTime);
          return `${datePart}<br>${timePart}`; // ✅ two lines
        }

        table.clear().draw();

        response.data.forEach(item => {
          table.row.add([
            // Action buttons
            `
            <div class="d-flex justify-content-center align-items-center gap-1">
              <a class="btn btn-sm btn-info text-white rounded-0" title="View" href="job-view?id=${item.job_id}">
                <i class="si si-eye"></i>
              </a>

              <a class="btn btn-sm btn-dark rounded-0" title="Duplicate" href="job-duplicate?id=${item.job_id}">
                <i class="si si-docs"></i>
              </a>
            </div>
            `,

            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                ${formatDateTime(item.log_date)}
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-column flex-wrap">
                <span style="font-size: 12px">${item.client_account_name}</span>
                <small>${item.ncc_compliance}</small>
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                <span style="font-size: 12px">${item.client_name}</span>
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                <strong>${item.job_reference_no}</strong>
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                <span class="text-wrap">${item.job_type}</span>
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                <span class="badge text-dark"
                  style="background-color: ${item.priority === "Top (COB)" ? "#F74639" :
              item.priority === "High 1 day" ? "#FFA775" :
                item.priority === "Standard 2 days" ? "#FF71CF" :
                  "#CF7AFA"}">
                  ${item.priority}
                </span>
              </div>
            `,
            // Staff column
            userRole !== "LUNTIAN"
              ? `<span><strong>${item.staff_id}</strong></span>`
              : `
                <div class="d-flex justify-content-center align-items-center w-100" style="max-width:80px; min-width:80px;">
                  <select class="form-select form-select-sm staff-select" data-id="${item.job_id}">
                    ${staffList.map(st =>
                `<option value="${st.staff_id}" ${item.staff_id === st.staff_id ? "selected" : ""}>
                        ${st.staff_id}
                      </option>`).join("")}
                  </select>
                </div>
              `,
            // Checker column
            userRole !== "LUNTIAN"
              ? `<span><strong>${item.checker_id}</strong></span>`
              : `
                <div class="d-flex justify-content-center align-items-center w-100" style="max-width:80px; min-width:80px;">
                  <select class="form-select form-select-sm checker-select" data-id="${item.job_id}">
                    ${checkerList.map(ch =>
                `<option value="${ch.checker_id}" ${item.checker_id === ch.checker_id ? "selected" : ""}>
                        ${ch.checker_id}
                      </option>`).join("")}
                  </select>
                </div>
              `,
            // Status column
            userRole !== "LUNTIAN"
              ? `
                <span class="badge text-dark"
                  style="background-color: ${item.job_status === "Pending" ? "#F86C62" :
                item.job_status === "For Discussion" ? "#6AB9CC" :
                  item.job_status === "Revision Requested" ? "#FAE2D4" :
                    item.job_status === "For Email Confirmation" ? "#7DB9E3" :
                      item.job_status === "Allocated" ? "#FFA775" :
                        item.job_status === "Accepted" ? "#FFD2B8" :
                          item.job_status === "Processing" ? "#FF8AD8" :
                            item.job_status === "For Checking" ? "#CF7AFA" :
                              item.job_status === "Cancelled" ? "#C4C4C4" :
                                item.job_status === "Completed" ? "#69F29B" :
                                  item.job_status === "Awaiting Further Information" ? "#EDE59A" :
                                    "#6c757d"}">
                  ${item.job_status}
                </span>
              `
              : `
                <div class="d-flex justify-content-center align-items-center w-100" style="max-width:150px; min-width:150px;">
                  <select class="form-select form-select-sm status-select" data-id="${item.job_id}">
                    ${statusList.map(st => `<option value="${st}" ${item.job_status === st ? "selected" : ""}>${st}</option>`).join("")}
                  </select>
                </div>
              `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                ${computeDueDate(item.log_date, item.priority)}
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                ${formatDateTime(item.completion_date)}
              </div>
            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                ${[1, 2, 3, 4, 5].map(i => `
                  <i class="fa fa-star ${i <= item.complexity ? 'text-warning' : 'text-secondary'}" 
                    style="font-size:12px; margin:0 2px;"></i>
                `).join('')}
              </div>

            `,
            `
              <div class="d-flex justify-content-center align-items-center text-center w-100 flex-wrap">
                <span class="text-wrap">${item.notes}</span>
              </div>
            `,
          ]).draw(false);
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

});