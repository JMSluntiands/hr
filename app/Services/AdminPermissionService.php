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

    /** Sidebar / route keys satisfied by related card permissions. */
    /** @var array<string, string[]> */
    private const CARD_ALIASES = [
        'inventory_dashboard' => ['inventory_card_dashboard'],
        'inventory_items_list' => ['inventory_card_items_list'],
        'inventory_items_add' => ['inventory_card_items_form'],
        'inventory_items_history' => ['inventory_card_items_history'],
        'inventory_allocation' => ['inventory_card_allocation'],
        'inventory_nav_requests' => ['inventory_card_requests'],
        'inventory_nav_decommission' => ['inventory_card_decommission'],
        'inventory_report' => ['inventory_card_report'],
        'inventory_messages' => ['inventory_card_messages'],
        'hr_nav_leave_requests' => ['hr_card_leave_requests'],
        'hr_nav_documents' => ['hr_card_documents'],
        'hr_nav_document_uploads' => ['hr_card_document_uploads'],
        'hr_nav_bank_requests' => ['hr_card_bank_requests'],
        'hr_nav_reimbursements' => ['hr_card_reimbursements'],
        'hr_nav_document_archive' => ['hr_card_document_archive'],
    ];

    public function can(int $adminUserId, string $permissionKey): bool
    {
        if ($adminUserId <= 0 || $permissionKey === '') {
            return false;
        }

        if (! Schema::hasTable('department_permissions')) {
            return true;
        }

        $departmentId = $this->resolveAdminDepartmentId($adminUserId);
        if ($departmentId === null) {
            return true;
        }

        if (! $this->departmentHasConfiguredPermissions($departmentId)) {
            return true;
        }

        return DB::table('department_permissions')
            ->where('department_id', $departmentId)
            ->where('permission_key', $permissionKey)
            ->exists();
    }

    public function canAccessSidebar(int $adminUserId, string $permissionKey): bool
    {
        if ($adminUserId <= 0 || $permissionKey === '') {
            return false;
        }

        if (! $this->isSidebarRestricted($adminUserId)) {
            return true;
        }

        if ($this->can($adminUserId, $permissionKey)) {
            return true;
        }

        foreach (self::CARD_ALIASES[$permissionKey] ?? [] as $cardKey) {
            if ($this->can($adminUserId, $cardKey)) {
                return true;
            }
        }

        foreach (self::SIDEBAR_LEGACY_ALIASES[$permissionKey] ?? [] as $legacyKey) {
            if ($this->can($adminUserId, $legacyKey)) {
                return true;
            }
        }

        return false;
    }

    public function isSidebarRestricted(int $adminUserId): bool
    {
        if (! Schema::hasTable('department_permissions')) {
            return false;
        }

        $departmentId = $this->resolveAdminDepartmentId($adminUserId);

        return $departmentId !== null && $this->departmentHasConfiguredPermissions($departmentId);
    }

    public function canAccessRoute(int $adminUserId, ?string $routeName, ?string $tab = null): bool
    {
        $key = AdminPermissionRegistry::resolveRoutePermissionKey($routeName, $tab);
        if ($key === null) {
            return true;
        }

        return $this->canAccessSidebar($adminUserId, $key);
    }

    public function canApprove(int $adminUserId, string $permissionKey, int $employeeId): bool
    {
        if ($adminUserId <= 0 || $permissionKey === '') {
            return false;
        }

        if (! Schema::hasTable('department_permissions')) {
            return true;
        }

        // Find the employee's department.
        $deptName = $employeeId > 0
            ? trim((string) DB::table('employees')->where('id', $employeeId)->value('department'))
            : '';

        if ($deptName === '') {
            // No employee record or no department — grant access (no restriction possible).
            return true;
        }

        $departmentId = DB::table('departments')->where('name', $deptName)->value('id');
        if (! $departmentId) {
            // Department name exists on employee but not in departments table — grant access.
            return true;
        }

        if (! $this->departmentHasConfiguredPermissions((int) $departmentId)) {
            // No saved permissions for this department — full access.
            return true;
        }

        return DB::table('department_permissions')
            ->where('department_id', $departmentId)
            ->where('permission_key', $permissionKey)
            ->exists();
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

    private function resolveAdminDepartmentId(int $adminUserId): ?int
    {
        if ($adminUserId <= 0 || ! Schema::hasTable('user_login')) {
            return null;
        }

        $email = DB::table('user_login')->where('id', $adminUserId)->value('email');
        $email = trim((string) $email);
        if ($email === '' || ! Schema::hasTable('employees')) {
            return null;
        }

        $deptName = DB::table('employees')->where('email', $email)->value('department');
        $deptName = trim((string) $deptName);
        if ($deptName === '' || ! Schema::hasTable('departments')) {
            return null;
        }

        $departmentId = DB::table('departments')->where('name', $deptName)->value('id');

        return $departmentId ? (int) $departmentId : null;
    }

    private function departmentHasConfiguredPermissions(int $departmentId): bool
    {
        if ($departmentId <= 0 || ! Schema::hasTable('department_permissions')) {
            return false;
        }

        return DB::table('department_permissions')
            ->where('department_id', $departmentId)
            ->exists();
    }
}
