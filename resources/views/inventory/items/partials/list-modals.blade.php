<div id="itemViewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Item Details</h3>
            <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700" data-target="itemViewModal">X</button>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-500">Item ID:</span> <span id="viewItemId" class="text-slate-800 font-medium"></span></div>
            <div><span class="text-slate-500">Item Name:</span> <span id="viewItemName" class="text-slate-800 font-medium"></span></div>
            <div><span class="text-slate-500">Description:</span> <span id="viewDescription" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Type:</span> <span id="viewType" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Condition:</span> <span id="viewCondition" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Remarks:</span> <span id="viewRemarks" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Date Arrived:</span> <span id="viewDateArrived" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Brand / Manufacturer:</span> <span id="viewBrandManufacturer" class="text-slate-800"></span></div>
            <div><span class="text-slate-500">Pictures:</span> <span id="viewPictureContainer" class="text-slate-800"></span></div>
        </div>
    </div>
</div>

<div id="itemHistoryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="historyModalTitle" class="text-lg font-semibold text-slate-800">Item History</h3>
            <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700" data-target="itemHistoryModal">X</button>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-3 py-2 border-b border-slate-200">Employee</th>
                            <th class="text-left px-3 py-2 border-b border-slate-200">Date Received</th>
                            <th class="text-left px-3 py-2 border-b border-slate-200">Date Return</th>
                            <th class="text-left px-3 py-2 border-b border-slate-200">Return Remarks</th>
                            <th class="text-left px-3 py-2 border-b border-slate-200">Status</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody"></tbody>
                </table>
            </div>
            <p id="historyEmptyState" class="hidden mt-3 text-sm text-slate-500">No allocation history yet for this item.</p>
        </div>
    </div>
</div>

<div id="itemEditModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl my-8">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Edit Item</h3>
            <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700 text-xl leading-none" data-target="itemEditModal">×</button>
        </div>
        <form id="editItemForm" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="current_image_paths" id="editCurrentImagePaths" value="[]">

            <div>
                <label class="block text-sm text-slate-600 mb-1">Item Name</label>
                <select name="item_name" id="editItemName" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select item</option>
                    @foreach ($itemOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Item ID (Auto)</label>
                <input type="text" id="editItemIdPreview" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50" readonly>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Description</label>
                <input type="text" name="description" id="editDescription" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Type</label>
                <input type="text" name="type" id="editType" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Item Condition</label>
                <select name="item_condition" id="editItemCondition" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Select condition</option>
                    @foreach ($itemConditions as $condition)
                        <option value="{{ $condition }}">{{ $condition }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Remarks</label>
                <input type="text" name="remarks" id="editRemarks" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Date Arrived / Purchased</label>
                <input type="date" name="date_arrived" id="editDateArrived" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-slate-600 mb-1">Brand / Manufacturer</label>
                <input type="text" name="brand_manufacturer" id="editBrandManufacturer" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Dell, HP, Acer">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm text-slate-600 mb-1">Add more pictures</label>
                <input type="file" name="item_images[]" id="editItemImages" accept=".jpg,.jpeg,.png,.gif,.webp" multiple class="w-full max-w-lg border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                <p id="editCurrentImageNote" class="mt-1 text-xs text-slate-500">No current images.</p>
                <p class="mt-1 text-xs text-slate-500">New files are added to existing photos (max 20 total).</p>
            </div>
            <div class="md:col-span-2 flex gap-2 pt-2">
                <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">Update Item</button>
                <button type="button" class="editModalCloseBtn px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-300 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>
