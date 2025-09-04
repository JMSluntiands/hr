let plansFiles = [];
let docsFiles = [];
let removedPlans = [];
let removedDocs = [];
const MAX_SIZE = 10 * 1024 * 1024;

$("#uploadPlans").on("change", function (e) {
  let files = Array.from(e.target.files);
  files.forEach(f => {
    if (f.size > MAX_SIZE) {
      toastr.error(`${f.name} exceeds 10MB limit.`);
    } else {
      plansFiles.push(f);
    }
  });
  refreshPreview(plansFiles, "plansPreview", "plansCount");
  $(this).val("");
});

$("#uploadDocs").on("change", function (e) {
  let files = Array.from(e.target.files);
  files.forEach(f => {
    if (f.size > MAX_SIZE) {
      toastr.error(`${f.name} exceeds 10MB limit.`);
    } else {
      docsFiles.push(f);
    }
  });
  refreshPreview(docsFiles, "docsPreview", "docsCount");
  $(this).val("");
});