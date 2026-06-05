@php use App\Services\StaffProfileService; @endphp
<div class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Employee Documents</h3>
            <p class="text-sm text-slate-500 mt-1">Checklist and files uploaded by employee</p>
        </div>
        <a href="{{ url('/admin/staff-add-document.php?id='.$employeeId) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Add Document
        </a>
    </div>
    <div class="p-6 space-y-6">
        <div>
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Document Checklist</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-10 text-center px-3 py-2 font-semibold text-slate-500 uppercase tracking-wide">Done</th>
                            <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                            <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
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
                                $statusClass = 'bg-slate-200 text-slate-800';
                                $statusText = 'Removal pending (employee)';
                            } else {
                                $status = $doc->status ?? 'Pending';
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $statusText = $status === 'Approved' ? 'Approved' : ($status === 'Rejected' ? 'Rejected' : 'Pending validation');
                            }
                            $lastUpdated = '—';
                            if ($doc) {
                                $ts = $doc->updated_at ?? $doc->created_at ?? null;
                                if ($ts) {
                                    $lastUpdated = \Carbon\Carbon::parse($ts)->format('M d, Y');
                                }
                            }
                            $isApproved = $hasFile && ($doc->status ?? '') === 'Approved' && !$pendingRemoval;
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-center">
                                @if($isApproved)
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                @elseif($hasFile)
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-amber-300 text-amber-400" title="Uploaded but not approved yet">
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
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Uploaded Files</h4>
            @if(empty($documents))
            <div class="text-center py-8 text-slate-500">
                <p class="text-sm">No documents uploaded yet.</p>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($documents as $doc)
                @php
                    $fileUrl = !empty($doc->file_path) ? StaffProfileService::uploadUrl($doc->file_path) : null;
                    $fileOk = $fileUrl && StaffProfileService::uploadExists($doc->file_path);
                    $isImage = $fileOk && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $doc->file_path);
                    $isPdf = $fileOk && preg_match('/\.pdf$/i', $doc->file_path);
                    $status = $doc->status ?? 'Pending';
                    $gridDelPending = !empty($doc->deletion_requested_at ?? null);
                    if ($gridDelPending) {
                        $statusClass = 'bg-slate-200 text-slate-800';
                        $gridStatus = 'Removal pending';
                    } else {
                        $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        $gridStatus = $status;
                    }
                @endphp
                <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <h4 class="font-medium text-slate-800 text-sm">{{ $doc->document_type ?? 'Document' }}</h4>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $gridStatus }}</span>
                    </div>
                    @if($fileOk)
                        @if($isImage)
                        <div class="mb-3 rounded border border-slate-200 overflow-hidden">
                            <img src="{{ $fileUrl }}" alt="{{ $doc->document_type }}" class="w-full h-32 object-cover">
                        </div>
                        @elseif($isPdf)
                        <div class="mb-3 p-4 bg-red-50 rounded border border-red-200 text-center">
                            <span class="text-xs text-red-700 font-medium">PDF Document</span>
                        </div>
                        @else
                        <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center">
                            <span class="text-xs text-slate-700 font-medium">Document File</span>
                        </div>
                        @endif
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-[#d97706] hover:text-[#b45309] font-medium">View/Download</a>
                    @else
                    <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center">
                        <p class="text-xs text-slate-500">File not found</p>
                    </div>
                    @endif
                    @if(!empty($doc->created_at))
                    <p class="text-xs text-slate-400 mt-2">Uploaded: {{ \Carbon\Carbon::parse($doc->created_at)->format('M d, Y') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
