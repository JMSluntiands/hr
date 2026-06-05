@extends('layouts.admin')
@section('title', 'Edit Employee')
@section('content')
@php
    $resolvedEmergencyAddress = old('emergency_contact_address', $employee->emergency_contact_address ?? '');
    $resolvedPrimaryAddress = old('address', $employee->address ?? '');
    $isEmergencySameAsPrimary = old('emergency_same_as_primary') !== null
        ? old('emergency_same_as_primary') == '1'
        : (trim($resolvedEmergencyAddress) !== '' && trim($resolvedEmergencyAddress) === trim($resolvedPrimaryAddress));
@endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Edit Employee</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $employee->full_name }} · {{ $employee->employee_id }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <x-hr-btn-link href="{{ route('admin.staff.show', $employee->id) }}" variant="view">View Profile</x-hr-btn-link>
        <x-hr-btn-link href="{{ route('admin.staff.index') }}" variant="secondary">Back to List</x-hr-btn-link>
    </div>
</div>

@if(session('error'))
<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg whitespace-pre-line">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    @include('admin.staff.partials._edit-form')
</div>

<div id="submitLoadingOverlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="flex flex-col items-center gap-4 text-white">
        <svg class="w-12 h-12 animate-spin text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-sm font-medium">Saving changes...</p>
    </div>
</div>
@endsection
@push('scripts')
<script>
    window.hrStaffEdit = { hasExistingResignation: {{ $hasResignationOnFile ? 'true' : 'false' }} };
</script>
<script src="{{ asset('assets/js/admin-staff-add.js') }}"></script>
<script src="{{ asset('assets/js/admin-staff-edit.js') }}"></script>
@endpush
