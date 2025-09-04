$('#editJobForm').on('submit', function (e) {
  e.preventDefault();

  const fd = new FormData(this);

  const pdfOk = (f) => f && f.type === 'application/pdf';
  const sizeOk = (f) => f && f.size <= (10 * 1024 * 1024);

  const plans = $('#uploadPlans')[0].files;
  const docs = $('#uploadDocs')[0].files;

  for (let f of plans) {
    if (!pdfOk(f)) { toastr.error('All Plans must be PDF.', 'Invalid File'); return; }
    if (!sizeOk(f)) { toastr.error('Plans file exceeds 10MB.', 'Too Large'); return; }
  }
  for (let f of docs) {
    if (!pdfOk(f)) { toastr.error('All Documents must be PDF.', 'Invalid File'); return; }
    if (!sizeOk(f)) { toastr.error('Document file exceeds 10MB.', 'Too Large'); return; }
  }

  $.ajax({
    url: '../controller/job/job_update.php',
    type: 'POST',
    data: fd,
    contentType: false,
    processData: false,
    dataType: 'json',
    success: function (res) {
      if (res.status === 'success') {
        toastr.success(res.message || 'Job updated.', 'Success');
        setTimeout(() => {
          window.location.href = "job";
        }, 900);
      } else {
        toastr.error(res.message || 'Update failed.', 'Error');
      }
    },
    error: function (xhr) {
      toastr.error('Something went wrong.', 'Error');
      console.error(xhr.responseText);
    }
  });
});