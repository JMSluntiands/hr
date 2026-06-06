@extends('layouts.admin')
@section('title', 'Incident Submitted by Employee - Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Incident submitted by employee</h1>
        <p class="text-sm text-slate-500 mt-1">
            Employee submissions appear here with status <strong>Pending</strong>. After you approve them, they move to
            <a href="{{ $listUrl }}" class="text-[#FA9800] font-medium hover:underline">List of incident</a>.
        </p>
    </div>
    <a href="{{ $listUrl }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Go to List of incident</a>
</div>

@if (!$tableReady)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
    Run <code class="bg-white/80 px-1 rounded">database/setup_incident_reports_table.php</code> once, then refresh.
</div>
@endif

@if(session('incident_report_flash'))
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">{{ session('incident_report_flash') }}</div>
@endif

@if($tableReady)
<section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Filters</h2>
    <form method="get" action="{{ route('admin.incident-reports.submitted') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Review status</label>
            <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25 bg-white">
                @foreach($allowedStatuses as $status)
                    <option value="{{ $status }}"@selected($statusFilter === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Employee name (on form)</label>
            <input type="text" name="employee" value="{{ $employeeQ }}" placeholder="Search name..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Incident type</label>
            <select name="incident_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25 bg-white">
                <option value="">All types</option>
                @foreach($allowedTypes as $t)
                    <option value="{{ $t }}"@selected($typeFilter === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-1 flex flex-wrap items-end gap-2">
            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply</button>
            <a href="{{ route('admin.incident-reports.submitted') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
        </div>
    </form>
</section>

<section class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Results</h2>
        <span class="text-xs text-slate-500">{{ $reports->count() }} row(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">ID</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Incident</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Type</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee (form)</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Submitted by</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">File</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                @php
                    $status = (string) ($r->review_status_display ?? 'Pending');
                    $statusClass = $status === 'Approved'
                        ? 'bg-emerald-100 text-emerald-700'
                        : ($status === 'Declined' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-slate-600">{{ (int) $r->id }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ !empty($r->incident_date) ? \Carbon\Carbon::parse($r->incident_date)->format('M j, Y') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->incident_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->employee_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $r->submitter_display ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if(!empty($r->attachment_path))
                        <a href="{{ route('admin.incident-reports.attachment', $r->id) }}" target="_blank" rel="noopener" class="text-amber-700 hover:underline text-xs font-medium">View proof</a>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-hr-btn-link href="{{ $editUrlBase }}?id={{ (int) $r->id }}">Edit</x-hr-btn-link>
                            @if($status === 'Pending')
                            <form method="post" action="{{ route('admin.incident-reports.approve', $r->id) }}" class="inline" onsubmit="return confirm('Approve this incident?');">
                                @csrf
                                <x-hr-btn type="submit" variant="approve">Approve</x-hr-btn>
                            </form>
                            <form method="post" action="{{ route('admin.incident-reports.decline', $r->id) }}" class="inline" onsubmit="return confirm('Decline this incident?');">
                                @csrf
                                <x-hr-btn type="submit" variant="decline">Decline</x-hr-btn>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">No reports match your filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
