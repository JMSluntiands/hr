@extends('layouts.admin')
@section('title', 'Reimbursement Report')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Report for Reimbursement</h1>
    <p class="text-sm text-slate-500 mt-1">Filter by reimbursed date, then view totals with attached admin receipt proof</p>
</div>

<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('admin.reimbursements.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">For review</a>
    <a href="{{ route('admin.reimbursements.list.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">List of reimbursement</a>
</div>

<form method="GET" action="{{ route('admin.reimbursements.report.index') }}" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">From (reimbursed date)</label>
        <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">To (reimbursed date)</label>
        <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
    </div>
    <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white text-sm font-medium rounded-lg hover:bg-[#e8870a]">Filter</button>
</form>

<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6">
    <p class="text-sm text-slate-500">Total reimbursement</p>
    <p class="text-3xl font-semibold text-emerald-600">₱{{ number_format($total, 2) }}</p>
    @if(!$filtered)
    <p class="text-xs text-slate-400 mt-2">Select a date range and click Filter to generate the report.</p>
    @endif
</section>

<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Reimbursed date</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Expense type</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Amount</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Proof receipt</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
            <tr class="hover:bg-slate-50/80">
                <td class="px-4 py-3 text-slate-700">{{ $row->reimbursed_at?->format('M d, Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->employee?->full_name ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $row->expense_type }}</td>
                <td class="px-4 py-3 text-slate-700">₱{{ number_format((float) $row->amount, 2) }}</td>
                <td class="px-4 py-3">
                    <a href="{{ asset('uploads/'.$row->admin_receipt_path) }}" target="_blank" rel="noopener" class="text-[#FA9800] hover:underline font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                    {{ $filtered ? 'No reimbursed records for the selected date range.' : 'No data yet — use the date filter above.' }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
