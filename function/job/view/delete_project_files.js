$(document).on("click", ".delete-doc", function () {
  let $btn = $(this);
  let filename = $btn.data("filename");
  let ref = $btn.data("ref");

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

  if (!confirm("Are you sure you want to delete this document?")) return;

  $.ajax({
    url: "../controller/job/delete_project_files.php",
    type: "POST",
    data: { filename: filename, ref: ref, safeDate: safeDate }, // ⬅️ kasama na
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        toastr.success(res.message);

        // remove <li>
        let $li = $btn.closest("li");
        let $list = $li.closest(".list-group");
        $li.remove();

        // kapag empty na list
        if ($list.find("li").length === 0) {
          $list.replaceWith('<p class="text-muted">No documents uploaded.</p>');
        }
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
