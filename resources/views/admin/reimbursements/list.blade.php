@extends('layouts.admin')
@section('title', 'List of Reimbursement')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">List of Reimbursement</h1>
    <p class="text-sm text-slate-500 mt-1">Approved reimbursements after admin review</p>
</div>

<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('admin.reimbursements.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-[#FA9800] rounded-lg hover:bg-[#e8870a]">For review (pending)</a>
    <a href="{{ route('admin.reimbursements.report.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Report reimbursement</a>
</div>

<h2 class="text-lg font-semibold text-slate-700 mb-3">For Receipt Attachment</h2>
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto mb-8">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Expense type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Purchased date</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved by</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Reimbursement receipt</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($forAttachment as $row)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 text-slate-700">{{ $row->employee?->full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->expense_type }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->purchased_date?->format('M d, Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">₱{{ number_format((float) $row->amount, 2) }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->approved_by_name ?? 'Admin' }}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.reimbursements.attach-receipt', $row->id) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="admin_receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="block text-xs border border-slate-200 rounded-lg px-2 py-1.5 max-w-[14rem]">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#FA9800] text-white text-xs font-medium whitespace-nowrap hover:bg-[#e8870a]">Attach</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No approved reimbursements awaiting receipt attachment.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="text-lg font-semibold text-slate-700 mb-3">Completed Reimbursed</h2>
<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Expense type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Purchased date</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved by</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Reimbursed at</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Reimbursement receipt</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($completed as $row)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 text-slate-700">{{ $row->employee?->full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->expense_type }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->purchased_date?->format('M d, Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">₱{{ number_format((float) $row->amount, 2) }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->approved_by_name ?? 'Admin' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->reimbursed_at?->format('M d, Y g:i A') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ asset('uploads/'.$row->admin_receipt_path) }}" target="_blank" rel="noopener" class="text-[#FA9800] hover:underline font-medium">View attached</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No completed reimbursed records yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
