@php
    $isEdit = !empty($editItem);
    $editPathsJson = htmlspecialchars(json_encode($editItemImagePaths ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
@endphp
<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Add / Edit Item</h2>
    <form id="itemForm" method="POST" action="{{ route('inventory.items.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <input type="hidden" name="id" id="rowId" value="{{ $isEdit ? (int) $editItem->id : '' }}">
        <input type="hidden" name="current_image_paths" id="currentImagePaths" value="{{ $editPathsJson }}">

        <div>
            <label class="block text-sm text-slate-600 mb-1">Item Name</label>
            <select name="item_name" id="itemName" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                <option value="">Select item</option>
                @foreach ($itemOptions as $option)
                    <option value="{{ $option }}" @selected($isEdit && ($editItem->item_name ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Item ID (Auto)</label>
            <input type="text" id="itemIdPreview" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50" readonly
                placeholder="Auto-generated after save" value="{{ $isEdit ? ($editItem->item_id ?? '') : '' }}">
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Description</label>
            <input type="text" name="description" id="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                value="{{ $isEdit ? ($editItem->description ?? '') : '' }}">
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Type</label>
            <input type="text" name="type" id="type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                value="{{ $isEdit ? ($editItem->type ?? '') : '' }}">
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Item Condition</label>
            <select name="item_condition" id="itemCondition" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                <option value="">Select condition</option>
                @foreach ($itemConditions as $condition)
                    <option value="{{ $condition }}" @selected($isEdit && (($editItem->item_condition ?? '') === $condition))>{{ $condition }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Remarks</label>
            <input type="text" name="remarks" id="remarks" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                value="{{ $isEdit ? ($editItem->remarks ?? '') : '' }}">
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Date Arrived / Purchased</label>
            <input type="date" name="date_arrived" id="dateArrived" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                value="{{ $isEdit ? ($editItem->date_arrived ?? '') : '' }}">
        </div>

        <div>
            <label class="block text-sm text-slate-600 mb-1">Brand / Manufacturer</label>
            <input type="text" name="brand_manufacturer" id="brandManufacturer" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                value="{{ $isEdit ? ($editItem->brand_manufacturer ?? '') : '' }}" placeholder="e.g. Dell, HP, Acer">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm text-slate-600 mb-1">Item pictures</label>
            <input type="file" name="item_images[]" id="itemImages" accept=".jpg,.jpeg,.png,.gif,.webp" multiple
                class="w-full max-w-lg border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
            <p class="mt-1 text-xs text-slate-500">JPG, PNG, GIF, or WEBP, up to 10MB each. Maximum 20 images per item.</p>
            <p id="currentImageNote" class="mt-2 text-xs text-slate-600">
                @if (!empty($editItemImagePaths))
                    <span class="text-slate-500">Current:</span>
                    @foreach ($editItemImagePaths as $idx => $p)
                        <a class="text-blue-600 hover:underline ml-1" target="_blank" href="{{ asset($p) }}">{{ $idx + 1 }}</a>
                    @endforeach
                    <span class="text-slate-400">(new uploads are added to these)</span>
                @else
                    <span class="text-slate-500">No pictures on file yet.</span>
                @endif
            </p>
        </div>

        <div class="md:col-span-2 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                {{ $isEdit ? 'Update Item' : 'Save Item' }}
            </button>
            <button type="button" id="resetBtn" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-300 transition">Reset</button>
        </div>
    </form>
</section>

@push('scripts')
<script>
    const prefixes = @json($itemPrefixes);
    function setItemIdPreview() {
        const itemName = document.getElementById('itemName')?.value;
        const preview = document.getElementById('itemIdPreview');
        if (!preview) return;
        if (!itemName || !prefixes[itemName]) {
            if (!document.getElementById('rowId')?.value) preview.value = '';
            return;
        }
        if (!document.getElementById('rowId')?.value) {
            preview.value = prefixes[itemName] + 'AUTO';
        }
    }
    function resetForm() {
        const form = document.getElementById('itemForm');
        if (!form) return;
        form.reset();
        document.getElementById('rowId').value = '';
        document.getElementById('itemIdPreview').value = '';
        const pathsHidden = document.getElementById('currentImagePaths');
        if (pathsHidden) pathsHidden.value = '[]';
        const note = document.getElementById('currentImageNote');
        if (note) note.innerHTML = '<span class="text-slate-500">No pictures on file yet.</span>';
    }
    document.getElementById('itemName')?.addEventListener('change', setItemIdPreview);
    document.getElementById('resetBtn')?.addEventListener('click', resetForm);
</script>
@endpush
