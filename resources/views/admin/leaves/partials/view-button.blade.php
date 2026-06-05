@props(['r', 'compact' => false])
<x-hr-btn type="button" variant="{{ $compact ? 'secondary' : 'view' }}" class="view-leave-btn"
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
    View
</x-hr-btn>
