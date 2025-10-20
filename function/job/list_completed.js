$(document).ready(function () {
  // 🔹 Init DataTable
  let table = $("#jobTable").DataTable({
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    data: [],
    buttons: [],
    columnDefs: [
      { responsivePriority: 1, targets: 0 },
      { responsivePriority: 2, targets: -1 },

      // 🔹 Hide client_name if userRole is not LUNTIAN
      ...(userRole !== "LUNTIAN" ? [
        { targets: [3], visible: false, searchable: true }
      ] : [])
    ]
  });

  function loadJob() {
    $.ajax({
      url: "../controller/job/list_completed.php",
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
          return dateObj.toLocaleDateString("en-US", optionsDate) + "<br>" +
            dateObj.toLocaleTimeString("en-US", optionsTime);
        }

        function computeDueDate(startDate, priority) {
          if (!startDate) return "";
          let d = new Date(startDate);
          if (priority === "Top (COB)") d.setDate(d.getDate() + 0);
          else if (priority === "High 1 day") d.setDate(d.getDate() + 1);
          else if (priority === "Standard 2 days") d.setDate(d.getDate() + 2);
          else d.setDate(d.getDate() + 3);
          return d.toLocaleDateString("en-US");
        }

        table.clear().draw();

        response.data.forEach(item => {
          table.row.add([
            `<div class="d-flex justify-content-center gap-1">
              <a class="btn btn-sm btn-info text-white rounded-0" title="View" href="job-view?id=${item.job_id}">
                <i class="si si-eye"></i>
              </a>
              <a class="btn btn-sm btn-dark rounded-0" title="Duplicate" href="job-duplicate?id=${item.job_id}">
                <i class="si si-docs"></i>
              </a>
            </div>`,

            `<div class="text-center">${formatDateTime(item.log_date)}</div>`,
            `<div class="text-center">
                <span style="font-size: 12px">${item.client_account_name}</span>
                <small>${item.ncc_compliance}</small>
             </div>`,
            `<div class="text-center"><span>${item.client_name}</span></div>`,
            `<div class="text-center"><strong>${item.job_reference_no}</strong></div>`,
            `<div class="text-center"><span>${item.job_type}</span></div>`,

            `<div class="text-center">
              <span class="badge text-dark" 
                style="background-color: ${item.priority === "Top (COB)" ? "#F74639" :
              item.priority === "High 1 day" ? "#FFA775" :
                item.priority === "Standard 2 days" ? "#FF71CF" : "#CF7AFA"}">
                ${item.priority}
              </span>
            </div>`,

            `<span><strong>${item.staff_id}</strong></span>`,
            `<span><strong>${item.checker_id}</strong></span>`,

            `<span class="badge text-dark"
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
            </span>`,

            `<div class="text-center">${computeDueDate(item.log_date, item.priority)}</div>`,
            `<div class="text-center">${formatDateTime(item.completion_date)}</div>`,

            `<div class="text-center">
              ${[1, 2, 3, 4, 5].map(i =>
              `<i class="fa fa-star ${i <= item.complexity ? 'text-warning' : 'text-secondary'}" 
                    style="font-size:12px; margin:0 2px;"></i>`
            ).join("")}
             </div>`,

            `<div class="text-center"><span>${item.notes || ''}</span></div>`
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
