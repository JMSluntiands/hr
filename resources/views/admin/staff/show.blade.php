@extends('layouts.admin')
@section('title', 'View Employee')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">View Employee</h1>
            <p class="text-sm text-slate-500 mt-1">Full employee profile and records</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-hr-btn-link href="{{ route('admin.staff.edit', $employeeId) }}">Edit Employee</x-hr-btn-link>
            <x-hr-btn-link href="{{ route('admin.staff.index') }}" variant="secondary">Back to List</x-hr-btn-link>
        </div>
    </div>

    @if($staffDocumentAdded)
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-3 text-emerald-800 text-sm mb-4">
        <span class="font-medium">Document added successfully.</span>
    </div>
    @endif
    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm mb-4">{{ session('error') }}</div>
    @endif

    @include('admin.staff.partials.show-hero')
    @include('admin.staff.partials.show-info')
    @include('admin.staff.partials.show-compensation')
    @include('admin.staff.partials.show-adjustments-modal')
    @include('admin.staff.partials.show-documents')
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/admin-staff-view.js') }}"></script>
@endpush
