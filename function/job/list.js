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
    let status = $(table.cell(dataIndex, 6).node()).text().trim();
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
              <button class="btn btn-sm btn-info text-white rounded-0" title="View" onclick="viewJob('${item.job_id}')">
                <i class="si si-eye"></i>
              </button>

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
            `<strong>${item.start_ref}${item.job_reference_no}</strong>`,

            // Client Ref
            `<span><strong>${item.client_reference_no}</strong></span>`,

            // // Staff / Checker
            // `<small>Staff: <span class="badge bg-info">${item.staff_name ?? ""}</span></small><br><small>Checker: <span class="badge bg-info">${item.checker_name ?? ""}</span></small>`,
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
              style="background-color: ${item.priority === "Top" ? "#F74639" :
              item.priority === "High (1 day)" ? "#FFA775" :
                item.priority === "Standard (2 days)" ? "#FF71CF" :
                  item.priority === "Standard (3 days)" ? "#CF7AFA" : "#6c757d"}">
              ${item.job_status}
            </span>
            `,

            // ✅ Completion Date
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