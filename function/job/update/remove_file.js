$(document).on('click', '.btn-remove-file', function () {
  const job_id = $(this).data('id');
  const type = $(this).data('type');
  const file = $(this).data('file');
  const row = $(this).closest('.file-row');

  if (!confirm('Remove this file?')) return;

  $.ajax({
    url: '../controller/job/job_file_remove.php',
    type: 'POST',
    dataType: 'json',
    data: { job_id, type, file },
    success: function (res) {
      if (res.status === 'success') {
        toastr.warning(res.message, 'Removed');
        row.remove();
        if (type === 'plans') {
          const n = parseInt($('#plansCount').text()) - 1;
          $('#plansCount').text((n < 0 ? 0 : n) + ' files');
        } else {
          const n = parseInt($('#docsCount').text()) - 1;
          $('#docsCount').text((n < 0 ? 0 : n) + ' files');
        }
      } else {
        toastr.error(res.message || 'Failed to remove file', 'Error');
      }
    },
    error: function () {
      toastr.error('Something went wrong.', 'Error');
    }
  });
});