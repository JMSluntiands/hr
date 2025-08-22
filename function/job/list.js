$(document).ready(function () {
  // 🔹 Init DataTable
  let table = $("#jobTable").DataTable({
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    data: [], // manual loading
    buttons: [
      // {
      //   extend: 'excelHtml5',
      //   title: 'Job List',
      //   text: 'Export to Excel',
      //   className: 'btn btn-success btn-sm form-control'
      // }
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

  // 🔹 Status Filter Logic (column 6 = Status)
  $(document).on("change", "#statusFilter", function () {
    let val = $(this).val();
    table.column(6).search(val ? '^' + val + '$' : '', true, false).draw();
  });

  // 🔹 Init Select2 for Job Request
  $('#jobRequest').select2({
    placeholder: "Select or search job request",
    width: '100%',
    dropdownParent: $('#newJobModal .modal-content'),
    minimumInputLength: 1,
    ajax: {
      url: "../controller/job/job_select",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return { q: params.term };
      },
      processResults: function (data) {
        return {
          results: data.map(function (item) {
            return { id: item.id, text: item.text };
          })
        };
      }
    }
  });

  let defaultJobRequest = { id: "EA_LBS_1SDB", text: "1S DB Base Model- 1S Design Builder Model" };

  let optionJob = new Option(defaultJobRequest.text, defaultJobRequest.id, true, true);
  $('#jobRequest').append(optionJob).trigger('change');


  $('#clientID').select2({
    placeholder: "Select or search Client",
    width: '100%',
    dropdownParent: $('#newJobModal .modal-content'),
    minimumInputLength: 1,
    ajax: {
      url: "../controller/job/client",
      dataType: "json",
      delay: 250,
      data: function (params) {
        return { q: params.term };
      },
      processResults: function (data) {
        return {
          results: data.map(function (item) {
            return { id: item.id, text: item.text };
          })
        };
      }
    }
  });

  let defaultClient = { id: "7", text: "Summit Homes Group" };

  let option = new Option(defaultClient.text, defaultClient.id, true, true);
  $('#clientID').append(option).trigger('change');


  $("#newJobForm").on("submit", function (e) {
    e.preventDefault();

    $.ajax({
      url: "../controller/job/job_save",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          toastr.success(response.message, "Success");
          $("#newJobModal").modal("hide");
          $("#newJobForm")[0].reset();
          $('#jobRequest').val(null).trigger('change');
          $('#clientID').val(null).trigger('change');

          // 🔄 refresh job list
          loadJob();
        } else {
          toastr.error(response.message, "Error");
        }
      },
      error: function () {
        toastr.error("Something went wrong. Please try again.", "Error");
      }
    });
  });

  function loadJob() {
    $.ajax({
      url: "../controller/job/list",
      type: "GET",
      dataType: "json",
      success: function (response) {
        console.log("Response from server:", response);

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
              <button class="btn btn-sm btn-info text-white" title="View" onclick="viewJob('${item.job_id}')"><i class="fa fa-eye"></i></button>
              <button class="btn btn-sm btn-warning text-white" title="Edit" onclick="editJob('${item.job_id}')"><i class="fa fa-edit"></i></button>
              <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteJob('${item.job_id}')"><i class="fa fa-trash"></i></button>
              <button class="btn btn-sm btn-secondary" title="Duplicate" onclick="duplicateJob('${item.job_id}')"><i class="fa fa-copy"></i></button>
            </div>
            `,

            // ✅ Log Date
            `<div class="text-center">${formatDateTime(item.log_date)}</div>`,

            // Client
            `<span style="font-size: 12px">${item.client_account_name}</span><br><small>Complex</small>`,

            // Reference
            `<strong>${item.start_ref}${item.job_reference_no}</strong>`,

            // Client Ref
            `<span><strong>${item.client_reference_no}</strong></span>`,

            // Staff / Checker
            `<small>Staff: ${item.staff_name ?? ""}</small><br><small>Checker: ${item.checker_name ?? ""}</small>`,

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

  $('.compliance').select2({
    width: '100%'
  });
  $('.priority').select2({
    width: '100%'
  });

  $('.checked').select2({
    width: '100%'
  });
  $('.assign').select2({
    width: '100%'
  });

});
