<?php
/**
 * One-time setup for department_permissions table.
 * Open in browser: /database/setup_admin_permissions_table.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../legacy/include/admin-permissions.php';

if (!ensureDepartmentPermissionsTable($conn)) {
    die('Failed to create department_permissions table: ' . htmlspecialchars($conn->error ?? 'unknown'));
}

echo '✓ department_permissions table is ready.<br>';
echo '<a href="/permission">Go to Department Permissions</a>';
