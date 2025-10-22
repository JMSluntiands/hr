$(document).ready(function () {
  loadAnnouncements();

  $("#addAnnouncementForm").on("submit", function (e) {
    e.preventDefault();
    $.ajax({
      url: "../controller/announcement/add-announcement.php",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          toastr.success(response.message);
          $("#addAnnouncementModal").modal("hide");
          loadAnnouncements();
          $("#addAnnouncementForm")[0].reset();
        } else {
          toastr.error(response.message);
        }
      },
      error: function (xhr) {
        toastr.error("Error: " + xhr.responseText);
      }
    });
  });
});

// 🧠 When "Edit" button is clicked
$(document).on("click", ".editBtn", function () {
  const btn = $(this);

  $("#edit_id").val(btn.data("id"));
  $("#edit_title").val(btn.data("title"));
  $("#edit_message").val(btn.data("message"));
  $("#edit_start_date").val(btn.data("start").replace(" ", "T"));
  $("#edit_end_date").val(btn.data("end") ? btn.data("end").replace(" ", "T") : "");
  $("#edit_status").val(btn.data("status"));

  $("#editAnnouncementModal").modal("show");
});

// 🧩 Handle form submit
$("#editAnnouncementForm").on("submit", function (e) {
  e.preventDefault();

  $.ajax({
    url: "../controller/announcement/update-announcement.php",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        toastr.success(response.message);
        $("#editAnnouncementModal").modal("hide");
        loadAnnouncements(); // 🔁 Reload table
      } else {
        toastr.error(response.message);
      }
    },
    error: function (xhr) {
      toastr.error("Error: " + xhr.responseText);
    }
  });
});

function loadAnnouncements() {
  $.ajax({
    url: "../controller/announcement/fetch-announcement.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      let table = $("#announcementTable").DataTable();
      table.clear().draw();

      response.data.forEach(item => {
        table.row.add([
          `<button class="btn btn-sm btn-primary editBtn" 
   data-id="${item.id}" 
   data-title="${item.title}" 
   data-message="${item.message}" 
   data-start="${item.start_date}" 
   data-end="${item.end_date}" 
   data-status="${item.status}">
   <i class="fa fa-edit"></i> Edit
</button>`,
          item.title,
          item.message,
          item.status,
          item.start_date,
          item.end_date
        ]).draw(false);
      });

      $("#announcementCount").text("Total Records: " + response.data.length);
    },
    error: function (xhr) {
      toastr.error("Error fetching data: " + xhr.responseText);
    }
  });
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}
