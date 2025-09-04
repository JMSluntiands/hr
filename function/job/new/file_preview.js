let plansFiles = [];
let docsFiles = [];

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

  $("#" + countContainer).text(fileArray.length + " file(s)");
}