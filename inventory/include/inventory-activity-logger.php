<?php

require_once __DIR__ . '/../../admin/include/activity-logger.php';

if (!function_exists('inventoryCanLogActivity')) {
    function inventoryCanLogActivity($conn): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        if (!($conn instanceof mysqli) || !function_exists('logActivity')) {
            $cached = false;
            return $cached;
        }

        $check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
        $cached = (bool)($check && $check->num_rows > 0);
        return $cached;
    }
}

if (!function_exists('inventoryLogActivity')) {
    function inventoryLogActivity($conn, string $action, string $entityType, ?int $entityId = null, string $description = ''): bool
    {
        if (!inventoryCanLogActivity($conn)) {
            return false;
        }

        $entityType = trim($entityType);
        if ($entityType === '') {
            $entityType = 'Inventory';
        } elseif (stripos($entityType, 'inventory') !== 0) {
            $entityType = 'Inventory ' . $entityType;
        }

        return (bool)logActivity($conn, $action, $entityType, $entityId, $description);
    }
}

if (!function_exists('inventoryGetItemCodeByItemDbId')) {
    function inventoryGetItemCodeByItemDbId($conn, int $itemDbId): string
    {
        if (!($conn instanceof mysqli) || $itemDbId <= 0) {
            return '';
        }

        $stmt = $conn->prepare("SELECT item_id FROM inventory_items WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $itemDbId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return trim((string)($row['item_id'] ?? ''));
    }
}

if (!function_exists('inventoryGetItemCodeByAllocationId')) {
    function inventoryGetItemCodeByAllocationId($conn, int $allocationId): string
    {
        if (!($conn instanceof mysqli) || $allocationId <= 0) {
            return '';
        }

        $stmt = $conn->prepare("
            SELECT ii.item_id
            FROM inventory_item_allocations ia
            JOIN inventory_items ii ON ii.id = ia.inventory_item_id
            WHERE ia.id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $allocationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return trim((string)($row['item_id'] ?? ''));
    }
}

if (!function_exists('inventoryActionWithItemCode')) {
    function inventoryActionWithItemCode(string $action, string $itemCode): string
    {
        $label = trim($itemCode) !== '' ? trim($itemCode) : 'NO-ID';
        return trim($action) . ' [ITEM: ' . $label . ']';
    }
}
