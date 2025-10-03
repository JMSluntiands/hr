function getSafeDate() {
  let createdAt = new Date();
  return createdAt.getFullYear() + "-" +
    String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
    String(createdAt.getDate()).padStart(2, "0") + " " +
    String(createdAt.getHours()).padStart(2, "0") + ":" +
    String(createdAt.getMinutes()).padStart(2, "0") + ":" +
    String(createdAt.getSeconds()).padStart(2, "0");
}

$(document).on("click", ".add-files", function () {
  let ref = $(this).data("ref");
  let column = $(this).data("column");

  // Hidden file input (multiple)
  let input = $('<input type="file" accept=".pdf" multiple style="display:none">');
  $("body").append(input);
  input.trigger("click");

  input.on("change", function () {
    let files = input[0].files;
    if (files.length === 0) return;

    let formData = new FormData();
    formData.append("ref", ref);
    formData.append("column", column);
    formData.append("safeDate", getSafeDate());
    for (let i = 0; i < files.length; i++) {
      formData.append("new_files[]", files[i]);
    }

    $.ajax({
      url: "../controller/job/add_files.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          toastr.success(res.message);
          loadActivityLogs();

          setTimeout(() => {
            location.reload();
          }, 1000);

          // choose correct list (plans or documents)
          let listSelector = (column === "upload_files") ? "#plans-list" : "#docs-list";

          // append uploaded files
          res.files.forEach(file => {
            $(listSelector).append(`
              <li class="list-group-item d-flex align-items-center">
                <i class="fa fa-file-pdf text-danger me-2"></i>
                <a href="../document/${ref}/${file}" target="_blank">${file}</a>
              </li>
            `);
          });
        } else {
          toastr.error(res.message);
        }
      },
      error: function () {
        toastr.error("Something went wrong. Please try again.");
      }
    });

    input.remove(); // cleanup
  });
});
