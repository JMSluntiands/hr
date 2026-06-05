@extends('layouts.admin')
@section('title', 'Leave Request')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Leave Request</h1>
        <p class="text-sm text-slate-500 mt-1">Review and approve employee leave submissions</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-100 text-amber-800 text-sm font-medium">
            {{ $pendingCount }} pending
        </span>
        <a href="{{ route('admin.leaves.history.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Leave history</a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 overflow-x-auto">
        <table id="leaveRequestsTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Leave type</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Start</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Return</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Days</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Submitted</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($requests as $r)
                <tr class="hover:bg-slate-50/80 leave-row"
                    data-id="{{ $r['id'] }}"
                    data-employee="{{ $r['employee_name'] }}"
                    data-type="{{ $r['leave_type'] }}"
                    data-start="{{ $r['start_display'] }}"
                    data-end="{{ $r['end_display'] }}"
                    data-days="{{ $r['days'] }}"
                    data-reason="{{ e($r['reason']) }}"
                    data-status="{{ $r['status'] }}"
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
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $r['created_at'] }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @include('admin.leaves.partials.view-button', ['r' => $r, 'compact' => true])
                            @if($r['can_approve'])
                            <form method="POST" action="{{ route('admin.leave-requests.approve', $r['id']) }}" class="inline">@csrf
                                <x-hr-btn type="submit" variant="approve">Approve</x-hr-btn>
                            </form>
                            <x-hr-btn type="button" variant="decline" class="decline-leave-btn" data-id="{{ $r['id'] }}">Decline</x-hr-btn>
                            @else
                            <span class="text-xs text-slate-400">No access</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">No pending leave requests. New employee submissions will appear here.</td>
                </tr>
                @endforelse
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
<script>
    window.hrLeaveRoutes = {
        decline: @json(url('/admin/leave-requests')),
        declineSuffix: '/decline',
    };
</script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('assets/js/admin-leaves.js') }}?v=2"></script>
<script>
$(function () {
    if ($('#leaveRequestsTable tbody tr').length && !$('#leaveRequestsTable tbody tr td[colspan]').length) {
        $('#leaveRequestsTable').DataTable({
            pageLength: 25,
            order: [[5, 'desc']],
            language: { search: '', searchPlaceholder: 'Search pending requests…', emptyTable: 'No pending leave requests.' },
        });
    }
});
</script>
@endpush
