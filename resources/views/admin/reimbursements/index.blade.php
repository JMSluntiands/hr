@extends('layouts.admin')
@section('title', 'Reimbursement Review')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">For Review of Reimbursement</h1>
        <p class="text-sm text-slate-500 mt-1">Pending reimbursement requests from employees</p>
    </div>
    <a href="{{ route('admin.reimbursements.list.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">List of reimbursement</a>
    <a href="{{ route('admin.reimbursements.report.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Report reimbursement</a>
</div>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Receipt</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($list as $r)
            @php $can = app(\App\Services\AdminPermissionService::class)->canApprove((int)session('user_id'), 'approve_reimbursement', (int)$r->employee_id); @endphp
            <tr>
                <td class="px-4 py-3">
                    <div class="font-medium">{{ $r->employee?->full_name }}</div>
                    <div class="text-xs text-slate-500">{{ $r->employee?->employee_id }}</div>
                </td>
                <td class="px-4 py-3">{{ $r->expense_type }}</td>
                <td class="px-4 py-3">{{ $r->purchased_date?->format('M d, Y') }}</td>
                <td class="px-4 py-3">₱{{ number_format((float)$r->amount, 2) }}</td>
                <td class="px-4 py-3">
                    @if($r->receipt_path)
                    <a href="{{ asset('uploads/'.$r->receipt_path) }}" target="_blank" class="text-[#FA9800] hover:underline">View</a>
                    @else — @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($can)
                        <form method="POST" action="{{ route('admin.reimbursements.approve', $r->id) }}" class="inline">@csrf
                            <x-hr-btn type="submit" variant="approve">Approve</x-hr-btn>
                        </form>
                        <x-hr-btn type="button" variant="decline" onclick="openReimDecline({{ $r->id }})">Decline</x-hr-btn>
                        @elseif($r->status === 'Pending')
                        <span class="text-xs text-slate-400">No access</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No pending reimbursement requests.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div id="reimDeclineModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <form method="POST" id="reimDeclineForm" class="bg-white rounded-xl p-6 max-w-md w-full space-y-4">@csrf
        <textarea name="rejection_reason" required rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Reason"></textarea>
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Decline</button>
    </form>
</div>
@endsection
@push('scripts')
<script>
function openReimDecline(id) {
    document.getElementById('reimDeclineForm').action = '{{ url('/admin/reimbursements') }}/' + id + '/decline';
    document.getElementById('reimDeclineModal').classList.remove('hidden');
}
</script>
@endpush
