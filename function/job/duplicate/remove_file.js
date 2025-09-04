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