<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    const itemHistoryMap = @json($itemHistoryMap);

    $(function () {
        if (!$('#itemsTable').length) return;

        const table = $('#itemsTable').DataTable({ pageLength: 10 });
        const itemNameColumnIndex = 1;
        const itemNameFilter = document.getElementById('itemNameFilter');
        const uniqueItemNames = [];

        table.column(itemNameColumnIndex).data().each(function (value) {
            const itemName = String(value).trim();
            if (itemName !== '' && !uniqueItemNames.includes(itemName)) {
                uniqueItemNames.push(itemName);
            }
        });
        uniqueItemNames.sort((a, b) => a.localeCompare(b));
        uniqueItemNames.forEach(function (itemName) {
            const option = document.createElement('option');
            option.value = itemName;
            option.textContent = itemName;
            itemNameFilter.appendChild(option);
        });
        itemNameFilter.addEventListener('change', function () {
            const selected = this.value;
            if (!selected) {
                table.column(itemNameColumnIndex).search('').draw();
                return;
            }
            table.column(itemNameColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selected) + '$', true, false).draw();
        });

        $('#itemsTable').on('click', '.editBtn', function () {
            const btn = this;
            const pathsRaw = btn.getAttribute('data-item-image-paths') || '[]';
            const updateUrl = @json(url('/inventory/items')).replace(/\/$/, '') + '/' + (btn.dataset.id || '');
            $('#editItemForm').attr('action', updateUrl);
            $('#editCurrentImagePaths').val(pathsRaw);
            $('#editItemName').val(btn.dataset.item_name || '');
            $('#editItemIdPreview').val(btn.dataset.item_id || '');
            $('#editDescription').val(btn.dataset.description || '');
            $('#editType').val(btn.dataset.type || '');
            $('#editItemCondition').val(btn.dataset.item_condition || '');
            $('#editRemarks').val(btn.dataset.remarks || '');
            $('#editDateArrived').val(btn.dataset.date_arrived || '');
            $('#editBrandManufacturer').val(btn.dataset.brand_manufacturer || '');
            $('#editItemImages').val('');
            let paths = [];
            try { paths = JSON.parse(pathsRaw); } catch (e) { paths = []; }
            if (!Array.isArray(paths)) paths = [];
            const noteEl = $('#editCurrentImageNote');
            noteEl.empty();
            if (paths.length) {
                noteEl.append(document.createTextNode('Current: '));
                const appBase = document.querySelector('meta[name="app-url"]')?.content || '';
                paths.forEach(function (path, i) {
                    if (i) noteEl.append(document.createTextNode(' '));
                    const href = path.startsWith('http') ? path : appBase.replace(/\/$/, '') + '/' + String(path).replace(/^\//, '');
                    noteEl.append($('<a>', { class: 'text-blue-600 hover:underline', target: '_blank', href: href, text: String(i + 1) }));
                });
            } else {
                noteEl.text('No current images.');
            }
            $('#itemEditModal').removeClass('hidden');
        });

        $('#itemsTable').on('click', '.viewBtn', function () {
            const btn = this;
            $('#viewItemId').text(btn.dataset.item_id || '');
            $('#viewItemName').text(btn.dataset.item_name || '');
            $('#viewDescription').text(btn.dataset.description || '');
            $('#viewType').text(btn.dataset.type || '');
            $('#viewCondition').text(btn.dataset.item_condition || '');
            $('#viewRemarks').text(btn.dataset.remarks || '');
            $('#viewDateArrived').text(btn.dataset.date_arrived || '');
            $('#viewBrandManufacturer').text(btn.dataset.brand_manufacturer || '');
            const pictureContainer = $('#viewPictureContainer');
            pictureContainer.empty();
            let vpaths = [];
            try { vpaths = JSON.parse(btn.getAttribute('data-item-image-paths') || '[]'); } catch (e) { vpaths = []; }
            if (!Array.isArray(vpaths)) vpaths = [];
            const appBase = document.querySelector('meta[name="app-url"]')?.content || '';
            if (vpaths.length) {
                vpaths.forEach(function (path, i) {
                    if (i) pictureContainer.append(document.createTextNode(' '));
                    const href = path.startsWith('http') ? path : appBase.replace(/\/$/, '') + '/' + String(path).replace(/^\//, '');
                    pictureContainer.append($('<a>', { class: 'text-blue-600 hover:underline', target: '_blank', href: href, text: 'Image ' + (i + 1) }));
                });
            } else {
                pictureContainer.text('No image');
            }
            $('#itemViewModal').removeClass('hidden');
        });

        $('#itemsTable').on('click', '.historyBtn', function () {
            const btn = this;
            const itemDbId = String(btn.dataset.id || '');
            const itemLabel = (btn.dataset.item_name || '') + ' (' + (btn.dataset.item_id || '') + ')';
            $('#historyModalTitle').text('Item History - ' + itemLabel);
            const historyRows = itemHistoryMap[itemDbId] || [];
            const tbody = $('#historyTableBody');
            tbody.empty();
            if (!historyRows.length) {
                $('#historyEmptyState').removeClass('hidden');
            } else {
                $('#historyEmptyState').addClass('hidden');
                historyRows.forEach(function (entry) {
                    const employeeLabel = entry.employee_name
                        ? (entry.employee_name + (entry.employee_code ? ' (' + entry.employee_code + ')' : ''))
                        : 'Unknown Employee';
                    const dateReturn = entry.date_return || '';
                    const remarks = entry.return_remarks || '';
                    const isReturned = Boolean(dateReturn);
                    const statusBadge = isReturned
                        ? '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Returned</span>'
                        : '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Currently Allocated</span>';
                    tbody.append(
                        '<tr class="border-b border-slate-100">' +
                            '<td class="px-3 py-2">' + $('<div>').text(employeeLabel).html() + '</td>' +
                            '<td class="px-3 py-2">' + $('<div>').text(entry.date_received || '').html() + '</td>' +
                            '<td class="px-3 py-2">' + $('<div>').text(dateReturn || '-').html() + '</td>' +
                            '<td class="px-3 py-2">' + $('<div>').text(remarks || '-').html() + '</td>' +
                            '<td class="px-3 py-2">' + statusBadge + '</td>' +
                        '</tr>'
                    );
                });
            }
            $('#itemHistoryModal').removeClass('hidden');
        });

        $('.closeModalBtn').on('click', function () {
            const target = this.dataset.target;
            if (target) $('#' + target).addClass('hidden');
        });
        $('.editModalCloseBtn').on('click', function () {
            $('#itemEditModal').addClass('hidden');
        });
        $('#itemViewModal, #itemHistoryModal, #itemEditModal').on('click', function (event) {
            if (event.target === this) $(this).addClass('hidden');
        });
    });
</script>
