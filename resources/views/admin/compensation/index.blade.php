@extends('layouts.admin')
@section('title', 'Compensation - Salary Adjustment & History')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Compensation</h1>
        <p class="text-sm text-slate-500 mt-1">Salary Adjustment &amp; History</p>
    </div>
    <button type="button" id="addAdjustmentBtn" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706] transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Add Salary Adjustment</span>
    </button>
</div>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-800">Salary Adjustment &amp; History</h2>
        <button type="button"
                class="js-comp-privacy-view inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-medium text-[#c2410c] bg-white hover:bg-orange-50 transition-colors"
                data-target="adminCompTableBody"
                data-placeholder="adminCompTablePlaceholder"
                aria-label="View details">
            <svg class="w-4 h-4 js-eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/>
            </svg>
            <svg class="w-4 h-4 js-eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.943-9.543-7a9.965 9.965 0 012.11-3.592M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.27 2.943 9.543 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 00-4.2-2.8M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18"/>
            </svg>
            <span class="js-comp-privacy-label">View</span>
        </button>
    </div>
    <div id="adminCompTablePlaceholder" class="px-6 py-8 text-center text-sm text-slate-500">
        <p>Salary amounts are hidden.</p>
        <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show the adjustment table.</p>
    </div>
    <div id="adminCompTableBody" class="p-6 overflow-x-auto hidden">
        <table id="adjustmentsTable" class="min-w-full text-sm w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Previous Salary</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">New Salary</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reason</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved By</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($adjustments as $adjustment)
                <tr>
                    <td class="px-4 py-3 text-slate-700">
                        <div class="font-medium">{{ $adjustment->full_name ?? 'N/A' }}</div>
                        <div class="text-xs text-slate-500">{{ $adjustment->employee_code ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">₱{{ number_format((float) $adjustment->previous_salary, 2) }}</td>
                    <td class="px-4 py-3 text-slate-700 font-semibold">₱{{ number_format((float) $adjustment->new_salary, 2) }}</td>
                    <td class="px-4 py-3 text-slate-700">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $adjustment->reason }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $adjustment->approved_by ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-slate-700" data-order="{{ $adjustment->date_approved }}">
                        {{ $adjustment->date_approved ? \Carbon\Carbon::parse($adjustment->date_approved)->format('M d, Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">No salary adjustments recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('modals')
<div id="addAdjustmentModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="adjustmentModalTitle">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="adjustmentModalTitle" class="text-lg font-semibold text-slate-800">Add Salary Adjustment</h3>
            <button type="button" id="closeModal" class="text-slate-400 hover:text-slate-600" aria-label="Close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="adjustmentForm" class="p-6 space-y-4">
            <div>
                <label for="employeeSelect" class="block text-sm font-medium text-slate-700 mb-2">Employee <span class="text-red-500">*</span></label>
                <select id="employeeSelect" name="employee_id" class="w-full" required>
                    <option value="">Select Employee</option>
                </select>
            </div>
            <div>
                <label for="previousSalary" class="block text-sm font-medium text-slate-700 mb-2">Previous Salary <span class="text-red-500">*</span></label>
                <input type="number" id="previousSalary" name="previous_salary" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="0.00" required readonly>
            </div>
            <div>
                <label for="newSalary" class="block text-sm font-medium text-slate-700 mb-2">New Salary <span class="text-red-500">*</span></label>
                <input type="number" id="newSalary" name="new_salary" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="0.00" required>
            </div>
            <div>
                <label for="reasonSelect" class="block text-sm font-medium text-slate-700 mb-2">Reason <span class="text-red-500">*</span></label>
                <select id="reasonSelect" name="reason" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" required>
                    <option value="">Select Reason</option>
                    <option value="Promotion">Promotion</option>
                    <option value="Annual Increase">Annual Increase</option>
                    <option value="Adjustment">Adjustment</option>
                </select>
            </div>
            <div>
                <label for="dateApproved" class="block text-sm font-medium text-slate-700 mb-2">Date <span class="text-red-500">*</span></label>
                <input type="date" id="dateApproved" name="date_approved" value="{{ $todayYmd }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" required>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" id="cancelBtn" class="px-4 py-2 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706] transition-colors">Save Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<style>
.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 6px !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    font-size: 14px;
    padding-left: 12px;
    line-height: 42px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100%;
    right: 10px;
}
.select2-dropdown { border: 1px solid #e5e7eb; border-radius: 6px; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/compensation-privacy.js') }}"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
(function () {
    const routes = {
        employees: @json(route('admin.compensation.employees')),
        salary: @json(route('admin.compensation.employee-salary')),
        store: @json(route('admin.compensation.store')),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const approvedBy = @json($adminName);
    const defaultDate = @json($todayYmd);

    function closeModal() {
        $('#addAdjustmentModal').removeClass('flex').addClass('hidden');
        $('#adjustmentForm')[0].reset();
        $('#employeeSelect').val(null).trigger('change');
        $('#previousSalary').val('');
        $('#dateApproved').val(defaultDate);
    }

    function initAdjustmentsTable() {
        const $table = $('#adjustmentsTable');
        if (!$table.length || $.fn.dataTable.isDataTable($table[0])) {
            if ($.fn.dataTable.isDataTable($table[0])) {
                $table.DataTable().columns.adjust().draw(false);
            }
            return;
        }
        const hasRows = $table.find('tbody tr').length && !$table.find('tbody tr td[colspan]').length;
        if (!hasRows) return;
        $table.DataTable({
            order: [[5, 'desc']],
            pageLength: 10,
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries found',
                infoFiltered: '(filtered from _MAX_ total entries)',
            },
        });
    }

    document.getElementById('adminCompTableBody')?.addEventListener('comp-privacy-shown', initAdjustmentsTable);

    $(function () {
        $.getJSON(routes.employees).done(function (response) {
            if (response.status === 'success') {
                const select = $('#employeeSelect');
                response.data.forEach(function (employee) {
                    select.append(new Option(
                        employee.employee_id + ' - ' + employee.full_name,
                        employee.id,
                        false,
                        false
                    ));
                });
            }
        }).fail(function () {
            alert('Error loading employees. Please refresh the page.');
        });

        $('#employeeSelect').select2({
            placeholder: 'Search and select employee...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#addAdjustmentModal'),
        });

        $('#addAdjustmentBtn').on('click', function () {
            $('#addAdjustmentModal').removeClass('hidden').addClass('flex');
        });
        $('#closeModal, #cancelBtn').on('click', closeModal);

        $('#employeeSelect').on('change', function () {
            const employeeId = $(this).val();
            if (!employeeId) {
                $('#previousSalary').val('');
                return;
            }
            $.getJSON(routes.salary, { employee_id: employeeId }).done(function (response) {
                $('#previousSalary').val(response.status === 'success' && response.salary ? response.salary : '0.00');
            }).fail(function () {
                $('#previousSalary').val('0.00');
            });
        });

        $('#adjustmentForm').on('submit', function (e) {
            e.preventDefault();
            const formData = {
                employee_id: $('#employeeSelect').val(),
                previous_salary: $('#previousSalary').val(),
                new_salary: $('#newSalary').val(),
                reason: $('#reasonSelect').val(),
                date_approved: $('#dateApproved').val(),
                approved_by: approvedBy,
            };
            if (!formData.employee_id || !formData.previous_salary || !formData.new_salary || !formData.reason || !formData.date_approved) {
                alert('Please fill in all required fields.');
                return;
            }
            $.ajax({
                url: routes.store,
                method: 'POST',
                data: formData,
                headers: { 'X-CSRF-TOKEN': csrf },
                dataType: 'json',
            }).done(function (response) {
                if (response.status === 'success') {
                    alert('Salary adjustment saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save salary adjustment.'));
                }
            }).fail(function (xhr) {
                let errorMsg = 'Failed to save salary adjustment.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || (response.errors ? Object.values(response.errors).flat().join(' ') : errorMsg);
                } catch (err) {}
                alert('Error: ' + errorMsg);
            });
        });
    });
})();
</script>
@endpush
