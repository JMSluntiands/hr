<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Schema;

class LeaveRequestPresenter
{
    public function __construct(
        private readonly LeaveService $leaveService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toRow(LeaveRequest $leave): array
    {
        $leave->loadMissing('employee');

        return [
            'model' => $leave,
            'id' => $leave->id,
            'employee_name' => $leave->employee?->full_name ?? '—',
            'employee_badge' => $leave->employee?->employee_id ?? '—',
            'leave_type' => $leave->leave_type ?? '—',
            'start_date' => $leave->start_date?->format('Y-m-d') ?? '',
            'start_display' => $leave->start_date?->format('M d, Y') ?? '—',
            'end_date' => $leave->end_date?->format('Y-m-d') ?? '',
            'end_display' => $leave->end_date?->format('M d, Y') ?? '—',
            'days' => $this->leaveService->calculatedDays($leave),
            'reason' => $leave->reason ?? '',
            'status' => $leave->status ?? 'Pending',
            'approver_label' => $this->approverLabel($leave),
            'approved_at' => $leave->approved_at?->format('M d, Y g:i A') ?? '',
            'created_at' => $leave->created_at?->format('M d, Y g:i A') ?? '—',
            'rejection_reason' => $leave->rejection_reason ?? '',
            'cancellation_reason' => Schema::hasColumn('leave_requests', 'cancellation_reason')
                ? (string) ($leave->cancellation_reason ?? '')
                : '',
            'can_approve' => false,
        ];
    }

    public function approverLabel(LeaveRequest $leave): string
    {
        if (($leave->status ?? '') !== 'Approved' && empty($leave->approved_by)) {
            return '—';
        }

        if (Schema::hasColumn('leave_requests', 'approved_by_name') && ! empty($leave->approved_by_name)) {
            return (string) $leave->approved_by_name;
        }

        if (! empty($leave->approved_by)) {
            return 'User #'.(int) $leave->approved_by;
        }

        return '—';
    }
}
