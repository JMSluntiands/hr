<div id="adjustmentsModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Salary Adjustment History</h3>
            <button type="button" id="closeAdjustmentsModal" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[75vh]">
            @if(empty($salaryAdjustments))
            <p class="text-sm text-slate-500">No salary adjustment history available.</p>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Previous Salary</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">New Salary</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reason</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved By</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Approved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($salaryAdjustments as $adjustment)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 text-slate-700">₱{{ number_format((float)($adjustment->previous_salary ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-slate-700 font-semibold">₱{{ number_format((float)($adjustment->new_salary ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $adjustment->reason ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $adjustment->approved_by ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ !empty($adjustment->date_approved) ? \Carbon\Carbon::parse($adjustment->date_approved)->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
