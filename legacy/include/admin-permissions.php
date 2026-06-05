<?php
/**
 * Department-scoped approval permissions for admin users.
 * If a user has no rows in admin_user_permissions, they retain full approve access (legacy).
 */

if (!function_exists('adminPermissionGroupLabels')) {
    /** @return array<string, string> */
    function adminPermissionGroupLabels(): array
    {
        return [
            'sidebar' => 'Sidebar buttons',
            'card' => 'Card',
            'actions' => 'Card edit/add buttons',
        ];
    }
}

if (!function_exists('adminPermissionModules')) {
    /**
     * Permissions per module: Sidebar buttons, Card, Card edit/add buttons.
     *
     * @return array<string, array{label: string, subtitle: string, groups: array<string, array{label: string, items: array<string, array{label: string, path?: string}>}>}>
     */
    function adminPermissionModules(): array
    {
        return [
            'inventory' => [
                'label' => 'Inventory',
                'subtitle' => 'INVENTORY MANAGEMENT',
                'groups' => [
                    'sidebar' => [
                        'label' => 'Sidebar buttons',
                        'items' => [
                            'inventory_dashboard' => ['label' => 'Inventory', 'path' => '/inventory'],
                            'inventory_items_list' => ['label' => 'List Item', 'path' => '/inventory/items?tab=list'],
                            'inventory_items_add' => ['label' => 'Add Item', 'path' => '/inventory/items?tab=add'],
                            'inventory_items_history' => ['label' => 'Item History', 'path' => '/inventory/items?tab=history'],
                            'inventory_allocation' => ['label' => 'Allocation', 'path' => '/inventory/allocation'],
                            'inventory_nav_requests' => ['label' => 'Request', 'path' => '/inventory/requests'],
                            'inventory_nav_decommission' => ['label' => 'Decommission', 'path' => '/inventory/decommission'],
                            'inventory_report' => ['label' => 'Report', 'path' => '/inventory/report'],
                            'inventory_messages' => ['label' => 'Messages', 'path' => '/inventory/messages'],
                            'inventory_activity_log' => ['label' => 'Activity Log', 'path' => '/inventory/activity-log'],
                        ],
                    ],
                    'card' => [
                        'label' => 'Card',
                        'items' => [
                            'inventory_card_dashboard' => ['label' => 'Inventory — Dashboard stats'],
                            'inventory_card_items_list' => ['label' => 'Inventory — Items table'],
                            'inventory_card_items_form' => ['label' => 'Inventory — Add / edit item form'],
                            'inventory_card_items_history' => ['label' => 'Inventory — Item history log'],
                            'inventory_card_allocation' => ['label' => 'Inventory — Allocation table'],
                            'inventory_card_requests' => ['label' => 'Inventory — Request queue'],
                            'inventory_card_decommission' => ['label' => 'Inventory — Decommission queue'],
                            'inventory_card_report' => ['label' => 'Inventory — Report charts'],
                            'inventory_card_messages' => ['label' => 'Inventory — Messages inbox'],
                        ],
                    ],
                    'actions' => [
                        'label' => 'Card edit/add buttons',
                        'items' => [
                            'inventory_action_items_add' => ['label' => 'Inventory Add Item'],
                            'inventory_action_items_edit' => ['label' => 'Inventory Update Item'],
                            'inventory_action_items_delete' => ['label' => 'Inventory Delete Item'],
                            'inventory_action_allocation_save' => ['label' => 'Inventory Save Allocation'],
                            'inventory_approve_request' => ['label' => 'Inventory Approve Request'],
                            'inventory_action_request_decline' => ['label' => 'Inventory Decline Request'],
                            'inventory_approve_decommission' => ['label' => 'Inventory Approve Decommission'],
                            'inventory_action_decommission_decline' => ['label' => 'Inventory Decline Decommission'],
                            'inventory_action_report_export' => ['label' => 'Inventory Export Report'],
                            'inventory_action_messages_reply' => ['label' => 'Inventory Reply Message'],
                        ],
                    ],
                ],
            ],
            'hr' => [
                'label' => 'HR',
                'subtitle' => 'HR MANAGEMENT',
                'groups' => [
                    'sidebar' => [
                        'label' => 'Sidebar buttons',
                        'items' => [
                            'hr_nav_leave_requests' => ['label' => 'Request Leaves', 'path' => '/admin/leave-requests'],
                            'hr_nav_documents' => ['label' => 'Request Document', 'path' => '/admin/documents'],
                            'hr_nav_document_uploads' => ['label' => 'Request Upload', 'path' => '/admin/document-uploads'],
                            'hr_nav_bank_requests' => ['label' => 'Request Bank', 'path' => '/admin/bank-requests'],
                            'hr_nav_reimbursements' => ['label' => 'Reimbursement Review', 'path' => '/admin/reimbursements'],
                            'hr_nav_document_archive' => ['label' => 'Document Archive', 'path' => '/admin/document-archive'],
                        ],
                    ],
                    'card' => [
                        'label' => 'Card',
                        'items' => [
                            'hr_card_leave_requests' => ['label' => 'HR — Leave request details'],
                            'hr_card_documents' => ['label' => 'HR — Document request details'],
                            'hr_card_document_uploads' => ['label' => 'HR — Upload request details'],
                            'hr_card_bank_requests' => ['label' => 'HR — Bank change details'],
                            'hr_card_reimbursements' => ['label' => 'HR — Reimbursement details'],
                            'hr_card_document_archive' => ['label' => 'HR — Archive removal details'],
                        ],
                    ],
                    'actions' => [
                        'label' => 'Card edit/add buttons',
                        'items' => [
                            'approve_leave' => ['label' => 'HR Approve Leave'],
                            'hr_action_leave_decline' => ['label' => 'HR Decline Leave'],
                            'approve_document_request' => ['label' => 'HR Approve Document'],
                            'hr_action_document_decline' => ['label' => 'HR Decline Document'],
                            'approve_document_upload' => ['label' => 'HR Approve Upload'],
                            'hr_action_upload_decline' => ['label' => 'HR Decline Upload'],
                            'approve_bank_change' => ['label' => 'HR Approve Bank Change'],
                            'hr_action_bank_decline' => ['label' => 'HR Decline Bank Change'],
                            'approve_reimbursement' => ['label' => 'HR Approve Reimbursement'],
                            'hr_action_reimbursement_decline' => ['label' => 'HR Decline Reimbursement'],
                            'approve_document_removal' => ['label' => 'HR Approve Archive Removal'],
                            'hr_action_archive_reject' => ['label' => 'HR Reject Archive Removal'],
                        ],
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('adminPermissionModuleRouteCount')) {
    function adminPermissionModuleRouteCount(array $module): int
    {
        $count = 0;
        foreach ($module['groups'] ?? [] as $group) {
            $count += count($group['items'] ?? []);
        }
        return $count;
    }
}

if (!function_exists('adminPermissionPages')) {
    /**
     * Flat map of all permission keys (Inventory + HR).
     *
     * @return array<string, array{label: string, path?: string, module: string, group: string}>
     */
    function adminPermissionPages(): array
    {
        $pages = [];
        foreach (adminPermissionModules() as $moduleKey => $module) {
            foreach ($module['groups'] as $groupKey => $group) {
                foreach ($group['items'] as $permKey => $item) {
                    $pages[$permKey] = $item + [
                        'module' => $moduleKey,
                        'group' => $groupKey,
                    ];
                }
            }
        }
        return $pages;
    }
}

if (!function_exists('adminPermissionDefinitions')) {
    function adminPermissionDefinitions(): array
    {
        $defs = [];
        foreach (adminPermissionPages() as $key => $page) {
            $defs[$key] = $page['label'] ?? $key;
        }
        return $defs;
    }
}

if (!function_exists('ensureAdminUserPermissionsTable')) {
    function ensureAdminUserPermissionsTable($conn): bool
    {
        if (!$conn) {
            return false;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `admin_user_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `department_id` int(11) NOT NULL,
            `permission_key` varchar(64) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_user_dept_perm` (`user_id`, `department_id`, `permission_key`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_department_id` (`department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        return (bool)$conn->query($sql);
    }
}

if (!function_exists('adminUserHasConfiguredPermissions')) {
    function adminUserHasConfiguredPermissions($conn, int $userId): bool
    {
        if (!$conn || $userId <= 0) {
            return false;
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare('SELECT 1 FROM admin_user_permissions WHERE user_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row);
    }
}

if (!function_exists('adminGetEmployeeDepartmentName')) {
    function adminGetEmployeeDepartmentName($conn, int $employeeId): ?string
    {
        if (!$conn || $employeeId <= 0) {
            return null;
        }
        $stmt = $conn->prepare('SELECT department FROM employees WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $name = trim((string)($row['department'] ?? ''));
        return $name !== '' ? $name : null;
    }
}

if (!function_exists('adminResolveDepartmentIdByName')) {
    function adminResolveDepartmentIdByName($conn, string $departmentName): ?int
    {
        if (!$conn || $departmentName === '') {
            return null;
        }
        $stmt = $conn->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $departmentName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        return (int)$row['id'];
    }
}

if (!function_exists('adminCanAccessSidebar')) {
    /**
     * Sidebar link visible if user has this permission in any department.
     * No saved permissions = full access (all sidebar links).
     */
    function adminCanAccessSidebar($conn, int $userId, string $permissionKey): bool
    {
        if (!$conn || $userId <= 0 || $permissionKey === '') {
            return false;
        }
        if (!adminUserHasConfiguredPermissions($conn, $userId)) {
            return true;
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare(
            'SELECT 1 FROM admin_user_permissions WHERE user_id = ? AND permission_key = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('is', $userId, $permissionKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row)) {
            return true;
        }

        $aliases = [
            'hr_nav_leave_requests' => ['approve_leave'],
            'hr_nav_documents' => ['approve_document_request'],
            'hr_nav_document_uploads' => ['approve_document_upload'],
            'hr_nav_bank_requests' => ['approve_bank_change'],
            'hr_nav_reimbursements' => ['approve_reimbursement'],
            'hr_nav_document_archive' => ['approve_document_removal'],
            'inventory_nav_requests' => ['inventory_approve_request'],
            'inventory_nav_decommission' => ['inventory_approve_decommission'],
        ];
        foreach ($aliases[$permissionKey] ?? [] as $legacyKey) {
            if (adminUserHasAnyDepartmentPermission($conn, $userId, $legacyKey)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('adminUserHasAnyDepartmentPermission')) {
    function adminUserHasAnyDepartmentPermission($conn, int $userId, string $permissionKey): bool
    {
        if (!$conn || $userId <= 0 || $permissionKey === '') {
            return false;
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare(
            'SELECT 1 FROM admin_user_permissions WHERE user_id = ? AND permission_key = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('is', $userId, $permissionKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row);
    }
}

if (!function_exists('adminUserHasDepartmentPermission')) {
    function adminUserHasDepartmentPermission($conn, int $userId, string $permissionKey, int $departmentId): bool
    {
        if (!$conn || $userId <= 0 || $departmentId <= 0 || $permissionKey === '') {
            return false;
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare(
            'SELECT 1 FROM admin_user_permissions
             WHERE user_id = ? AND department_id = ? AND permission_key = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iis', $userId, $departmentId, $permissionKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row);
    }
}

if (!function_exists('adminCanApproveEmployee')) {
    function adminCanApproveEmployee($conn, int $userId, string $permissionKey, int $employeeId): bool
    {
        if (!$conn || $userId <= 0 || $employeeId <= 0) {
            return false;
        }
        if (!adminUserHasConfiguredPermissions($conn, $userId)) {
            return true;
        }
        $deptName = adminGetEmployeeDepartmentName($conn, $employeeId);
        if ($deptName === null) {
            return false;
        }
        $departmentId = adminResolveDepartmentIdByName($conn, $deptName);
        if ($departmentId === null) {
            return false;
        }
        return adminUserHasDepartmentPermission($conn, $userId, $permissionKey, $departmentId);
    }
}

if (!function_exists('adminDenyApprovalRedirect')) {
    function adminDenyApprovalRedirect(string $sessionKey, string $redirectUrl): void
    {
        $_SESSION[$sessionKey] = 'You do not have permission to approve or decline requests for this employee\'s department.';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

if (!function_exists('adminGetUserPermissionsMatrix')) {
    /**
     * @return array<int, array<string, bool>> department_id => permission_key => true
     */
    function adminGetUserPermissionsMatrix($conn, int $userId): array
    {
        $matrix = [];
        if (!$conn || $userId <= 0) {
            return $matrix;
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare(
            'SELECT department_id, permission_key FROM admin_user_permissions WHERE user_id = ?'
        );
        if (!$stmt) {
            return $matrix;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $deptId = (int)$row['department_id'];
            $key = (string)$row['permission_key'];
            if (!isset($matrix[$deptId])) {
                $matrix[$deptId] = [];
            }
            $matrix[$deptId][$key] = true;
        }
        $stmt->close();
        return $matrix;
    }
}

if (!function_exists('adminSaveUserPermissions')) {
  /**
   * @param array<int, string[]> $permissionsByDept department_id => list of permission keys
   */
    function adminSaveUserPermissions($conn, int $userId, array $permissionsByDept): bool
    {
        if (!$conn || $userId <= 0) {
            return false;
        }
        ensureAdminUserPermissionsTable($conn);
        $definitions = adminPermissionDefinitions();

        $conn->begin_transaction();
        try {
            $del = $conn->prepare('DELETE FROM admin_user_permissions WHERE user_id = ?');
            if (!$del) {
                throw new RuntimeException('Failed to clear permissions');
            }
            $del->bind_param('i', $userId);
            if (!$del->execute()) {
                throw new RuntimeException('Failed to clear permissions');
            }
            $del->close();

            $ins = $conn->prepare(
                'INSERT INTO admin_user_permissions (user_id, department_id, permission_key) VALUES (?, ?, ?)'
            );
            if (!$ins) {
                throw new RuntimeException('Failed to prepare insert');
            }

            foreach ($permissionsByDept as $departmentId => $keys) {
                $departmentId = (int)$departmentId;
                if ($departmentId <= 0 || !is_array($keys)) {
                    continue;
                }
                foreach ($keys as $key) {
                    $key = (string)$key;
                    if (!isset($definitions[$key])) {
                        continue;
                    }
                    $ins->bind_param('iis', $userId, $departmentId, $key);
                    if (!$ins->execute()) {
                        throw new RuntimeException('Failed to save permission');
                    }
                }
            }
            $ins->close();
            $conn->commit();
            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            return false;
        }
    }
}

if (!function_exists('adminCanShowApproveForEmployee')) {
    function adminCanShowApproveForEmployee($conn, int $userId, string $permissionKey, int $employeeId): bool
    {
        return adminCanApproveEmployee($conn, $userId, $permissionKey, $employeeId);
    }
}

if (!function_exists('adminGetDepartmentNamesForPermission')) {
    /**
     * @return string[]
     */
    function adminGetDepartmentNamesForPermission($conn, int $userId, string $permissionKey): array
    {
        if (!$conn || $userId <= 0) {
            return [];
        }
        if (!adminUserHasConfiguredPermissions($conn, $userId)) {
            return [];
        }
        ensureAdminUserPermissionsTable($conn);
        $stmt = $conn->prepare(
            'SELECT d.name
             FROM admin_user_permissions p
             INNER JOIN departments d ON d.id = p.department_id
             WHERE p.user_id = ? AND p.permission_key = ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('is', $userId, $permissionKey);
        $stmt->execute();
        $names = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $n = trim((string)($row['name'] ?? ''));
            if ($n !== '') {
                $names[] = $n;
            }
        }
        $stmt->close();
        return $names;
    }
}
