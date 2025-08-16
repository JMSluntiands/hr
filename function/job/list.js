$(document).ready(function () {
  let table = $("#jobTable").DataTable({
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    buttons: [
      {
        extend: 'excelHtml5',
        title: 'Job List',
        text: 'Export to Excel',
        className: 'btn btn-success btn-sm'
      }
    ],
    columnDefs: [
      { responsivePriority: 1, targets: 0 }, // pinakaimportante (Job Ref)
      { responsivePriority: 2, targets: -1 } // Actions or Status
    ]
  });

  // Load job data
  function loadJob() {
    $("#jobBody").html(`<tr><td colspan="7" class="text-center">Loading...</td></tr>`);

    $.ajax({
      url: "../controller/job/list",
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (!response.data || !Array.isArray(response.data)) {
          console.error("Invalid response format:", response);
          toastr.error("Invalid response format from server.", "Error");
          return;
        }

        table.clear().draw();

        if (response.data.length > 0) {
          response.data.forEach(item => {
            table.row.add([
              // Column 1: Job Reference + Client
              `
              <strong>${item.job_reference_no}</strong><br>
              <small class="text-muted">${item.client_account} (${item.client_code})</small>
              `,

              // Column 2: Job Info (priority + complexity)
              `
              <span class="badge bg-primary">${item.priority}</span><br>
              <small>Complexity: ${item.plan_complexity}</small>
              `,

              // Column 3: Address + Client Ref
              `
              ${item.job_address || "-"}<br>
              <small>Client Ref: ${item.client_reference_no || "N/A"}</small>
              `,

              // Column 4: Status + Date
              `
              <span class="badge ${item.job_status === "Completed" ? "bg-success" : "bg-secondary"}">
                ${item.job_status}
              </span><br>
              <small>${item.log_date}</small>
              `
            ]).draw(false);
          });
        } else {
          $("#jobBody").html(`<tr><td colspan="7" class="text-center text-muted">No records found.</td></tr>`);
        }

        $("#jobCount").text("Total Records: " + response.count);
      },
      error: function (xhr) {
        table.clear().draw();
        $("#jobBody").html(`<tr><td colspan="7" class="text-center text-danger">Error loading data.</td></tr>`);
        toastr.error("Error fetching data: " + xhr.responseText, "Error");
      }
    });
  }

  loadJob();
});
