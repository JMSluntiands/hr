@php use App\Services\StaffProfileService; @endphp
<div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/50">
    <div class="p-6 md:p-8 border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-amber-50/30">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Upload Documents</h3>
                <p class="text-sm text-slate-500">HRIS 201 documents — admin will validate each upload.</p>
            </div>
        </div>
    </div>
    <div class="p-6 md:p-8 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="w-10 text-center px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Done</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($documentTypes as $docType)
                @php
                    $doc = $documentsByType[$docType] ?? null;
                    $hasFile = $doc && !empty($doc->file_path);
                    $pendingRemoval = $hasFile && !empty($doc->deletion_requested_at ?? null);
                    if (!$hasFile) {
                        $statusClass = 'bg-slate-100 text-slate-600';
                        $statusText = 'No File';
                    } elseif ($pendingRemoval) {
                        $statusClass = 'bg-slate-200 text-slate-700';
                        $statusText = 'Pending HR removal';
                    } else {
                        $status = $doc->status ?? 'Pending';
                        $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        $statusText = $status === 'Approved' ? 'Verified' : ($status === 'Rejected' ? 'Rejected' : 'Pending validation');
                    }
                    $lastUpdated = '—';
                    if ($doc) {
                        $ts = $doc->updated_at ?? $doc->created_at ?? null;
                        if ($ts) {
                            $lastUpdated = \Carbon\Carbon::parse($ts)->format('M d, Y');
                        }
                    }
                    $isVerifiedActive = $hasFile && ($doc->status ?? '') === 'Approved' && !$pendingRemoval;
                @endphp
                <tr>
                    <td class="px-3 py-2 text-center">
                        @if($isVerifiedActive)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        @elseif($hasFile)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-amber-300 text-amber-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01" /></svg>
                        </span>
                        @else
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-slate-300 text-slate-300">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" stroke-width="2" /></svg>
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-slate-700">{{ $docType }}</td>
                    <td class="px-4 py-2"><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td class="px-4 py-2 text-slate-500 text-xs">{{ $lastUpdated }}</td>
                    <td class="px-4 py-2">
                        <div class="flex items-center flex-wrap gap-2">
                            <button type="button" class="upload-doc-btn px-3 py-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-medium transition-colors" data-doc-type="{{ $docType }}">Upload</button>
                            @if($hasFile && !$pendingRemoval)
                            <a href="{{ route('employee.documents.view', $doc->id) }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium">View</a>
                            <a href="{{ route('employee.documents.download', $doc->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-medium">Download</a>
                            @elseif($pendingRemoval)
                            <span class="text-xs text-slate-500">On hold until HR approves removal</span>
                            @else
                            <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs cursor-not-allowed">View</span>
                            @endif
                            @if($isVerifiedActive)
                            <button type="button" class="request-doc-removal-btn px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium" data-doc-id="{{ $doc->id }}" data-doc-name="{{ $docType }}">Request removal</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="uploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-slate-800">Upload Document</h2>
            <button type="button" id="closeUploadModal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
        </div>
        <form id="uploadForm" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="document_type" id="uploadDocType" value="">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Document Type</label>
                <input type="text" id="uploadDocTypeDisplay" readonly class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Select File <span class="text-red-500">*</span></label>
                <input type="file" name="document_file" id="documentFile" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-2 border border-slate-200 rounded-lg">
                <p class="text-xs text-slate-500 mt-1">Allowed: PDF, JPG, PNG (Max 5MB)</p>
            </div>
            <div id="uploadMessage" class="hidden"></div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelUpload" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309]">Upload</button>
            </div>
        </form>
    </div>
</div>

<div id="removalModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-lg font-semibold text-slate-800">Request document removal</h2>
        <p class="text-sm text-slate-600">HR approval is required before this document can be deleted.</p>
        <p id="removalDocName" class="text-sm font-medium text-slate-800">—</p>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" id="cancelRemovalBtn" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">Cancel</button>
            <button type="button" id="confirmRemovalBtn" class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700">Yes, request removal</button>
        </div>
    </div>
</div>
