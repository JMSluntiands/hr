@extends('layouts.admin')
@section('title', 'Leave Summary')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Leave Summary per Employee</h1>
        <p class="text-sm text-slate-500 mt-1">Approved sick and vacation days used in {{ $year }} (from leave requests)</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.leave-requests.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-[#FA9800] rounded-lg hover:bg-[#e8870a]">Leave requests</a>
        <a href="{{ route('admin.leaves.history.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Leave history</a>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-3 mb-6">
    <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employees</p>
        <p class="text-2xl font-bold text-slate-800 mt-1">{{ count($summaries) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Year</p>
        <p class="text-2xl font-bold text-[#FA9800] mt-1">{{ $year }}</p>
    </div>
    <div class="bg-gradient-to-br from-amber-50 to-white rounded-xl border border-amber-100 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800/70">Tip</p>
        <p class="text-sm text-slate-600 mt-1">Used days count approved requests only.</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="summaryTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Department</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Sick leave</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Vacation leave</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($summaries as $summary)
                <tr class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $summary->full_name }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $summary->employee_id }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $summary->department ?: 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 font-semibold text-sm">{{ $summary->sl_used }} day{{ $summary->sl_used === 1 ? '' : 's' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 font-semibold text-sm">{{ $summary->vl_used }} day{{ $summary->vl_used === 1 ? '' : 's' }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-slate-500">No approved leave records for {{ $year }} yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
    if ($('#summaryTable tbody tr').length && !$('#summaryTable tbody tr td[colspan]').length) {
        $('#summaryTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            order: [[0, 'asc']],
            language: {
                search: '',
                searchPlaceholder: 'Search employees…',
                emptyTable: 'No leave summary found.',
            },
        });
    }
});
</script>
@endpush
