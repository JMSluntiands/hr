@extends('layouts.inventory')
@section('title', 'Inventory Requests')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Item Requests</h1>
        <p class="text-sm text-slate-500">Mga request ng employees para sa bagong inventory items.</p>
    </div>
    @if ($tableReady)
    <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 self-start">
        Pending: {{ (int) $pendingCount }}
    </span>
    @endif
</div>

@if ($status === 'updated')
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
        Request status updated.
    </div>
@endif

@if (!$tableReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Inventory tables are not ready. Ensure the database is configured and refresh this page.
    </div>
@else
    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requested</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Admin / Resolved</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $row)
                        @php
                            $st = (string) ($row->status ?? '');
                            $isPending = $st === 'pending';
                            $employeeLabel = ($row->full_name ?? '') . ' (' . ($row->employee_code ?? '') . ')';
                        @endphp
                        <tr class="{{ $isPending ? 'bg-amber-50/40' : '' }}">
                            <td class="px-4 py-3">
                                @if ($st === 'pending')
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                @elseif ($st === 'approved')
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $employeeLabel }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->item_name ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-700">{!! nl2br(e(trim((string) ($row->details ?? '')) !== '' ? $row->details : '—')) !!}</td>
                            <td class="px-4 py-3 text-slate-600">{{ \App\Support\HrDateTime::formatDateTime($row->created_at ?? '') }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">
                                @if (trim((string) ($row->admin_remark ?? '')) !== '')
                                    <div class="text-slate-700">{!! nl2br(e($row->admin_remark)) !!}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if (!empty($row->resolved_at))
                                    <div class="mt-1 text-slate-500">{{ \App\Support\HrDateTime::formatDateTime($row->resolved_at) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if ($isPending)
                                    <div class="flex flex-col gap-2 min-w-[200px]">
                                        <form method="POST" action="{{ route('inventory.requests.update-status') }}" class="space-y-1">
                                            @csrf
                                            <input type="hidden" name="request_id" value="{{ (int) $row->id }}">
                                            <input type="hidden" name="new_status" value="approved">
                                            <textarea name="admin_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional note sa employee"></textarea>
                                            <x-hr-btn type="submit" variant="approve" class="w-full">Approve</x-hr-btn>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.requests.update-status') }}" class="space-y-1">
                                            @csrf
                                            <input type="hidden" name="request_id" value="{{ (int) $row->id }}">
                                            <input type="hidden" name="new_status" value="rejected">
                                            <textarea name="admin_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Reason (optional)"></textarea>
                                            <x-hr-btn type="submit" variant="danger" class="w-full">Reject</x-hr-btn>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                No item requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
