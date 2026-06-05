@extends('layouts.inventory')
@section('title', 'Decommission Requests')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Equipment Decommissioning Requests</h1>
        <p class="text-sm text-slate-500">
            Review employee submissions; approve or decline with an optional note.
            <span class="text-slate-600">Submitted and resolved times are Philippine Standard Time (Asia/Manila).</span>
        </p>
    </div>
    @if ($tableReady)
    <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 self-start">
        Pending: {{ (int) $pendingCount }}
    </span>
    @endif
</div>

@if ($status === 'updated')
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
        Request updated.
    </div>
@elseif ($status === 'error')
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
        {{ request()->query('message') ?: 'Something went wrong.' }}
    </div>
@endif

@if (!$tableReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Inventory tables are not ready. Ensure the database is configured and refresh this page.
    </div>
@else
    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Pending and declined requests</h2>
            <p class="text-sm text-slate-500">Approve pending requests to remove the item from active inventory and allocation.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requester</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Equipment / Item ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Submitted</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reviewer / Resolved</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
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
                            <td class="px-4 py-3 align-top">
                                @if ($st === 'pending')
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Declined</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700 align-top">{{ $employeeLabel }}</td>
                            <td class="px-4 py-3 text-slate-700 align-top">
                                <div class="font-medium">{{ $row->equipment_name ?? '' }}</div>
                                <div class="text-xs text-slate-500">ID: {{ $row->item_code ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 align-top whitespace-nowrap">
                                {{ \App\Support\HrDateTime::formatDateTime($row->created_at ?? '') }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs align-top">
                                @if (trim((string) ($row->reviewed_by_name ?? '')) !== '')
                                    <div class="text-slate-700 font-medium">{{ $row->reviewed_by_name }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if (!empty($row->resolved_at))
                                    <div class="mt-1 text-slate-500">{{ \App\Support\HrDateTime::formatDateTime($row->resolved_at) }}</div>
                                @endif
                                @if (trim((string) ($row->resolution_remark ?? '')) !== '')
                                    <div class="mt-1 text-slate-600">{!! nl2br(e($row->resolution_remark)) !!}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs align-top max-w-xs">
                                <details class="cursor-pointer">
                                    <summary class="text-[#FA9800] font-medium">View form</summary>
                                    <div class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                        @include('inventory.partials.decommission-details', ['row' => $row])
                                    </div>
                                </details>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if ($isPending)
                                    <div class="flex flex-col gap-2 min-w-[200px]">
                                        <form method="POST" action="{{ route('inventory.decommission.update-status') }}" class="space-y-1">
                                            @csrf
                                            <input type="hidden" name="request_id" value="{{ (int) $row->id }}">
                                            <input type="hidden" name="new_status" value="approved">
                                            <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional note"></textarea>
                                            <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-600 text-white hover:opacity-90">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.decommission.update-status') }}" class="space-y-1">
                                            @csrf
                                            <input type="hidden" name="request_id" value="{{ (int) $row->id }}">
                                            <input type="hidden" name="new_status" value="declined">
                                            <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional reason"></textarea>
                                            <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-red-600 text-white hover:opacity-90">Decline</button>
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
                                No pending or declined decommission requests.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4 mt-8">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Approved decommissioning</h2>
            <p class="text-sm text-slate-500">Same fields as List Item. These assets are removed from the main inventory list and cannot be allocated again.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Brand / Mfr</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condition</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Remarks</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date arrived</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requester</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved at</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reviewer</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($approvedRows as $ar)
                        @php
                            $dispName = trim((string) ($ar->live_item_name ?? '')) !== '' ? $ar->live_item_name : ($ar->equipment_name ?? '');
                            $dispDesc = trim((string) ($ar->live_description ?? '')) !== '' ? $ar->live_description : ($ar->equipment_description ?? '');
                            $dispType = trim((string) ($ar->live_type ?? '')) !== '' ? $ar->live_type : ($ar->equipment_type ?? '');
                            $dispBrand = trim((string) ($ar->live_brand ?? '')) !== '' ? $ar->live_brand : ($ar->brand_manufacturer ?? '');
                            $dispCond = trim((string) ($ar->live_condition ?? '')) !== '' ? $ar->live_condition : 'Decommissioned';
                            $dispRem = trim((string) ($ar->live_remarks ?? '')) !== '' ? $ar->live_remarks : '—';
                            $reqLabel = ($ar->full_name ?? '') . ' (' . ($ar->employee_code ?? '') . ')';
                            $row = $ar;
                        @endphp
                        <tr class="bg-emerald-50/20">
                            <td class="px-4 py-3 text-slate-800 font-mono text-xs">{{ $ar->item_code ?? '' }}</td>
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ $dispName }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs max-w-[200px]">{!! nl2br(e($dispDesc !== '' ? $dispDesc : '—')) !!}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $dispType !== '' ? $dispType : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $dispBrand !== '' ? $dispBrand : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $dispCond }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs max-w-[160px]">{!! nl2br(e($dispRem)) !!}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">{{ \App\Support\HrDateTime::formatDate($ar->live_date_arrived ?? '') }}</td>
                            <td class="px-4 py-3 text-slate-700 text-xs">{{ $reqLabel }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">{{ \App\Support\HrDateTime::formatDateTime($ar->resolved_at ?? '') }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ trim((string) ($ar->reviewed_by_name ?? '')) !== '' ? $ar->reviewed_by_name : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs align-top max-w-xs">
                                <details class="cursor-pointer">
                                    <summary class="text-[#FA9800] font-medium">View form</summary>
                                    <div class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                        @include('inventory.partials.decommission-details', ['row' => $row])
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-500 text-sm">No approved decommissioning yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
