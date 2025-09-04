$("#uploadPlans").on("change", function (e) {
  let files = Array.from(e.target.files);
  plansFiles = plansFiles.concat(files);
  refreshPreview(plansFiles, "plansPreview", "plansCount");
  $(this).val("");
});