@extends('layouts.admin')
@section('title', 'Request Bank')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Request Bank</h1>
    <p class="text-sm text-slate-500 mt-1">Approve or reject employee bank account change requests (from Compensation Details)</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-100">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-700">Bank account change requests</h2>
        <p class="text-xs text-slate-500 mt-1">Employees request changes from My Compensation. Approve to update their bank details.</p>
    </div>
    <div class="p-6 overflow-x-auto">
        @if($list->isEmpty())
        <div class="text-center py-12 text-slate-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <p class="text-sm">No pending bank account change requests.</p>
        </div>
        @else
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Requested</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">New bank details</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($list as $r)
                @php
                    $can = app(\App\Services\AdminPermissionService::class)->canApprove($adminId, 'approve_bank_change', (int) $r->employee_id);
                @endphp
                <tr class="hover:bg-slate-50/80">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-700">{{ $r->employee?->full_name ?? '—' }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $r->employee?->employee_id ?? $r->employee_id }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->requested_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="space-y-0.5 text-slate-700">
                            <div><span class="text-slate-500">Bank:</span> {{ $r->bank_name ?? '—' }}</div>
                            <div><span class="text-slate-500">Account #:</span> {{ $r->account_number ?? '—' }}</div>
                            <div><span class="text-slate-500">Account name:</span> {{ $r->account_name ?? '—' }}</div>
                            <div>
                                <span class="text-slate-500">Type:</span> {{ $r->account_type ?? '—' }}
                                @if(!empty($r->branch)) · {{ $r->branch }} @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if($can)
                            <form method="POST" action="{{ route('admin.bank-requests.approve', $r->id) }}" class="inline">@csrf
                                <x-hr-btn type="submit" variant="approve">Approve</x-hr-btn>
                            </form>
                            <x-hr-btn type="button" variant="decline" class="decline-bank-btn" data-id="{{ $r->id }}">Decline</x-hr-btn>
                            @else
                            <span class="text-xs text-slate-400 px-2" title="No department permission">No access</span>
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
@endsection

@push('modals')
<div id="declineBankModal" class="hidden fixed inset-0 z-[200] items-center justify-center p-4 bg-black/50" aria-modal="true" role="dialog">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800">Decline bank account change</h2>
        </div>
        <form method="POST" id="declineBankForm" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelDeclineBank" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
$(function () {
    const modal = document.getElementById('declineBankModal');
    const form = document.getElementById('declineBankForm');
    const open = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const close = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };

    $(document).on('click', '.decline-bank-btn', function () {
        const id = $(this).data('id');
        if (form && id) {
            form.action = @json(url('/admin/bank-requests')) + '/' + id + '/decline';
            form.querySelector('textarea')?.value = '';
            open();
        }
    });
    $('#cancelDeclineBank').on('click', close);
    $('#declineBankModal').on('click', function (e) { if (e.target === this) close(); });
});
</script>
@endpush
