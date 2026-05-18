<?php
/**
 * Department-scoped approval permissions for admin users.
 * If a user has no rows in admin_user_permissions, they retain full approve access (legacy).
 */

if (!function_exists('adminPermissionPages')) {
    /**
     * HR admin pages that require department-scoped approve permission.
     *
     * @return array<string, array{label: string, description: string, admin_path: string}>
     */
    function adminPermissionPages(): array
    {
        return [
            'approve_leave' => [
                'label' => 'Request Leaves',
                'description' => 'Approve or decline employee leave requests.',
                'admin_path' => 'request-leaves.php',
            ],
            'approve_document_request' => [
                'label' => 'Request Document',
                'description' => 'Approve or decline certificate and document requests (COE, etc.).',
                'admin_path' => 'request-document.php',
            ],
            'approve_document_upload' => [
                'label' => 'Request Upload',
                'description' => 'Approve or decline profile document uploads.',
                'admin_path' => 'request-upload.php',
            ],
            'approve_bank_change' => [
                'label' => 'Request Bank',
                'description' => 'Approve or decline bank account change requests.',
                'admin_path' => 'request-bank.php',
            ],
            'approve_reimbursement' => [
                'label' => 'Reimbursement Review',
                'description' => 'Approve or decline reimbursement requests.',
                'admin_path' => 'reimbursement-review.php',
            ],
            'approve_document_removal' => [
                'label' => 'Document Archive',
                'description' => 'Approve or reject employee document removal requests.',
                'admin_path' => 'document-archive.php',
            ],
        ];
    }
}

if (!function_exists('adminPermissionDefinitions')) {
    function adminPermissionDefinitions(): array
    {
        $defs = [];
        foreach (adminPermissionPages() as $key => $page) {
            $defs[$key] = 'Approve — ' . ($page['label'] ?? $key);
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
