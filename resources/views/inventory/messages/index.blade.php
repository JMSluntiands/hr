@extends('layouts.inventory')
@section('title', 'Inventory Messages')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Messages</h1>
        <p class="text-sm text-slate-500">Employee appeals for wrong inventory allocation.</p>
    </div>
    @if ($tableReady)
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1">
            Unread: {{ (int) $unreadCount }}
        </span>
        <form method="POST" action="{{ route('inventory.messages.mark-all-read') }}">
            @csrf
            <x-hr-btn type="submit">Mark All as Read</x-hr-btn>
        </form>
    </div>
    @endif
</div>

@if ($status === 'updated')
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
        Message status updated.
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
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Appeal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Remarks</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Sent</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($messages as $row)
                        @php
                            $isUnread = empty($row->admin_viewed_at);
                            $employeeLabel = ($row->full_name ?? '') . ' (' . ($row->employee_code ?? '') . ')';
                            $itemLabel = ($row->item_id ?? '') . ' - ' . ($row->item_name ?? '');
                        @endphp
                        <tr class="{{ $isUnread ? 'bg-amber-50/40' : '' }}">
                            <td class="px-4 py-3">
                                @if ($isUnread)
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">New</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Read</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $employeeLabel }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $itemLabel }}</td>
                            <td class="px-4 py-3 text-slate-700">{!! nl2br(e($row->employee_appeal ?? '')) !!}</td>
                            <td class="px-4 py-3 text-slate-700">{{ trim((string) ($row->employee_appeal_remarks ?? '')) !== '' ? $row->employee_appeal_remarks : '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ \App\Support\HrDateTime::formatDateTime($row->employee_appeal_at ?? '') }}</td>
                            <td class="px-4 py-3">
                                @if ($isUnread)
                                    <form method="POST" action="{{ route('inventory.messages.mark-read') }}">
                                        @csrf
                                        <input type="hidden" name="allocation_id" value="{{ (int) $row->id }}">
                                        <x-hr-btn type="submit">Mark as Read</x-hr-btn>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                No employee appeals yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
