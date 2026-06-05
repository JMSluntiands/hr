<?php

namespace App\Services;

use App\Support\AdminPermissionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPermissionService
{
    /** @var array<string, string[]> */
    private const SIDEBAR_LEGACY_ALIASES = [
        'hr_nav_leave_requests' => ['approve_leave'],
        'hr_nav_documents' => ['approve_document_request'],
        'hr_nav_document_uploads' => ['approve_document_upload'],
        'hr_nav_bank_requests' => ['approve_bank_change'],
        'hr_nav_reimbursements' => ['approve_reimbursement'],
        'hr_nav_document_archive' => ['approve_document_removal'],
        'inventory_nav_requests' => ['inventory_approve_request'],
        'inventory_nav_decommission' => ['inventory_approve_decommission'],
    ];

    public function canAccessSidebar(int $adminUserId, string $permissionKey): bool
    {
        if ($adminUserId <= 0 || $permissionKey === '') {
            return false;
        }

        if (! $this->hasConfiguredPermissions($adminUserId)) {
            return true;
        }

        if (! AdminPermissionRegistry::isSidebarKey($permissionKey)) {
            return false;
        }

        if ($this->hasPermissionInAnyDepartment($adminUserId, $permissionKey)) {
            return true;
        }

        foreach (self::SIDEBAR_LEGACY_ALIASES[$permissionKey] ?? [] as $legacyKey) {
            if ($this->hasPermissionInAnyDepartment($adminUserId, $legacyKey)) {
                return true;
            }
        }

        return false;
    }

    public function isSidebarRestricted(int $adminUserId): bool
    {
        return $this->hasConfiguredPermissions($adminUserId);
    }

    public function canAccessRoute(int $adminUserId, ?string $routeName, ?string $tab = null): bool
    {
        $key = AdminPermissionRegistry::resolveRoutePermissionKey($routeName, $tab);
        if ($key === null) {
            return true;
        }

        return $this->canAccessSidebar($adminUserId, $key);
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
