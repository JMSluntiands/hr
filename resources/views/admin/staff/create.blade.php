@extends('layouts.admin')
@section('title', 'Add New Employee')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Add New Employee</h1>
        <p class="text-sm text-slate-500 mt-1">Create a new employee account in the system</p>
    </div>
    <x-hr-btn-link href="{{ route('admin.staff.index') }}" variant="secondary">Back to List</x-hr-btn-link>
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
