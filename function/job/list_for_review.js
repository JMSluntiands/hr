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

            // ✅ Log Date
            `<div class="text-center">${formatDateTime(item.log_date)}</div>`,

            // Client
            `<span style="font-size: 12px">${item.client_account_name}</span><br><small>${item.ncc_compliance}</small>`,

            // Reference
            `<strong>${item.job_reference_no}</strong>`,

            // Client Ref
            `<span><strong>${item.client_reference_no}</strong></span>`,

            // // Staff / Checker
            `<small>
                <span class="">
                     Staff : 
                </span> ${item.staff_name ?? ""}
              </small><br>
              <small>
                <span class="">
                    Checker : 
                </span> ${item.checker_name ?? ""}
              </small><br>
            <small>`,
            // Status
            `
            <span class="badge text-dark" 
              style="background-color: ${item.priority === "Top (COB)" ? "#F74639" :
              item.priority === "High 1 day" ? "#FFA775" :
                item.priority === "Standard 2 days" ? "#FF71CF" :
                  item.priority === "Standard 3 days" ? "#CF7AFA" : "#6c757d"}">
              ${item.job_status}
            </span>
            `,
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

  loadJob();

});