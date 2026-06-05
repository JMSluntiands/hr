<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-4">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Item History Filters</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label for="historyItemNameFilter" class="block text-sm text-slate-600 mb-1">Select Item Name</label>
            <select id="historyItemNameFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Choose item name</option>
            </select>
        </div>
        <div>
            <label for="historyItemIdFilter" class="block text-sm text-slate-600 mb-1">Select Item ID</label>
            <select id="historyItemIdFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" disabled>
                <option value="">Choose item ID</option>
            </select>
        </div>
    </div>
    <p class="text-sm text-slate-500 mb-4">Pili muna ng Item Name at Item ID bago lumabas ang history.</p>
</section>

<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Item History</h2>
    <div class="overflow-x-auto">
        <table id="itemHistoryTable" class="display stripe hover w-full text-sm">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Employee</th>
                    <th>Date Received</th>
                    <th>Date Return</th>
                    <th>Return Remarks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($historyRows as $history)
                @php
                    $employeeLabel = trim((string) ($history->full_name ?? '')) !== ''
                        ? ($history->full_name . ' (' . ($history->employee_code ?? '') . ')')
                        : 'Unknown Employee';
                    $dateReturn = (string) ($history->date_return ?? '');
                    $isReturned = $dateReturn !== '';
                @endphp
                <tr>
                    <td>{{ $history->item_id }}</td>
                    <td>{{ $history->item_name }}</td>
                    <td>{{ $employeeLabel }}</td>
                    <td>{{ $history->date_received }}</td>
                    <td>{{ $dateReturn !== '' ? $dateReturn : '-' }}</td>
                    <td>{{ ($history->return_remarks ?? '') !== '' ? $history->return_remarks : '-' }}</td>
                    <td>
                        @if ($isReturned)
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Returned</span>
                        @else
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Currently Allocated</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function () {
        if (!$('#itemHistoryTable').length) return;

        const historyTable = $('#itemHistoryTable').DataTable({
            pageLength: 10,
            order: [[3, 'desc']],
            language: { emptyTable: 'Select Item Name and Item ID to view history.' }
        });

        const historyItemIdColumnIndex = 0;
        const historyItemNameColumnIndex = 1;
        const historyItemNameFilter = document.getElementById('historyItemNameFilter');
        const historyItemIdFilter = document.getElementById('historyItemIdFilter');
        const uniqueHistoryItemNames = [];
        const itemNameToItemIds = {};

        historyTable.rows().data().each(function (row) {
            const itemId = String(row[historyItemIdColumnIndex] || '').trim();
            const itemName = String(row[historyItemNameColumnIndex] || '').trim();
            if (itemName !== '' && !uniqueHistoryItemNames.includes(itemName)) {
                uniqueHistoryItemNames.push(itemName);
            }
            if (itemName !== '' && itemId !== '') {
                if (!itemNameToItemIds[itemName]) itemNameToItemIds[itemName] = [];
                if (!itemNameToItemIds[itemName].includes(itemId)) {
                    itemNameToItemIds[itemName].push(itemId);
                }
            }
        });

        uniqueHistoryItemNames.sort((a, b) => a.localeCompare(b));
        uniqueHistoryItemNames.forEach(function (itemName) {
            const option = document.createElement('option');
            option.value = itemName;
            option.textContent = itemName;
            historyItemNameFilter.appendChild(option);
        });

        function applyHistoryFilters() {
            const selectedName = historyItemNameFilter.value;
            const selectedId = historyItemIdFilter.value;
            if (!selectedName || !selectedId) {
                historyTable.column(historyItemNameColumnIndex).search('a^', true, false);
                historyTable.column(historyItemIdColumnIndex).search('a^', true, false);
                historyTable.draw();
                return;
            }
            historyTable.column(historyItemNameColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selectedName) + '$', true, false);
            historyTable.column(historyItemIdColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selectedId) + '$', true, false);
            historyTable.draw();
        }

        historyItemNameFilter.addEventListener('change', function () {
            const selectedName = this.value;
            historyItemIdFilter.innerHTML = '<option value="">Choose item ID</option>';
            historyItemIdFilter.value = '';
            if (!selectedName || !itemNameToItemIds[selectedName]) {
                historyItemIdFilter.disabled = true;
                applyHistoryFilters();
                return;
            }
            const ids = itemNameToItemIds[selectedName].slice().sort((a, b) => a.localeCompare(b));
            ids.forEach(function (itemId) {
                const option = document.createElement('option');
                option.value = itemId;
                option.textContent = itemId;
                historyItemIdFilter.appendChild(option);
            });
            historyItemIdFilter.disabled = false;
            applyHistoryFilters();
        });

        historyItemIdFilter.addEventListener('change', applyHistoryFilters);
        applyHistoryFilters();
    });
</script>
@endpush
