@extends('layouts.inventory')
@section('title', 'Allocation')

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #cbd5e1 !important; background: #ffffff !important;
        color: #334155 !important; border-radius: 8px !important;
        padding: 6px 12px !important; margin-left: 6px !important; font-size: 13px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        border-color: #94a3b8 !important; background: #f8fafc !important; color: #0f172a !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        border-color: #FA9800 !important; background: #FA9800 !important; color: #ffffff !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        opacity: 0.55; cursor: not-allowed !important;
        border-color: #cbd5e1 !important; background: #f1f5f9 !important; color: #64748b !important;
    }
    .select2-container .select2-selection--single {
        height: 42px; border: 1px solid rgb(203 213 225); border-radius: 0.5rem;
        padding: 0.35rem 0.25rem; font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container { width: 100% !important; }
</style>
@endpush

@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-6">Item Allocation</h1>

@if ($status === 'created')
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">Item allocation saved successfully.</div>
@elseif ($status === 'returned')
    <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">Item returned successfully. Allocation removed from employee.</div>
@elseif ($status === 'error')
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
        {{ $message !== '' ? $message : 'Something went wrong.' }}
    </div>
@endif

@if (!$tableReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Inventory tables are not ready. Ensure the database is configured and refresh this page.
    </div>
@else
    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Allocate Item to Employee</h2>
        <form method="POST" action="{{ route('inventory.allocation.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-sm text-slate-600 mb-1">Employee</label>
                <select name="employee_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ (int) $employee->id }}">
                            {{ $employee->full_name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Item</label>
                <select id="inventoryItemSelect" name="inventory_item_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select item</option>
                    @foreach ($availableItems as $item)
                        <option value="{{ (int) $item->id }}">
                            {{ $item->item_id }} - {{ $item->item_name }}{{ trim((string) ($item->description ?? '')) !== '' ? ' (' . $item->description . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Date Received</label>
                <input type="date" name="date_received" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                    Save Allocation
                </button>
            </div>
        </form>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Allocated Items</h2>
                <p class="text-sm text-slate-500">Choose an employee first. Table is empty by default.</p>
            </div>
            <div class="w-full md:w-[520px]">
                <label class="block text-sm text-slate-600 mb-1">Employee Filter</label>
                <select id="employeeFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select employee to view allocations</option>
                    @foreach ($employees as $employee)
                        <option value="{{ (int) $employee->id }}">
                            {{ $employee->full_name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="allocationTable" class="display stripe hover w-full text-sm">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Condition</th>
                        <th>Date Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <a id="exportPdfBtn" href="{{ route('inventory.allocation.export.pdf') }}" class="hidden inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition whitespace-nowrap">
                Export to PDF
            </a>
        </div>
    </section>
@endif
@endsection

@if ($tableReady)
@push('modals')
<div id="returnModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-5 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800">Return Item</h3>
            <p class="text-sm text-slate-500 mt-1">Set Date Return to remove this item from employee allocation.</p>
        </div>
        <form method="POST" action="{{ route('inventory.allocation.return') }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="allocation_id" id="returnAllocationId">
            <div>
                <label class="block text-sm text-slate-600 mb-1">Date Return</label>
                <input type="date" name="date_return" id="returnDate" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Remarks (Optional)</label>
                <textarea name="return_remarks" id="returnRemarks" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Ilagay kung may sira o issue sa item."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelReturnBtn" class="px-4 py-2 rounded-lg text-sm bg-slate-200 text-slate-700 hover:bg-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-[#FA9800] text-white hover:opacity-90">Confirm Return</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/js/manila-time.js') }}"></script>
<script>
    const allocationRows = @json($allocationRows);
    const selectedEmployeeFromQuery = {{ (int) $selectedEmployeeId }};
    const exportPdfBaseUrl = @json(route('inventory.allocation.export.pdf'));

    $(function () {
        $('#inventoryItemSelect').select2({
            placeholder: 'Select item',
            allowClear: true,
            width: '100%'
        });

        const table = $('#allocationTable').DataTable({
            pageLength: 10,
            language: {
                emptyTable: 'Select an employee to view allocated items.'
            }
        });

        function renderByEmployee(employeeId) {
            table.clear();
            const exportBtn = document.getElementById('exportPdfBtn');
            const shouldShowExport = Boolean(employeeId);
            exportBtn.classList.toggle('hidden', !shouldShowExport);
            exportBtn.href = employeeId
                ? exportPdfBaseUrl + '?employee_id=' + encodeURIComponent(employeeId)
                : exportPdfBaseUrl;

            if (!employeeId) {
                table.draw();
                return;
            }

            const filtered = allocationRows.filter(function (row) {
                return String(row.employee_id) === String(employeeId);
            });

            filtered.forEach(function (row) {
                const employeeLabel = row.full_name + ' (' + row.emp_code + ')';
                const returnBtnHtml =
                    '<button type="button" class="returnBtn px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500 text-white hover:bg-amber-600" data-allocation-id="' +
                    String(row.id) +
                    '">Return</button>';
                table.row.add([
                    employeeLabel,
                    row.item_id ?? '',
                    row.item_name ?? '',
                    row.description ?? '',
                    row.type ?? '',
                    row.item_condition ?? '',
                    row.date_received ?? '',
                    returnBtnHtml
                ]);
            });

            table.draw();
        }

        $('#employeeFilter').on('change', function () {
            renderByEmployee(this.value);
        });

        if (selectedEmployeeFromQuery > 0) {
            $('#employeeFilter').val(String(selectedEmployeeFromQuery));
            renderByEmployee(String(selectedEmployeeFromQuery));
        }

        const returnModal = document.getElementById('returnModal');
        const returnAllocationIdInput = document.getElementById('returnAllocationId');
        const returnDateInput = document.getElementById('returnDate');
        const returnRemarksInput = document.getElementById('returnRemarks');
        const cancelReturnBtn = document.getElementById('cancelReturnBtn');

        function closeReturnModal() {
            returnModal.classList.add('hidden');
            returnAllocationIdInput.value = '';
            returnDateInput.value = '';
            returnRemarksInput.value = '';
        }

        $('#allocationTable').on('click', '.returnBtn', function () {
            const allocationId = this.dataset.allocationId || '';
            returnAllocationIdInput.value = allocationId;
            returnDateInput.value = (window.HrManilaTime && HrManilaTime.getTodayYmd) ? HrManilaTime.getTodayYmd() : new Date().toISOString().slice(0, 10);
            returnModal.classList.remove('hidden');
        });

        cancelReturnBtn.addEventListener('click', closeReturnModal);
        returnModal.addEventListener('click', function (event) {
            if (event.target === returnModal) {
                closeReturnModal();
            }
        });
    });
</script>
@endpush
@endif
