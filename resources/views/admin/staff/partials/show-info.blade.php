@php
    $fmtDate = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('M d, Y') : 'N/A';
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 md:p-8 bg-gradient-to-b from-slate-50 to-white border-b border-slate-100">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Personal Information</h3>
                <p class="text-sm text-slate-500">Contact details and work locations</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-white border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Email</p><p class="font-medium text-slate-800 truncate">{{ $employee->email ?? 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-white border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Phone</p><p class="font-medium text-slate-800">{{ $employee->phone ?? 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-white border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Birthdate</p><p class="font-medium text-slate-800">{{ $fmtDate($employee->birthdate ?? null) }}</p></div>
            <div class="p-4 rounded-xl bg-white border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Gender</p><p class="font-medium text-slate-800">{{ $employee->gender ?? 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-white border border-slate-100 sm:col-span-2"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Primary Workplace</p><p class="font-medium text-slate-800">{{ $employee->address ?: 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-white border border-slate-100 sm:col-span-2"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Secondary Workplace</p><p class="font-medium text-slate-800">{{ $employee->secondary_workplace ?: 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-100 sm:col-span-2 lg:col-span-3">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Emergency Contact</p>
                <p class="font-medium text-slate-800">{{ $employee->emergency_contact_name ?: 'N/A' }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $employee->emergency_contact_relationship }}@if($employee->emergency_contact_phone) · {{ $employee->emergency_contact_phone }}@endif</p>
                @if(trim((string)($employee->emergency_contact_address ?? '')) !== '')
                <p class="text-xs text-slate-500 mt-1">{{ $employee->emergency_contact_address }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="p-6 md:p-8 border-t border-slate-100">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <div><h3 class="text-lg font-semibold text-slate-800">Employment</h3><p class="text-sm text-slate-500">Role, department and status</p></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Position</p><p class="font-medium text-slate-800">{{ $employee->position ?? 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Department</p><p class="font-medium text-slate-800">{{ $employee->department ?? 'N/A' }}</p></div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Employment Type</p>
                <p class="font-medium text-slate-800">@if($employmentTypeName){{ $employmentTypeName }}@elseif(!empty($compensation->employment_type)){{ $compensation->employment_type }}@else N/A @endif</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Date Hired</p><p class="font-medium text-slate-800">{{ $fmtDate($employee->date_hired ?? null) }}</p></div>
            @if(($employee->status ?? '') === 'Inactive')
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Date inactive</p><p class="font-medium text-slate-800">{{ $fmtDate($employee->date_inactive ?? null) }}</p></div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Resignation letter</p>
                @if($employee->resignation_letter_path && \App\Services\StaffProfileService::uploadExists($employee->resignation_letter_path))
                <a href="{{ \App\Services\StaffProfileService::uploadUrl($employee->resignation_letter_path) }}" target="_blank" rel="noopener" class="font-medium text-amber-700 hover:underline">View file</a>
                @else
                <p class="font-medium text-slate-600">No file on record</p>
                @endif
            </div>
            @endif
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100"><p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Created At</p><p class="font-medium text-slate-800">@if($employee->created_at){{ \Carbon\Carbon::parse($employee->created_at)->format('M d, Y H:i') }}@else N/A @endif</p></div>
        </div>
    </div>
    <div class="p-6 md:p-8 border-t border-slate-100 bg-slate-50/40">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div><h3 class="text-lg font-semibold text-slate-800">Government IDs</h3><p class="text-sm text-slate-500">SSS, PhilHealth, Pag-IBIG, TIN & clearances</p></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(['sss' => 'SSS', 'philhealth' => 'PhilHealth', 'pagibig' => 'Pag-IBIG', 'tin' => 'TIN', 'nbi_clearance' => 'NBI Clearance', 'police_clearance' => 'Police Clearance'] as $field => $label)
            <div class="p-4 rounded-xl bg-white border border-slate-100">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">{{ $label }}</p>
                <p class="font-medium text-slate-800 {{ in_array($field, ['sss','philhealth','pagibig','tin']) ? 'font-mono text-sm' : 'text-sm' }}">{{ $employee->$field ?: '—' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
