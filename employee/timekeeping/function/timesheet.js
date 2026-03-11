function initTimesheetPage() {
  // Simple navigation between timesheet and payslip
  $('.js-side-link').on('click', function (e) {
    const url = $(this).data('url');
    if (!url) return;
    e.preventDefault();

    if (url === 'index.php' || url === 'payslip.php') {
      window.location.href = url;
      return;
    }
  });

  // Client-side validation for date range (From cannot be after To)
  const rangeForm = document.getElementById('fillRangeForm');
  if (rangeForm) {
    rangeForm.addEventListener('submit', function (e) {
      const fromInput = document.getElementById('from');
      const toInput = document.getElementById('to');
      if (!fromInput || !toInput) return;

      const fromVal = fromInput.value;
      const toVal = toInput.value;
      if (fromVal && toVal && fromVal > toVal) {
        e.preventDefault();
        alert('"Date From" cannot be after "Date To". Please adjust the range.');
      }
    });
  }

  // Add new task row inside a day card (max 16 rows per day)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-add-row]');
    if (!btn) return;

    const date = btn.getAttribute('data-date');
    if (!date) return;

    const tbody = document.querySelector('[data-tbody-date="' + date + '"]');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('tr[data-row-index]');
    if (!rows.length) return;

    // Hard cap: max 16 rows per day
    if (rows.length >= 16) {
      alert('Maximum of 16 task rows per day.');
      return;
    }

    const lastRow = rows[rows.length - 1];
    const nextIndex = rows.length + 1;

    const newRow = lastRow.cloneNode(true);
    newRow.setAttribute('data-row-index', String(nextIndex));

    // Update inputs and clear values
    newRow.querySelectorAll('input').forEach(function (input) {
      if (input.name) {
        input.name = input.name.replace(/\[\d+\]$/, '[' + nextIndex + ']');
      }
      if (input.type === 'time' || input.type === 'text') {
        input.value = '';
      }
    });

    // Reset total text if present
    const totalSpan = newRow.querySelector('[data-total]');
    if (totalSpan) {
      totalSpan.textContent = '0:00';
    }

    tbody.appendChild(newRow);
  });

  // AJAX save for timesheet
  const form = document.getElementById('timesheetForm');
  const saveBtn = document.getElementById('saveTimesheetBtn');
  if (form && saveBtn) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = $(form).serialize();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      $.post('save-timesheet.php', formData, function (res) {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Timesheet';

        if (res && res.status === 'success') {
          alert(res.message || 'Timesheet saved.');
        } else {
          alert(res && res.message ? res.message : 'Failed to save timesheet.');
        }
      }, 'json').fail(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Timesheet';
        alert('Failed to save timesheet. Please try again.');
      });
    });
  }

  // Overview: click day to show details
  document.addEventListener('click', function (e) {
    const cell = e.target.closest('[data-overview-date]');
    if (!cell) return;

    const date = cell.getAttribute('data-overview-date');
    if (!date) return;

    const detailsContainer = document.getElementById('overviewDetails');
    if (!detailsContainer) return;

    // Visual highlight
    document
      .querySelectorAll('[data-overview-date]')
      .forEach(function (td) { td.classList.remove('ring-2', 'ring-[#FA9800]'); });
    cell.classList.add('ring-2', 'ring-[#FA9800]');

    detailsContainer.classList.remove('hidden');
    detailsContainer.innerHTML = '<div class="text-xs text-slate-500 px-2 py-1">Loading details...</div>';

    $.getJSON('get-day-details.php', { date: date }, function (res) {
      if (!res || res.status !== 'success') {
        detailsContainer.innerHTML = '<div class="text-xs text-red-600 px-2 py-1">' +
          (res && res.message ? res.message : 'Failed to load details.') +
          '</div>';
        return;
      }

      if (!res.rows || !res.rows.length) {
        detailsContainer.innerHTML = '<div class="text-xs text-slate-500 px-2 py-2 border border-slate-200 rounded-lg bg-slate-50">' +
          'No entries for ' + res.display_date + '.</div>';
        return;
      }

      var html = '';
      html += '<div class="border border-slate-200 rounded-xl bg-white overflow-hidden">';
      html += '  <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50">';
      html += '    <div class="text-sm font-semibold text-slate-800">Details for ' + res.display_date + '</div>';
      html += '    <div class="text-xs text-slate-500">Total: <span class="font-semibold">' + res.total_label + '</span></div>';
      html += '  </div>';
      html += '  <div class="overflow-x-auto">';
      html += '    <table class="min-w-full text-xs">';
      html += '      <thead class="bg-slate-50 text-slate-600">';
      html += '        <tr>';
      html += '          <th class="px-3 py-2 text-left font-semibold">#</th>';
      html += '          <th class="px-3 py-2 text-left font-semibold">Description</th>';
      html += '          <th class="px-3 py-2 text-center font-semibold">Start</th>';
      html += '          <th class="px-3 py-2 text-center font-semibold">Finish</th>';
      html += '          <th class="px-3 py-2 text-center font-semibold">Total</th>';
      html += '        </tr>';
      html += '      </thead>';
      html += '      <tbody class="divide-y divide-slate-100">';

      res.rows.forEach(function (row) {
        html += '<tr class="hover:bg-slate-50">';
        html += '  <td class="px-3 py-1.5 text-slate-700 text-center">' + row.row_number + '</td>';
        html += '  <td class="px-3 py-1.5 text-slate-700">' + (row.description || '&mdash;') + '</td>';
        html += '  <td class="px-3 py-1.5 text-slate-700 text-center">' + (row.time_start || '&mdash;') + '</td>';
        html += '  <td class="px-3 py-1.5 text-slate-700 text-center">' + (row.time_end || '&mdash;') + '</td>';
        html += '  <td class="px-3 py-1.5 text-slate-700 text-center">' + row.total_label + '</td>';
        html += '</tr>';
      });

      html += '      </tbody>';
      html += '    </table>';
      html += '  </div>';
      html += '</div>';

      detailsContainer.innerHTML = html;
    }).fail(function () {
      detailsContainer.innerHTML = '<div class="text-xs text-red-600 px-2 py-1">Failed to load details. Please try again.</div>';
    });
  });
}

$(initTimesheetPage);

