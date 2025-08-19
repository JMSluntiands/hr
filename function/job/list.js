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
      { responsivePriority: 2, targets: -1 } // Actions
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
            // format log_date
            let formattedDate = "";
            if (item.log_date) {
              let dateObj = new Date(item.log_date);
              let optionsDate = { year: "numeric", month: "long", day: "numeric" };
              let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
              let datePart = dateObj.toLocaleDateString("en-US", optionsDate);
              let timePart = dateObj.toLocaleTimeString("en-US", optionsTime);
              formattedDate = `${datePart}<br>${timePart}`;
            }

            // format last_update
            let formattedUpdate = "";
            if (item.last_update) {
              let updateObj = new Date(item.last_update);
              let optionsDate = { year: "numeric", month: "long", day: "numeric" };
              let optionsTime = { hour: "numeric", minute: "numeric", hour12: true };
              let datePart = updateObj.toLocaleDateString("en-US", optionsDate);
              let timePart = updateObj.toLocaleTimeString("en-US", optionsTime);
              formattedUpdate = `${datePart}<br>${timePart}`;
            }

            table.row.add([
              // Column 1: Action Buttons
              `
              <div class="d-flex justify-content-center align-items-center gap-1">
                <button class="btn btn-sm btn-info text-white" title="View" onclick="viewJob('${item.id}')">
                  <i class="fa fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-warning text-white" title="Edit" onclick="editJob('${item.id}')">
                  <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteJob('${item.id}')">
                  <i class="fa fa-trash"></i>
                </button>
                <button class="btn btn-sm btn-secondary" title="Duplicate" onclick="duplicateJob('${item.id}')">
                  <i class="fa fa-copy"></i>
                </button>
              </div>

              `,

              // Column 2: Log Date
              `<div class="d-flex justify-content-center align-items-center w-100 h-100">
                <span>${formattedDate}</span>
              </div>`,

              // Column 3: Job Info
              `
              <span style="font-size: 12px">${item.client_account_name}</span><br>
              <small>Complexity: ${item.client_code}</small>
              `,

              // Column 4: Address + Client Ref
              `
              <div class="d-flex justify-content-center items-center w-100 h-100"><strong>${item.start_ref}${item.job_reference_no}</strong></div>
              `,
              `
              <span><strong>${item.client_reference_no}</strong></span>
              `,

              // Column 5: Staff / Checker
              `
              <small>Staff: ${item.staff_name}</small><br>
              <small>Checker: ${item.checker_name}</small>
              `,

              // Column 6: Status
              `
              <span 
                class="badge text-dark" 
                style="background-color: ${item.priority === "Top" ? "#FFCFA4" :
                item.priority === "High (1 day)" ? "#FFD8CF" :
                  item.priority === "Standard (2 days)" ? "#FFD8CF" :
                    item.priority === "Standard (3 days)" ? "#E0D2FF" :
                      "#6c757d" // default gray
              }"
              >
                ${item.job_status}
              </span>

              `,

              // Column 7: Last Update
              `<div class="d-flex justify-content-center align-items-center w-100 h-100">
                <span>${formattedUpdate}</span>
              </div>`,
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

// Sample functions (to be implemented)
function viewJob(id) { alert("View Job: " + id); }
function editJob(id) { alert("Edit Job: " + id); }
function deleteJob(id) { alert("Delete Job: " + id); }
function duplicateJob(id) { alert("Duplicate Job: " + id); }
