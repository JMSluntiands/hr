@extends('layouts.admin')
@section('title', 'Add New Employee')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Add New Employee</h1>
        <p class="text-sm text-slate-500 mt-1">Create a new employee account in the system</p>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium text-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to List
    </a>
</div>

@if(session('error'))
<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg whitespace-pre-line">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    @include('admin.staff._form')
</div>
@endsection
@push('scripts')
<script src="{{ asset('assets/js/admin-staff-add.js') }}"></script>
@endpush
