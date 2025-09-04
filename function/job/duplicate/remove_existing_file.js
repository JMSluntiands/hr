$(document).on("click", ".remove-existing-file", function () {
  let fileName = $(this).data("file");
  let $row = $(this).closest(".existing-file-row");
  if ($row.parent().attr("id") === "plansPreview") {
    removedPlans.push(fileName);
  } else if ($row.parent().attr("id") === "docsPreview") {
    removedDocs.push(fileName);
  }
  $row.remove();
  $("#plansCount").text($("#plansPreview .existing-file-row").length + plansFiles.length + " file(s)");
  $("#docsCount").text($("#docsPreview .existing-file-row").length + docsFiles.length + " file(s)");
});