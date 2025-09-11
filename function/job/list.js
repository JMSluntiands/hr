$(document).ready(function () {
  let buttonsConfig = [];

  if (userRole === "LUNTIAN") {
    buttonsConfig.push({
      extend: 'excelHtml5',
      text: '<i class="si si-doc"></i> Export to Excel',
      titleAttr: 'Export table to Excel',
      className: 'btn btn-success btn-sm rounded-0',
      exportOptions: {
        columns: ':not(:first-child)', // exclude action buttons
        format: {
          body: function (data, row, column, node) {
            // Remove HTML tags, keep plain text
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
        type: 'column'   // ✅ show + button on first column
      }
    },
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    data: [],
    buttons: buttonsConfig,
    columnDefs: [
      {
        className: 'dtr-control', // ✅ built-in + button
        orderable: false,
        targets: 0                // gawin siyang first column
      },
      { responsivePriority: 1, targets: 1 }, // Log Date
      { responsivePriority: 2, targets: 2 }, // Client
      ...(userRole === "LBS" ? [
        { targets: [6, 7], visible: false }
      ] : [])
    ]
  });


  // 🔹 Add Job Status Filter Dropdown before Export Button
  let statusFilter = `
    <select id="statusFilter" class="form-select form-select-sm d-inline-block mb-2 p-1" style="width:200px;">
      <option value="">Filtered by Status</option>
      <option value="Allocated">Allocated</option>
      <option value="Accepted">Accepted</option>
      <option value="Processing">Processing</option>
      <option value="For Checking">For Checking</option>
      <option value="Completed">Completed</option>
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
    let status = $(table.cell(dataIndex, 7).node()).text().trim();

    return selected === "" || status === selected;
  });

  $(document).on("change", "#statusFilter", function () {
    table.draw();
  });

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

              <a class="btn btn-sm btn-warning btn-edit-job text-white rounded-0" title="Edit" href="job-edit?id=${item.job_id}">
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

            // ✅ Log Date
            `<div class="text-center">${formatDateTime(item.log_date)}</div>`,

            // Client
            `<span style="font-size: 12px">${item.client_account_name}</span><br><small>${item.ncc_compliance}</small>`,

            // Reference
            `<strong>${item.job_reference_no}</strong>`,

            `<span>${item.job_type}</span>`,

            `
            <span class="badge text-dark"
              style="background-color: ${item.priority === "Top" ? "#F74639" :
              item.priority === "High Prio" ? "#FFA775" :
                item.priority === "Standard 2 days" ? "#FF71CF" :
                  item.priority === "Standard 3 days" ? "#CF7AFA" :
                    "#6c757d" // default gray
            }">
              ${item.priority}
            </span>
            `,


            // Client Ref
            // `<span><strong>${item.client_reference_no}</strong></span>`,

            `<span><strong>${item.staff_name}</strong></span>`,

            `<span><strong>${item.checker_name}</strong></span>`,

            `
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
                                  "#6c757d" // default gray
            }">
              ${item.job_status}
            </span>`,
            `<div class="text-center">${computeDueDate(item.log_date, item.priority)}</div>`,
            `<div class="text-center">${formatDateTime(item.completion_date)}</div>`
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

    let dateObj = new Date(logDate);

    switch (priority) {
      case "Standard 4 days":
        dateObj.setDate(dateObj.getDate() + 4);
        dateObj.setHours(dateObj.getHours() + 48);
        break;
      case "Standard 3 days":
        dateObj.setDate(dateObj.getDate() + 3);
        dateObj.setHours(dateObj.getHours() + 48);
        break;
      case "Standard 2 days":
        dateObj.setDate(dateObj.getDate() + 2);
        dateObj.setHours(dateObj.getHours() + 48);
        break;
      case "High 1 day":
        dateObj.setDate(dateObj.getDate() + 1);
        dateObj.setHours(dateObj.getHours() + 24);
        break;
      case "Top (COB)":
        dateObj.setHours(dateObj.getHours() + 4);
        break;
      default:
        return "";
    }

    let optionsDate = { year: "numeric", month: "long", day: "numeric" };
    let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
    let datePart = dateObj.toLocaleDateString("en-US", optionsDate);
    let timePart = dateObj.toLocaleTimeString("en-US", optionsTime);

    return `${datePart}<br>${timePart}`;
  }


  loadJob();

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

});