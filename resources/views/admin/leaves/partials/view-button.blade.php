@props(['r', 'compact' => false])
<button type="button"
    class="view-leave-btn {{ $compact ? 'p-2 text-slate-600 hover:text-[#FA9800] hover:bg-amber-50 rounded-lg' : 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-[#FA9800] hover:bg-amber-50 rounded-lg' }}"
    title="View details"
    data-employee="{{ $r['employee_name'] }}"
    data-type="{{ $r['leave_type'] }}"
    data-start="{{ $r['start_display'] }}"
    data-end="{{ $r['end_display'] }}"
    data-days="{{ $r['days'] }}"
    data-reason="{{ e($r['reason']) }}"
    data-status="{{ $r['status'] }}"
    data-approved="{{ $r['approver_label'] }}"
    data-approved-at="{{ $r['approved_at'] }}"
    data-created="{{ $r['created_at'] }}"
    data-rejection="{{ $r['rejection_reason'] }}"
    data-cancellation="{{ $r['cancellation_reason'] }}">
    <svg class="{{ $compact ? 'w-5 h-5' : 'w-4 h-4' }} pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    @unless($compact)<span class="pointer-events-none">View</span>@endunless
</button>
