<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function approve(LeaveRequest $leave, int $adminId, string $adminName): void
    {
        DB::transaction(function () use ($leave, $adminId, $adminName) {
            $data = [
                'status' => 'Approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'rejection_reason' => null,
            ];
            if (Schema::hasColumn('leave_requests', 'approved_by_name')) {
                $data['approved_by_name'] = $adminName;
            }
            $leave->update($data);

            $empName = $leave->employee?->full_name ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Approve Leave Request',
                "Approved {$leave->leave_type} request for {$empName}",
                'Leave Request'
            );
        });
    }

    public function decline(LeaveRequest $leave, int $adminId, string $adminName, string $reason): void
    {
        $leave->update([
            'status' => 'Rejected',
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $empName = $leave->employee?->full_name ?? 'Unknown';
        $this->activityLogger->log(
            $adminId,
            $adminName,
            'Decline Leave Request',
            "Declined {$leave->leave_type} request for {$empName}. Reason: ".substr($reason, 0, 100),
            'Leave Request'
        );
    }

    public function calculatedDays(LeaveRequest $leave): int
    {
        if ($leave->start_date === $leave->end_date) {
            return max(1, (int) ($leave->total_days ?? $leave->days ?? 1));
        }

        if ($leave->total_days) {
            return (int) $leave->total_days;
        }
        if ($leave->days) {
            return (int) $leave->days;
        }

        return max(1, $leave->start_date->diffInDays($leave->end_date) + 1);
    }
}
