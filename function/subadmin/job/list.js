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

  $(document).on("change", "#statusFilter", function () {
    table.draw();
  });

  function loadJob() {
    $.ajax({
      url: "../controller/subdomain/job/list",
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
            </div>
            `,

            // ✅ Log Date
            `<div class="text-center">${formatDateTime(item.log_date)}</div>`,

            // Client
            `<span style="font-size: 12px">${item.client_account_name}</span><br><small>${item.ncc_compliance}</small>`,

            `<span style="font-size: 12px">${item.client_name}</span>`,

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

    let start = new Date(logDate);

    // Normalize start: kung before 8AM → start at 8AM
    if (start.getHours() < 8) {
      start.setHours(8, 0, 0, 0);
    }

    // Kung lagpas 3PM → next day 8AM start
    if (start.getHours() >= 15) {
      start.setDate(start.getDate() + 1);
      start.setHours(8, 0, 0, 0);
    }

    let due = new Date(start);

    function addWorkingDays(days) {
      for (let i = 0; i < days; i++) {
        due.setDate(due.getDate() + 1);
        // skip weekends kung kailangan (optional)
        // while (due.getDay() === 0 || due.getDay() === 6) {
        //   due.setDate(due.getDate() + 1);
        // }
      }
    }

    switch (priority) {
      case "Top (COB)": {
        // 6 working hours from 8AM start
        due.setHours(due.getHours() + 6);
        if (due.getHours() > 15) {
          // lumampas sa cutoff → next working day 8AM + sobra
          let extra = due.getHours() - 15;
          due.setDate(due.getDate() + 1);
          due.setHours(8 + extra, due.getMinutes(), 0, 0);
        }
        break;
      }

      case "High 1 day": {
        addWorkingDays(1);
        break;
      }

      case "Standard 2 days": {
        addWorkingDays(2);
        break;
      }

      case "Standard 3 days": {
        addWorkingDays(3);
        break;
      }

      case "Standard 4 days": {
        addWorkingDays(4);
        break;
      }

      case "Low 5 days": {
        addWorkingDays(5);
        break;
      }

      case "Low 6 days": {
        addWorkingDays(6);
        break;
      }

      case "Low 7 days": {
        addWorkingDays(7);
        break;
      }

      default:
        return "";
    }

    let optionsDate = { year: "numeric", month: "long", day: "numeric" };
    let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
    let datePart = due.toLocaleDateString("en-US", optionsDate);
    let timePart = due.toLocaleTimeString("en-US", optionsTime);

    let formatted = `${datePart}<br>${timePart}`;

    // Highlight overdue
    let now = new Date();
    let diffHours = (due - now) / (1000 * 60 * 60);

    if (diffHours <= 4 && diffHours > 0) {
      return `<span class="text-danger fw-bold">${formatted}</span>`;
    } else if (diffHours <= 0) {
      return `<span class="text-danger fw-bold">${formatted} (Overdue)</span>`;
    }

    return formatted;
  }

  loadJob();

});