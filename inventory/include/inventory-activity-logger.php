<?php

if (!function_exists('inventoryCanLogActivity')) {
    function inventoryCanLogActivity($conn): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        if (!($conn instanceof mysqli)) {
            $cached = false;
            return $cached;
        }

        $check = $conn->query("SHOW TABLES LIKE 'inventory_activity_logs'");
        $cached = (bool)($check && $check->num_rows > 0);
        return $cached;
    }
}

if (!function_exists('inventoryLogActivity')) {
    /**
     * Log inventory activity to inventory_activity_logs table (separate from HR activity_logs).
     *
     * @param mysqli $conn
     * @param string $action e.g. "Update Item [ITEM: LAP-0001]"
     * @param string $entityType e.g. "Item", "Allocation", "Appeal"
     * @param int|null $entityId
     * @param string $description Main description
     * @param string|null $changeDetails Detailed field changes, e.g. "Item name: Laptop → Desktop\nDescription: old → new"
     * @param string|null $itemCode e.g. "LAP-0001" for display/filtering
     */
    function inventoryLogActivity($conn, string $action, string $entityType, ?int $entityId = null, string $description = '', ?string $changeDetails = null, ?string $itemCode = null): bool
    {
        if (!inventoryCanLogActivity($conn) || !isset($_SESSION['user_id'])) {
            return false;
        }

        $userId = (int)$_SESSION['user_id'];
        $userName = (string)($_SESSION['name'] ?? 'Unknown');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $entityType = trim($entityType);
        if ($entityType === '') {
            $entityType = 'Item';
        }

        $stmt = $conn->prepare("
            INSERT INTO inventory_activity_logs (user_id, user_name, action, entity_type, entity_id, item_code, description, change_details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }
        $changeDetailsVal = $changeDetails !== null && $changeDetails !== '' ? $changeDetails : null;
        $itemCodeVal = $itemCode !== null && $itemCode !== '' ? $itemCode : null;
        $stmt->bind_param('isssissss', $userId, $userName, $action, $entityType, $entityId, $itemCodeVal, $description, $changeDetailsVal, $ipAddress);
        $result = $stmt->execute();
        $stmt->close();

        return (bool)$result;
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
