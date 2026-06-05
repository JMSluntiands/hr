@extends('layouts.admin')
@section('title', 'Document Archive')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Document Archive</h1>
    <p class="text-sm text-slate-500 mt-1">Staff removal requests land here. Approve to move the file into archive and remove it from the employee profile; reject to restore normal access.</p>
</div>

@if(!$schemaOk)
<div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-sm">
    Database setup required. Run <code class="bg-amber-100 px-1 rounded">database/setup_document_deletion_archive.php</code> once (browser or CLI).
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-8">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Pending removal requests</h2>
    </div>
    <div class="p-6 overflow-x-auto">
        @if($pendingRemovals->isEmpty())
        <p class="text-sm text-slate-500">No pending requests.</p>
        @else
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left bg-slate-50">
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Requested</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($pendingRemovals as $p)
                @php
                    $can = app(\App\Services\AdminPermissionService::class)->canApprove($adminId, 'approve_document_removal', (int) $p->employee_id);
                @endphp
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-700">{{ $p->employee?->full_name ?? '—' }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $p->employee?->employee_id ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $p->document_type }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $p->deletion_requested_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($can)
                            <form method="POST" action="{{ route('admin.document-archive.approve', $p->id) }}" class="inline"
                                  onsubmit="return confirm('Approve removal? File will be kept in archive only.');">@csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.document-archive.reject', $p->id) }}" class="inline"
                                  onsubmit="return confirm('Reject request? Employee will keep the document.');">@csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-700 text-xs font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Reject
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-slate-400" title="No department permission">No access</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Archived files (after approved removal)</h2>
    </div>
    <div class="p-6 overflow-x-auto">
        @if($archivedList->isEmpty())
        <p class="text-sm text-slate-500">No archived documents yet.</p>
        @else
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left bg-slate-50">
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Archived</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">By</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">View</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($archivedList as $a)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3 font-medium text-slate-700">{{ $a->employee_full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $a->document_type ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ !empty($a->archived_at) ? \Carbon\Carbon::parse($a->archived_at)->format('M d, Y H:i') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $a->archived_by_name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.document-archive.file', $a->id) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#FA9800] hover:bg-[#e8870a] text-white text-xs font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7m0-7L10 14m-4-4H5a2 2 0 00-2 2v7a2 2 0 002 2h7a2 2 0 002-2v-1"/></svg>
                            Open
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
