<div id="viewLeaveModal" class="hidden fixed inset-0 z-[200] items-center justify-center p-4 bg-black/50" aria-modal="true" role="dialog">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto" role="document">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-slate-800">Leave request details</h2>
            <button type="button" id="closeViewLeaveModal" class="text-slate-400 hover:text-slate-600 text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100" aria-label="Close">&times;</button>
        </div>
        <div id="viewLeaveModalBody" class="p-6 space-y-4 text-sm"></div>
    </div>
</div>

<div id="declineLeaveModal" class="hidden fixed inset-0 z-[200] items-center justify-center p-4 bg-black/50" aria-modal="true" role="dialog">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800">Decline leave request</h2>
        </div>
        <form method="POST" id="declineLeaveForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelDeclineLeave" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
            </div>
        </form>
    </div>
</div>
