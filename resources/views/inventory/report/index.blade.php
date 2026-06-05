@extends('layouts.inventory')
@section('title', 'Inventory Report')

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #cbd5e1 !important; background: #fff !important;
        color: #334155 !important; border-radius: 8px !important;
        padding: 6px 12px !important; margin-left: 6px !important; font-size: 13px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        border-color: #94a3b8 !important; background: #f8fafc !important; color: #0f172a !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        border-color: #FA9800 !important; background: #FA9800 !important; color: #fff !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        opacity: 0.55; cursor: not-allowed !important;
        border-color: #cbd5e1 !important; background: #f1f5f9 !important; color: #64748b !important;
    }
</style>
@endpush

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Inventory Report</h1>
        <p class="text-sm text-slate-500">Item name, item id, description, and allocation owner.</p>
    </div>
    @if ($tableReady)
    <div class="flex gap-2">
        <a href="{{ route('inventory.report.export.excel') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">Export to Excel</a>
        <a href="{{ route('inventory.report.export.pdf') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700">Export to PDF</a>
    </div>
    @endif
</div>

@if (!$tableReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Inventory tables are not ready. Ensure the database is configured and refresh this page.
    </div>
@else
    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <div>
                <label for="itemNameFilter" class="block text-sm text-slate-600 mb-1">Filter by Item Name</label>
                <select id="itemNameFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All item names</option>
                </select>
            </div>
            <div>
                <label for="allocatedToFilter" class="block text-sm text-slate-600 mb-1">Filter by Allocated To</label>
                <select id="allocatedToFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All allocation owners</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="reportTable" class="display stripe hover w-full text-sm">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Item ID</th>
                        <th>Description</th>
                        <th>Allocated To</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row->item_name ?? '' }}</td>
                            <td>{{ $row->item_id ?? '' }}</td>
                            <td>{{ $row->description ?? '' }}</td>
                            <td>{{ \App\Services\Inventory\InventoryReportService::allocatedToLabel($row) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection

@if ($tableReady)
@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function () {
        const table = $('#reportTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']]
        });

        const itemNameColumnIndex = 0;
        const allocatedToColumnIndex = 3;
        const itemNameFilter = document.getElementById('itemNameFilter');
        const allocatedToFilter = document.getElementById('allocatedToFilter');

        const uniqueItemNames = [];
        const uniqueAllocatedTo = [];

        table.column(itemNameColumnIndex).data().each(function (value) {
            const text = String(value).trim();
            if (text !== '' && !uniqueItemNames.includes(text)) {
                uniqueItemNames.push(text);
            }
        });

        table.column(allocatedToColumnIndex).data().each(function (value) {
            const text = String(value).trim();
            if (text !== '' && !uniqueAllocatedTo.includes(text)) {
                uniqueAllocatedTo.push(text);
            }
        });

        uniqueItemNames.sort((a, b) => a.localeCompare(b));
        uniqueAllocatedTo.sort((a, b) => a.localeCompare(b));

        uniqueItemNames.forEach(function (itemName) {
            const option = document.createElement('option');
            option.value = itemName;
            option.textContent = itemName;
            itemNameFilter.appendChild(option);
        });

        uniqueAllocatedTo.forEach(function (allocatedTo) {
            const option = document.createElement('option');
            option.value = allocatedTo;
            option.textContent = allocatedTo;
            allocatedToFilter.appendChild(option);
        });

        function applyFilters() {
            const selectedItemName = itemNameFilter.value;
            const selectedAllocatedTo = allocatedToFilter.value;

            table.column(itemNameColumnIndex).search(
                selectedItemName ? '^' + $.fn.dataTable.util.escapeRegex(selectedItemName) + '$' : '',
                Boolean(selectedItemName),
                false
            );
            table.column(allocatedToColumnIndex).search(
                selectedAllocatedTo ? '^' + $.fn.dataTable.util.escapeRegex(selectedAllocatedTo) + '$' : '',
                Boolean(selectedAllocatedTo),
                false
            );
            table.draw();
        }

        itemNameFilter.addEventListener('change', applyFilters);
        allocatedToFilter.addEventListener('change', applyFilters);
    });
</script>
@endpush
@endif
