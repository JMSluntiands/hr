@extends('layouts.admin')
@section('title', 'View Employee')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">View Employee</h1>
            <p class="text-sm text-slate-500 mt-1">Full employee profile and records</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.staff.edit', $employeeId) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Employee
            </a>
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 font-medium text-sm border border-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Back to List
            </a>
        </div>
    </div>

    @if($staffDocumentAdded)
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-3 text-emerald-800 text-sm mb-4">
        <span class="font-medium">Document added successfully.</span>
    </div>
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
