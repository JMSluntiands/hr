<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPermissionService
{
    /**
     * Department permissions control approve actions per department only.
     * Admin navigation stays full access by default.
     */
    public function canAccessSidebar(int $adminUserId, string $permissionKey): bool
    {
        return true;
    }

    public function isSidebarRestricted(int $adminUserId): bool
    {
        return false;
    }

    public function canAccessRoute(int $adminUserId, ?string $routeName, ?string $tab = null): bool
    {
        return true;
    }

    public function hasPermissionInAnyDepartment(int $adminUserId, string $permissionKey): bool
    {
        if (! Schema::hasTable('admin_user_permissions')) {
            return true;
        }

        return DB::table('admin_user_permissions')
            ->where('user_id', $adminUserId)
            ->where('permission_key', $permissionKey)
            ->exists();
    }

    public function canApprove(int $adminUserId, string $permissionKey, int $employeeId): bool
    {
        if ($adminUserId <= 0 || $employeeId <= 0 || ! Schema::hasTable('admin_user_permissions')) {
            return true;
        }

        if (! $this->hasConfiguredPermissions($adminUserId)) {
            return true;
        }

        $deptName = DB::table('employees')->where('id', $employeeId)->value('department');
        $deptName = trim((string) $deptName);
        if ($deptName === '') {
            return false;
        }

        $departmentId = DB::table('departments')->where('name', $deptName)->value('id');
        if (! $departmentId) {
            return false;
        }

        return DB::table('admin_user_permissions')
            ->where('user_id', $adminUserId)
            ->where('department_id', $departmentId)
            ->where('permission_key', $permissionKey)
            ->exists();
    }

    public function hasConfiguredPermissions(int $adminUserId): bool
    {
        if (! Schema::hasTable('admin_user_permissions')) {
            return false;
        }

        return DB::table('admin_user_permissions')->where('user_id', $adminUserId)->exists();
    }

    public function pendingCounts(): array
    {
        $counts = [
            'leaves' => 0,
            'documents' => 0,
            'uploads' => 0,
            'bank' => 0,
            'reimbursements' => 0,
            'archive' => 0,
            'incidents' => 0,
        ];

        if (Schema::hasTable('leave_requests')) {
            $counts['leaves'] = (int) DB::table('leave_requests')->where('status', 'Pending')->count();
        }
        if (Schema::hasTable('document_requests')) {
            $counts['documents'] = (int) DB::table('document_requests')->where('status', 'Pending')->count();
        }
        if (Schema::hasTable('employee_document_uploads')) {
            $counts['uploads'] = (int) DB::table('employee_document_uploads')->where('status', 'Pending')->count();
            if (Schema::hasColumn('employee_document_uploads', 'deletion_requested_at')) {
                $counts['archive'] = (int) DB::table('employee_document_uploads')
                    ->where('status', 'Approved')
                    ->whereNotNull('deletion_requested_at')
                    ->count();
            }
        }
        if (Schema::hasTable('bank_account_change_requests')) {
            $counts['bank'] = (int) DB::table('bank_account_change_requests')->where('status', 'Pending')->count();
        }
        if (Schema::hasTable('reimbursements')) {
            $counts['reimbursements'] = (int) DB::table('reimbursements')->where('status', 'Pending')->count();
        }
        if (Schema::hasTable('incident_reports') && Schema::hasColumn('incident_reports', 'review_status')) {
            $counts['incidents'] = (int) DB::table('incident_reports')
                ->whereRaw("COALESCE(NULLIF(TRIM(review_status), ''), 'Pending') = 'Pending'")
                ->count();
        }

        return $counts;
    }
}
