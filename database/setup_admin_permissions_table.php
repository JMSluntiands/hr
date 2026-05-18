<?php
/**
 * One-time setup for admin_user_permissions table.
 * Open in browser: /hr/database/setup_admin_permissions_table.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';

if (!ensureAdminUserPermissionsTable($conn)) {
    die('Failed to create admin_user_permissions table: ' . htmlspecialchars($conn->error ?? 'unknown'));
}

echo '✓ admin_user_permissions table is ready.<br>';
echo '<a href="../permission/index.php">Go to Department Permissions</a>';
