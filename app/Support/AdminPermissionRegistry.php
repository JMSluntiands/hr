<?php

namespace App\Support;

class AdminPermissionRegistry
{
    public static function sidebarKeys(string $module): array
    {
        return config("admin_permissions.{$module}", []);
    }

    public static function isSidebarKey(string $permissionKey): bool
    {
        return in_array($permissionKey, array_merge(
            self::sidebarKeys('inventory'),
            self::sidebarKeys('hr')
        ), true);
    }

    public static function resolveRoutePermissionKey(?string $routeName, ?string $tab = null): ?string
    {
        if (! $routeName) {
            return null;
        }

        if ($routeName === 'inventory.items.index') {
            $tab = $tab ?: 'list';
            return config("admin_permissions.inventory_item_tabs.{$tab}");
        }

        return config("admin_permissions.routes.{$routeName}");
    }
}
