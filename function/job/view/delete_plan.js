$(".delete-plan").on("click", function () {
  let filename = $(this).data("filename");
  let ref = $(this).data("ref");
  function getSafeDate() {
    let createdAt = new Date();
    return createdAt.getFullYear() + "-" +
      String(createdAt.getMonth() + 1).padStart(2, "0") + "-" +
      String(createdAt.getDate()).padStart(2, "0") + " " +
      String(createdAt.getHours()).padStart(2, "0") + ":" +
      String(createdAt.getMinutes()).padStart(2, "0") + ":" +
      String(createdAt.getSeconds()).padStart(2, "0");
  }

  let safeDate = getSafeDate(); // 🕒 JS time

  if (!confirm("Are you sure you want to delete this file?")) return;

  $.ajax({
    url: "../controller/job/delete_plan.php",
    type: "POST",
    data: { filename: filename, ref: ref, safeDate: safeDate },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        toastr.success(res.message);
        // tanggalin sa UI agad
        $(`[data-filename='${filename}'][data-ref='${ref}']`).closest("li").remove();
        loadActivityLogs();
        setTimeout(() => {
          location.reload();
        }, 1000);
      } else {
        toastr.error(res.message);
      }
    },
    error: function () {
      toastr.error("Something went wrong. Please try again.");
    }
  });
});