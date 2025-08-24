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

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    let selected = $('#statusFilter').val();
    let status = $(table.cell(dataIndex, 6).node()).text().trim();
    return selected === "" || status === selected;
  });

  $(document).on("change", "#statusFilter", function () {
    table.draw();
  });

  // 🔹 Init Select2 for Job Request
  $('#jobRequest').select2({
    placeholder: "Select or search job request",
    width: '100%',
    dropdownParent: $('#newJobModal .modal-content'),
    minimumResultsForSearch: 2, // 🚫 disable search box
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

  $('#newJobModal').on('hidden.bs.modal', function () {
    // reset all inputs sa form
    $('#newJobForm')[0].reset();

    // clear Select2 if gamit ka
    $('#jobRequest').val(null).trigger('change');
    $('#clientID').val(null).trigger('change');

    // clear previews & counters
    $('#plansPreview').empty();
    $('#docsPreview').empty();
    $('#plansCount').text("0 files");
    $('#docsCount').text("0 files");
  });

  let defaultJobRequest = { id: "EA_LBS_1SDB", text: "1S DB Base Model- 1S Design Builder Model" };

  let optionJob = new Option(defaultJobRequest.text, defaultJobRequest.id, true, true);
  $('#jobRequest').append(optionJob).trigger('change');


  $('#clientID').select2({
    placeholder: "Select or search Client",
    width: '100%',
    dropdownParent: $('#newJobModal .modal-content'),
    minimumResultsForSearch: 2,
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

    // ✅ Filter out any empty or invalid files
    plansFiles = plansFiles.filter(file => file && file.name);
    docsFiles = docsFiles.filter(file => file && file.name);

    let formData = new FormData(this);

    // ✅ Append valid plans
    for (let i = 0; i < plansFiles.length; i++) {
      formData.append("plans[]", plansFiles[i]);
    }

    // ✅ Append valid docs
    for (let i = 0; i < docsFiles.length; i++) {
      formData.append("docs[]", docsFiles[i]);
    }

    $.ajax({
      url: "../controller/job/job_save.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          toastr.success(response.message, "Success");
          $("#newJobModal").modal("hide");
          $("#newJobForm")[0].reset();
          $('#jobRequest').val(null).trigger('change');
          $('#clientID').val(null).trigger('change');
          $("#uploadPlans").val("");
          $("#uploadDocs").val("");
          $("#plansPreview").html("");
          $("#docsPreview").html("");

          // ✅ Reset file arrays
          plansFiles = [];
          docsFiles = [];

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
    width: '100%',
    minimumResultsForSearch: Infinity,
  });
  $('.priority').select2({
    width: '100%',
    minimumResultsForSearch: Infinity,
  });

  $('.checked').select2({
    width: '100%',
    minimumResultsForSearch: Infinity,
  });
  $('.assign').select2({
    width: '100%',
    minimumResultsForSearch: Infinity,
  });

  let plansFiles = [];
  let docsFiles = [];

  // Refresh preview + update badge
  function refreshPreview(fileArray, previewContainer, countContainer) {
    let $preview = $("#" + previewContainer);
    $preview.empty();

    $.each(fileArray, function (i, file) {
      let $row = $(`
        <div class="d-flex align-items-center border p-2 mb-1 rounded file-row">
          <i class="fa fa-file-pdf text-danger me-2 fs-4"></i>
          <span class="flex-grow-1">${file.name}</span>
          <button type="button" class="btn btn-sm btn-danger remove-file" data-index="${i}" data-target="${previewContainer}">
            <i class="fa fa-times"></i>
          </button>
        </div>
      `);
      $preview.append($row);
    });

    // update badge text
    $("#" + countContainer).text(fileArray.length + " file(s)");
  }

  // Handle file input changes
  $("#uploadPlans").on("change", function (e) {
    let files = Array.from(e.target.files);
    plansFiles = plansFiles.concat(files);
    refreshPreview(plansFiles, "plansPreview", "plansCount");
    $(this).val(""); // reset para makapili ulit
  });

  $("#uploadDocs").on("change", function (e) {
    let files = Array.from(e.target.files);
    docsFiles = docsFiles.concat(files);
    refreshPreview(docsFiles, "docsPreview", "docsCount");
    $(this).val("");
  });

  // Handle remove click
  $(document).on("click", ".remove-file", function () {
    let index = $(this).data("index");
    let target = $(this).data("target");

    if (target === "plansPreview") {
      plansFiles.splice(index, 1);
      refreshPreview(plansFiles, "plansPreview", "plansCount");
    } else if (target === "docsPreview") {
      docsFiles.splice(index, 1);
      refreshPreview(docsFiles, "docsPreview", "docsCount");
    }
  });
});
