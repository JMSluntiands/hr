$(document).ready(function () {
  let buttonsConfig = [];

  if (userRole === "LUNTIAN") {
    buttonsConfig.push({
      extend: 'excelHtml5',
      text: '<i class="si si-doc"></i> Export to Excel',
      titleAttr: 'Export table to Excel',
      className: 'btn btn-success btn-sm rounded-0',
      exportOptions: {
        columns: ':not(:first-child)',
        format: {
          body: function (data, row, column, node) {
            let text = data.replace(/<br\s*\/?>/gi, "\n")
              .replace(/<[^>]*>?/gm, "");
            return text.trim();
          }
        }
      }
    });
  }

  let table = $("#jobTable").DataTable({
    responsive: {
      details: {
        type: 'column'
      }
    },
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    data: [],
    buttons: buttonsConfig,
    columnDefs: [
      {
        className: 'dtr-control',
        orderable: false,
        targets: 0
      },
      { responsivePriority: 1, targets: 1 },
      { responsivePriority: 2, targets: 2 },
      { targets: [13], visible: false, searchable: true },

      // hide some columns depending on role
      ...(userRole === "LBS" ? [
        { targets: [6, 8], visible: false, searchable: true }
      ] : []),

      // hide client_name if not LUNTIAN
      ...(userRole !== "LUNTIAN" ? [
        { targets: [3], visible: false, searchable: true }
      ] : [])
    ]
  });


  let statusFilter = `
    <select id="statusFilter" class="form-select form-select-sm d-inline-block mb-2 p-1" style="width:200px;">
      <option value="">Filtered by Status</option>
      <option value="Allocated">Allocated</option>
      <option value="Accepted">Accepted</option>
      <option value="Processing">Processing</option>
      <option value="For Checking">For Checking</option>
      <option value="Awaiting Further Information">Awaiting Further Information</option>
      <option value="Pending">Pending</option>
      <option value="For Discussion">For Discussion</option>
      <option value="Revision Requested">Revision Requested</option>
      <option value="Revised">Revised</option>
    </select>
  `;
  $(".dt-buttons").prepend(statusFilter);

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    let selected = $('#statusFilter').val();
    let status = $(table.cell(dataIndex, 9).node()).text().trim();
    return selected === "" || status === selected;
  });

  $(document).on("change", "#statusFilter", function () {
    table.draw();
  });

  let staffList = [];
  let checkerList = [];

  function loadDropdowns(callback) {
    $.getJSON("../controller/job/table_assigned_dropdown.php", function (res) {
      staffList = res.staff;
      checkerList = res.checker;
      if (typeof callback === "function") callback();
    }).fail(function (xhr) {
      toastr.error("Failed to load dropdown lists: " + xhr.responseText);
    });
  }

  let statusList = [
    "Cancelled", "For Email Confirmation"
  ];

  function getSafeDate() {
    let createdAt = new Date();
    return createdAt.getFullYear() + "-" +
      String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
      String(createdAt.getDate()).padStart(2, "0") + " " +
      String(createdAt.getHours()).padStart(2, "0") + ":" +
      String(createdAt.getMinutes()).padStart(2, "0") + ":" +
      String(createdAt.getSeconds()).padStart(2, "0");
  }


  function updateJobField(jobId, field, newVal) {
    // console.log("📤 Sending:", { jobId, field, newVal });
    $.post(
      "../controller/job/update_field.php",
      {
        job_id: jobId,
        field: field,
        value: newVal,
        safeDate: getSafeDate()
      },
      function (res) {
        // console.log("📥 Response:", res);
        if (res.status === "success") {
          toastr.success(res.message || `${field} updated successfully`);
          if (field === "job_status") {
            loadJob();
          }
        } else {
          toastr.error(res.message || `Failed to update ${field}`);
        }
      },
      "json"
    ).fail(function (xhr) {
      toastr.error("Server error: " + xhr.responseText, "Error");
    });
  }

  $(document).on("change", ".staff-select", function () {
    updateJobField($(this).data("id"), "staff_id", $(this).val());
  });

  $(document).on("change", ".checker-select", function () {
    updateJobField($(this).data("id"), "checker_id", $(this).val());
  });

  $(document).on("change", ".status-select", function () {
    updateJobField($(this).data("id"), "job_status", $(this).val());
  });

  function formatDateTime(datetimeStr) {
    if (!datetimeStr) return "";
    let dateObj = new Date(datetimeStr);
    if (isNaN(dateObj)) return datetimeStr;
    let optionsDate = { year: "numeric", month: "long", day: "numeric" };
    let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
    let datePart = dateObj.toLocaleDateString("en-US", optionsDate);
    let timePart = dateObj.toLocaleTimeString("en-US", optionsTime);
    return `${datePart}<br>${timePart}`;
  }

  function loadJob() {
    $.ajax({
      url: "../controller/job/list",
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (!response.data || !Array.isArray(response.data)) {
          toastr.error("Invalid response format from server.", "Error");
          return;
        }
        table.clear().draw();
        response.data.forEach(item => {
          table.row.add([
            `
              <div class="">
                <a class="btn btn-sm btn-info text-white rounded-0" title="View" href="job-view?id=${item.job_id}">
                  <i class="si si-eye"></i>
                </a>
                <a class="btn btn-sm btn-warning text-white rounded-0" title="Edit" href="job-edit?id=${item.job_id}">
                  <i class="si si-note"></i>
                </a>
                <button class="btn btn-sm btn-danger btn-delete-job rounded-0" title="Delete" data-id="${item.job_id}">
                  <i class="si si-trash"></i>
                </button>
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
            // Status column - UPDATED
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
              : (function () {
                // Always include special statuses
                let special = ["Cancelled", "For Email Confirmation"];
                // Build options array. If actual status exists and is not one of the special ones, include it
                let options = special.slice();
                if (item.job_status && special.indexOf(item.job_status) === -1) {
                  // include actual status and make it first so it's selected and visible
                  options.unshift(item.job_status);
                }
                // Determine which value should be selected
                let selectedVal = item.job_status && item.job_status.trim() !== "" ? item.job_status : "Cancelled";
                return `
                  <div class="d-flex justify-content-center align-items-center w-100" style="max-width:180px; min-width:150px;">
                    <select class="form-select form-select-sm status-select" data-id="${item.job_id}">
                      ${options.map(st => `<option value="${st}" ${st === selectedVal ? "selected" : ""}>${st}</option>`).join("")}
                    </select>
                  </div>
                `;
              })(),
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

  function computeDueDate(logDate, priority) {
    if (!logDate) return "";
    let start = new Date(logDate);
    if (isNaN(start)) return logDate;
    if (start.getHours() < 8) start.setHours(8, 0, 0, 0);
    if (start.getHours() >= 15) {
      start.setDate(start.getDate() + 1);
      start.setHours(8, 0, 0, 0);
    }
    let due = new Date(start);
    function addWorkingDays(days) {
      for (let i = 0; i < days; i++) {
        due.setDate(due.getDate() + 1);
      }
    }
    switch (priority) {
      case "Top (COB)": {
        due.setHours(due.getHours() + 6);
        if (due.getHours() > 15) {
          let extra = due.getHours() - 15;
          due.setDate(due.getDate() + 1);
          due.setHours(8 + extra, due.getMinutes(), 0, 0);
        }
        break;
      }
      case "High 1 day": addWorkingDays(1); break;
      case "Standard 2 days": addWorkingDays(2); break;
      case "Standard 3 days": addWorkingDays(3); break;
      case "Standard 4 days": addWorkingDays(4); break;
      case "Low 5 days": addWorkingDays(5); break;
      case "Low 6 days": addWorkingDays(6); break;
      case "Low 7 days": addWorkingDays(7); break;
      default: return "";
    }
    let optionsDate = { year: "numeric", month: "long", day: "numeric" };
    let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
    let formatted = `${due.toLocaleDateString("en-US", optionsDate)}<br>${due.toLocaleTimeString("en-US", optionsTime)}`;
    let now = new Date();
    let diffHours = (due - now) / (1000 * 60 * 60);
    if (diffHours <= 4 && diffHours > 0) {
      return `<span class="text-danger fw-bold">${formatted}</span>`;
    } else if (diffHours <= 0) {
      return `<span class="text-danger fw-bold">${formatted} (Overdue)</span>`;
    }
    return formatted;
  }

  $(document).on("click", ".btn-delete-job", function () {
    let jobId = $(this).data("id");
    if (!confirm("Are you sure you want to delete this job?")) return;
    $.ajax({
      url: "../controller/job/job_delete",
      type: "POST",
      data: { job_id: jobId },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          toastr.success(response.message || "Job deleted successfully", "Success");
          loadJob();
        } else {
          toastr.error(response.message || "Failed to delete job", "Error");
        }
      },
      error: function (xhr) {
        toastr.error("Error deleting job: " + xhr.responseText, "Error");
      }
    });
  });

  loadDropdowns(function () {
    loadJob();
  });
});
