@extends('layouts.admin')
@section('title', 'Request Upload')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Request Upload</h1>
    <p class="text-sm text-slate-500 mt-1">Staff uploads from My Profile (IDs, 201 file, etc.). Approve or reject each file here.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Pending uploads</h2>
    </div>
    <div class="p-6 overflow-x-auto">
        <table id="docUploadsTable" class="min-w-full text-sm w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document type</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Uploaded</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list as $u)
                @php
                    $can = app(\App\Services\AdminPermissionService::class)->canApprove($adminId, 'approve_document_upload', (int) $u->employee_id);
                @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-700">{{ $u->employee?->full_name ?? '—' }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $u->employee?->employee_id ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $u->document_type }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $u->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.document-uploads.file', $u->id) }}" target="_blank" rel="noopener" class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            @if($can)
                            <form method="POST" action="{{ route('admin.document-uploads.approve', $u->id) }}" class="inline">@csrf
                                <button type="submit" class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Approve">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            <button type="button" class="decline-upload-btn p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors" data-id="{{ $u->id }}" title="Decline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @else
                            <span class="text-xs text-slate-400 px-2" title="No department permission">No access</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-500">No pending document uploads found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
<div id="declineUploadModal" class="hidden fixed inset-0 z-[200] items-center justify-center p-4 bg-black/50" aria-modal="true" role="dialog">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800">Decline document upload</h2>
        </div>
        <form method="POST" id="declineUploadForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelDeclineUpload" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
@endpush
@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(function () {
    if ($('#docUploadsTable tbody tr').length && !$('#docUploadsTable tbody tr td[colspan]').length) {
        $('#docUploadsTable').DataTable({
            pageLength: 10,
            order: [[2, 'desc']],
            language: { search: '', searchPlaceholder: 'Search pending uploads…', emptyTable: 'No pending document uploads found.' },
        });
    }

    const modal = document.getElementById('declineUploadModal');
    const form = document.getElementById('declineUploadForm');
    const open = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const close = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

    $(document).on('click', '.decline-upload-btn', function () {
        const id = $(this).data('id');
        if (form && id) {
            form.action = @json(url('/admin/document-uploads')) + '/' + id + '/decline';
            form.querySelector('textarea')?.value = '';
            open();
        }
    });
    $('#cancelDeclineUpload').on('click', close);
    $('#declineUploadModal').on('click', function (e) { if (e.target === this) close(); });
});
</script>
@endpush
