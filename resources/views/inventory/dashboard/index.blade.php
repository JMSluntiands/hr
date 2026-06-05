@extends('layouts.inventory')
@section('title', 'Inventory Dashboard')

@section('content')
<div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-semibold text-slate-800">Inventory Management</h1>
</div>

@if (!$tableReady)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    Inventory tables are not ready. Ensure the database is configured and refresh this page.
</div>
@else
@php $perm = $permCan ?? fn () => true; @endphp
@if($perm('inventory_card_dashboard'))
<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-2">Inventory Overview</h2>
    <p class="text-slate-600 text-sm mb-6">Total count per item category.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($cards as $card)
        <div class="rounded-xl bg-gradient-to-br {{ $card['bgClass'] }} p-4 text-white shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 rounded-full bg-white/20"></div>
            <div class="flex items-start justify-between mb-3">
                <div class="text-sm font-semibold">{{ $card['name'] }}</div>
                <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center text-sm">
                    {!! $card['iconSvg'] !!}
                </div>
            </div>
            <div class="text-3xl font-bold leading-none">{{ (int) $card['count'] }}</div>
            <div class="text-xs text-white/90 mt-1">Total Count</div>
        </div>
        @endforeach
    </div>
</section>
@endif

@if($perm('inventory_card_messages'))
<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Messages</h2>
            <p class="text-slate-600 text-sm">Employee appeals about wrong item allocation.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1">
                Unread: {{ (int) $appealUnreadCount }}
            </span>
            <a href="{{ $messagesUrl }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">
                Open Messages
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Appeal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($appeals as $appeal)
                @php
                    $isUnread = empty($appeal->admin_viewed_at);
                    $employeeLabel = ($appeal->full_name ?? 'Unknown') . ' (' . ($appeal->employee_code ?? 'N/A') . ')';
                    $itemLabel = ($appeal->item_id ?? '') . ' - ' . ($appeal->item_name ?? '');
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        @if($isUnread)
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">New</span>
                        @else
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Read</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $employeeLabel }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $itemLabel }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $appeal->employee_appeal }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">No employee appeals yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endif
@endsection
