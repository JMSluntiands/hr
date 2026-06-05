@php use App\Services\StaffProfileService; @endphp
<div class="bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-20 md:h-24"></div>
    <div class="px-6 md:px-8 pb-6 -mt-10 md:-mt-12 relative">
        <div class="flex flex-col md:flex-row md:items-end gap-6">
            <div class="flex items-center gap-4 md:gap-5">
                <div class="w-24 h-24 md:w-28 md:h-28 rounded-2xl overflow-hidden bg-white border-4 border-white shadow-lg flex items-center justify-center flex-shrink-0">
                    @php $photo = $employee->profile_picture && StaffProfileService::uploadExists($employee->profile_picture) ? $employee->profile_picture : null; @endphp
                    @if($photo)
                        <img src="{{ StaffProfileService::uploadUrl($photo) }}" alt="" class="w-full h-full object-cover">
                    @else
                        <span class="text-3xl md:text-4xl font-bold text-amber-600">{{ strtoupper(substr($employee->full_name ?? '?', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="text-left">
                    <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-1">{{ $employee->full_name }}</h2>
                    <p class="text-xs md:text-sm text-slate-500 font-mono mb-2">{{ $employee->employee_id }}</p>
                    <div class="flex flex-wrap items-center gap-2 text-xs md:text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium {{ ($employee->status ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $employee->status ?? 'Active' }}
                        </span>
                        @if($employee->position)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                            {{ $employee->position }}@if($employee->department)<span class="mx-1 text-slate-400">·</span>{{ $employee->department }}@endif
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="md:ml-auto">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Signature</p>
                <div class="w-44 h-16 border border-slate-200 rounded-lg bg-white flex items-center justify-center overflow-hidden shadow-sm">
                    @if($employee->signature && StaffProfileService::uploadExists($employee->signature))
                        <img src="{{ StaffProfileService::uploadUrl($employee->signature) }}" alt="Employee signature" class="max-w-full max-h-full object-contain">
                    @else
                        <span class="text-slate-400 text-xs">No signature</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
