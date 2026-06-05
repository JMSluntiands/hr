@extends('layouts.inventory')
@section('title', 'Inventory Activity Log')

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>
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
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Inventory Activity Log</h1>
</div>

@if ($tableMissing)
    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
        Activity log table is not available yet.
    </div>
@endif

<section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
    <div class="overflow-x-auto">
        <table id="inventoryActivityTable" class="display stripe hover w-full text-sm">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Change Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ \App\Support\HrDateTime::formatDateTime($log->created_at ?? '', 'M d, Y h:i:s A') }}</td>
                        <td>
                            {{ $log->user_name ?? '—' }}
                            @if (!empty($log->user_id))
                                <div class="text-xs text-slate-500">User ID: {{ (int) $log->user_id }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-mono text-xs text-slate-600">{{ $log->ip_address ?? '—' }}</td>
                        <td>{{ $log->action ?? '' }}</td>
                        <td>{{ $log->entity_type ?? '' }}</td>
                        <td>{{ $log->item_code ?? '—' }}</td>
                        <td>{{ $log->description ?? '' }}</td>
                        <td class="whitespace-pre-wrap text-slate-600">{{ $log->change_details ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function () {
        $('#inventoryActivityTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                emptyTable: 'No inventory activity logs found.',
                search: '',
                searchPlaceholder: 'Search logs...'
            }
        });
    });
</script>
@endpush
