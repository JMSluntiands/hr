@extends('layouts.admin')
@section('title', 'Leave History')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Leave History</h1>
        <p class="text-sm text-slate-500 mt-1">All leave requests with approval history</p>
    </div>
    <a href="{{ route('admin.leave-requests.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-[#FA9800] rounded-lg hover:bg-[#e8870a]">Leave request (pending)</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="leaveHistoryTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Leave type</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Start</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Return</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Days</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved by</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($requests as $r)
                @php
                    $status = $r['status'];
                    $statusClass = match ($status) {
                        'Approved' => 'bg-emerald-100 text-emerald-700',
                        'Rejected' => 'bg-red-100 text-red-700',
                        'Cancelled' => 'bg-slate-200 text-slate-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <tr class="hover:bg-slate-50/80 leave-row"
                    data-id="{{ $r['id'] }}"
                    data-employee="{{ $r['employee_name'] }}"
                    data-type="{{ $r['leave_type'] }}"
                    data-start="{{ $r['start_display'] }}"
                    data-end="{{ $r['end_display'] }}"
                    data-days="{{ $r['days'] }}"
                    data-reason="{{ e($r['reason']) }}"
                    data-status="{{ $status }}"
                    data-approved="{{ $r['approver_label'] }}"
                    data-approved-at="{{ $r['approved_at'] }}"
                    data-created="{{ $r['created_at'] }}"
                    data-rejection="{{ e($r['rejection_reason']) }}"
                    data-cancellation="{{ e($r['cancellation_reason']) }}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $r['employee_name'] }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $r['employee_badge'] }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $r['leave_type'] }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r['start_display'] }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r['end_display'] }}</td>
                    <td class="px-4 py-3 font-medium">{{ $r['days'] }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $status }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $r['approver_label'] }}</td>
                    <td class="px-4 py-3">
                        @include('admin.leaves.partials.view-button', ['r' => $r])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
@push('modals')
@include('admin.leaves.partials.modals')
@endpush
@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('assets/js/admin-leaves.js') }}?v=2"></script>
<script>
$(function () {
    $('#leaveHistoryTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        language: { search: '', searchPlaceholder: 'Search leave history…', emptyTable: 'No leave records found.' },
    });
});
</script>
@endpush
