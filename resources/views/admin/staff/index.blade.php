@extends('layouts.admin')
@section('title', 'Employees')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">List of Employees</h1>
        <p class="text-sm text-slate-500 mt-1">Manage employee records</p>
    </div>
    <a href="{{ route('admin.staff.create') }}" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:bg-[#d18a15]">Add Employee</a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
    <table id="staffTable" class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">ID</th>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Name</th>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Email</th>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Department</th>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Status</th>
                <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($employees as $emp)
            <tr>
                <td class="px-4 py-3">{{ $emp->employee_id }}</td>
                <td class="px-4 py-3 font-medium">{{ $emp->full_name }}</td>
                <td class="px-4 py-3">{{ $emp->email }}</td>
                <td class="px-4 py-3">{{ $emp->department }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ strtolower($emp->status) === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $emp->status }}</span>
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.staff.edit', $emp->id) }}" class="text-[#FA9800] hover:underline">Edit</a>
                    <a href="{{ route('admin.staff.show', $emp->id) }}" class="text-slate-600 hover:underline ml-2">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>$('#staffTable').DataTable({ pageLength: 25 });</script>
@endpush
