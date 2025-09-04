$("#uploadDocs").on("change", function (e) {
  let files = Array.from(e.target.files);
  docsFiles = docsFiles.concat(files);
  refreshPreview(docsFiles, "docsPreview", "docsCount");
  $(this).val("");
});