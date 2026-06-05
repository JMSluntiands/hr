@extends('layouts.inventory')
@section('title', 'Items')

@php
    $tabClass = fn (string $tab) => $activeTab === $tab
        ? 'bg-[#FA9800] text-white'
        : 'text-slate-600 hover:bg-slate-100';
    $needsDataTables = in_array($activeTab, ['list', 'history'], true);
@endphp

@if ($needsDataTables)
@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@if ($activeTab === 'list')
<style>
    .action-cell { min-width: 250px; }
    .action-buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .action-btn {
        width: 38px; height: 38px; border: 0; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; text-decoration: none; cursor: pointer;
        transition: opacity .2s ease, transform .2s ease;
    }
    .action-btn:hover { opacity: .92; transform: translateY(-1px); }
    .action-btn-blue { background: #2563eb; }
    .action-btn-red { background: #dc2626; }
    .action-btn-emerald { background: #059669; }
    .action-btn-violet { background: #7c3aed; }
    .print-label-cell { min-width: 160px; }
    .print-label-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .print-label-link {
        display: inline-block; padding: 5px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;
    }
    .print-label-qr { background: #dbeafe; color: #1d4ed8; }
    .print-label-barcode { background: #dcfce7; color: #15803d; }
    .picture-link { background: #ede9fe; color: #6d28d9; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #cbd5e1 !important; background: #fff !important;
        color: #334155 !important; border-radius: 8px !important;
        padding: 6px 12px !important; margin-left: 6px !important; font-size: 13px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        border-color: #94a3b8 !important; background: #f8fafc !important; color: #0f172a !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        border-color: #FA9800 !important; background: #FA9800 !important; color: #fff !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        opacity: 0.55; cursor: not-allowed !important;
        border-color: #cbd5e1 !important; background: #f1f5f9 !important; color: #64748b !important;
    }
</style>
@endif
@endpush
@endif

@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-6">Item Management</h1>

<section class="mb-6">
    <div class="inline-flex rounded-lg bg-white border border-slate-200 p-1 shadow-sm">
        <a href="{{ route('inventory.items.index', ['tab' => 'add']) }}" class="px-4 py-2 text-sm rounded-md font-medium transition {{ $tabClass('add') }}">Add Item</a>
        <a href="{{ route('inventory.items.index', ['tab' => 'list']) }}" class="px-4 py-2 text-sm rounded-md font-medium transition {{ $tabClass('list') }}">List Item</a>
        <a href="{{ route('inventory.items.index', ['tab' => 'history']) }}" class="px-4 py-2 text-sm rounded-md font-medium transition {{ $tabClass('history') }}">Item History</a>
    </div>
</section>

@if ($status === 'created')
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">Item created successfully.</div>
@elseif ($status === 'updated')
    <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">The item has been updated successfully.</div>
@elseif ($status === 'deleted')
    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">Item deleted successfully.</div>
@elseif ($status === 'error')
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
        {{ $message !== '' ? $message : 'Something went wrong.' }}
    </div>
@endif

@if (!$tableReady)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        Inventory tables are not ready. Ensure the database is configured and refresh this page.
    </div>
@else
    @if ($activeTab === 'add')
        @include('inventory.items.partials.add')
    @elseif ($activeTab === 'list')
        @include('inventory.items.partials.list')
    @elseif ($activeTab === 'history')
        @include('inventory.items.partials.history')
    @endif
@endif
@endsection
