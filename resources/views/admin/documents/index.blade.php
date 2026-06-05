@extends('layouts.admin')
@section('title', 'Request Document')
@section('content')
<h1 class="text-2xl font-semibold text-slate-800 mb-6">Request Document</h1>
<div class="bg-white rounded-xl border overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Document</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($list as $r)
            @php $can = $r->status === 'Pending' && app(\App\Services\AdminPermissionService::class)->canApprove((int)session('user_id'), 'approve_document_request', (int)$r->employee_id); @endphp
            <tr>
                <td class="px-4 py-3">{{ $r->employee?->full_name }}</td>
                <td class="px-4 py-3">{{ $r->document_type }}</td>
                <td class="px-4 py-3">{{ $r->status }}</td>
                <td class="px-4 py-3">
                    @if($can)
                    <form method="POST" action="{{ route('admin.documents.approve', $r->id) }}" class="inline">@csrf<button class="text-emerald-600 text-sm">Approve</button></form>
                    <form method="POST" action="{{ route('admin.documents.decline', $r->id) }}" class="inline ml-2">@csrf<button class="text-red-600 text-sm">Decline</button></form>
                    @endif
                    @if($r->status === 'Approved')
                    <a href="{{ url('/legacy/admin/request-document.php') }}" class="text-[#FA9800] text-sm ml-2">Issue (legacy)</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
