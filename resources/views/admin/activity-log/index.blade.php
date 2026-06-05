@extends('layouts.admin')
@section('title', 'Activity Log')
@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-2">Activity Log</h1>
<p class="text-sm text-slate-500 mb-6">Philippine Standard Time (Asia/Manila).</p>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table id="activityTable" class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Date & Time</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">User</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Action</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Description</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($logs as $log)
            <tr>
                <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at }}</td>
                <td class="px-4 py-3">{{ $log->user_name }}</td>
                <td class="px-4 py-3">{{ $log->action }}</td>
                <td class="px-4 py-3">{{ $log->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
@push('head')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>$('#activityTable').DataTable({ order: [[0,'desc']], pageLength: 25 });</script>
@endpush
