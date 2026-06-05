<div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-800">Compensation Details</h3>
        <button type="button"
                class="js-comp-privacy-view inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-medium text-[#c2410c] bg-white hover:bg-orange-50 transition-colors"
                data-target="adminStaffCompBody"
                data-placeholder="adminStaffCompPlaceholder"
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
    <div id="adminStaffCompPlaceholder" class="px-6 py-8 text-center text-sm text-slate-500">
        <p>Compensation details are hidden.</p>
        <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show salary, allowances, and bank details.</p>
    </div>
    <div id="adminStaffCompBody" class="p-6 hidden">
        @if($compensation || $latestAdjustment)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if($compensation)
            <div>
                <p class="text-sm text-slate-500 mb-1">Basic Salary (Daily)</p>
                <p class="font-medium text-slate-800 text-lg">₱{{ number_format($compensation->basic_salary_daily ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500 mb-1">Employment Type</p>
                <p class="font-medium text-slate-800">{{ $compensation->employment_type ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500 mb-1">Effective Date</p>
                <p class="font-medium text-slate-800">{{ !empty($compensation->effective_date) ? \Carbon\Carbon::parse($compensation->effective_date)->format('M d, Y') : 'N/A' }}</p>
            </div>
            @endif
            @if($currentSalary && $dailyGross !== null)
            <div class="md:col-span-2 border-slate-200">
                <h4 class="text-md font-semibold text-slate-700 mb-4">Gross Income (Based on New Salary)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-slate-600 mb-1">Daily Gross Income</p>
                        <p class="text-slate-800 text-xl font-bold">₱{{ number_format($dailyGross, 2) }}</p>
                        <p class="text-xs text-slate-500 mt-1">Daily compensation basis</p>
                    </div>
                </div>
            </div>
            @endif
            @if($compensation)
            <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-200">
                <h4 class="text-md font-semibold text-slate-700 mb-3">Allowances</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach(['allowance_internet' => 'Internet', 'allowance_meal' => 'Meal', 'allowance_position' => 'Position/Representation', 'allowance_transportation' => 'Transportation'] as $key => $label)
                    <div>
                        <p class="text-xs text-slate-500 mb-1">{{ $label }}</p>
                        <p class="font-medium text-slate-800">₱{{ number_format($compensation->$key ?? 0, 2) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @else
        <p class="text-slate-500 text-sm">No compensation information available.</p>
        @endif

        <div class="mt-6 pt-6 border-t border-slate-200">
            <h4 class="text-md font-semibold text-slate-700 mb-4">Quick Actions</h4>
            <button type="button" id="viewAdjustmentsBtn" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                <span>View All Adjustments</span>
            </button>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-200">
            <h4 class="text-md font-semibold text-slate-700 mb-4">Bank Details</h4>
            @if($bankDetails)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-sm text-slate-500 mb-1">Bank Name</p><p class="font-medium text-slate-800">{{ $bankDetails->bank_name }}</p></div>
                <div><p class="text-sm text-slate-500 mb-1">Account Number</p><p class="font-medium text-slate-800">{{ $bankDetails->account_number }}</p></div>
                <div><p class="text-sm text-slate-500 mb-1">Account Name</p><p class="font-medium text-slate-800">{{ $bankDetails->account_name }}</p></div>
                <div><p class="text-sm text-slate-500 mb-1">Account Type</p><p class="font-medium text-slate-800">{{ $bankDetails->account_type }}</p></div>
                @if(!empty($bankDetails->branch))
                <div><p class="text-sm text-slate-500 mb-1">Branch</p><p class="font-medium text-slate-800">{{ $bankDetails->branch }}</p></div>
                @endif
            </div>
            @else
            <p class="text-slate-500 text-sm">No bank details added by employee yet.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/compensation-privacy.js') }}"></script>
@endpush
