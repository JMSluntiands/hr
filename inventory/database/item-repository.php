<?php

require_once __DIR__ . '/item-config.php';
require_once __DIR__ . '/setup_inventory_items_table.php';

function generateInventoryItemId(mysqli $conn, string $prefix): string
{
    $sql = "SELECT item_id FROM inventory_items WHERE item_id LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $prefix . '0001';
    }
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $lastItemId = '';
    if ($result && $row = $result->fetch_assoc()) {
        $lastItemId = (string)$row['item_id'];
    }
    $stmt->close();

    $nextNumber = 1;
    if ($lastItemId !== '') {
        $lastNumber = (int)preg_replace('/[^0-9]/', '', substr($lastItemId, strlen($prefix)));
        $nextNumber = $lastNumber + 1;
    }

    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}

function createInventoryItem(mysqli $conn, array $data): bool
{
    $prefixes = getInventoryItemPrefixes();
    if (!isset($prefixes[$data['item_name']])) {
        return false;
    }

    $itemId = generateInventoryItemId($conn, $prefixes[$data['item_name']]);
    $stmt = $conn->prepare("
        INSERT INTO inventory_items (item_id, item_name, description, `type`, item_condition, remarks, item_image_path, date_arrived)
        VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))
    ");
    if (!$stmt) {
        error_log('createInventoryItem prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param(
        'ssssssss',
        $itemId,
        $data['item_name'],
        $data['description'],
        $data['type'],
        $data['item_condition'],
        $data['remarks'],
        $data['item_image_path'],
        $data['date_arrived']
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function updateInventoryItem(mysqli $conn, int $id, array $data): bool
{
    $prefixes = getInventoryItemPrefixes();
    if (!isset($prefixes[$data['item_name']])) {
        return false;
    }

    $currentItemId = '';
    $stmtGet = $conn->prepare("SELECT item_id FROM inventory_items WHERE id = ? LIMIT 1");
    if (!$stmtGet) {
        error_log('updateInventoryItem initial select prepare failed: ' . $conn->error);
        return false;
    }
    $stmtGet->bind_param('i', $id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $currentItemId = (string)$row['item_id'];
    }
    $stmtGet->close();

    $expectedPrefix = $prefixes[$data['item_name']];
    $finalItemId = (strpos($currentItemId, $expectedPrefix) === 0 && $currentItemId !== '')
        ? $currentItemId
        : generateInventoryItemId($conn, $expectedPrefix);

    $stmt = $conn->prepare("
        UPDATE inventory_items
        SET item_id = ?, item_name = ?, description = ?, `type` = ?, item_condition = ?, remarks = ?, item_image_path = NULLIF(?, ''), date_arrived = NULLIF(?, '')
        WHERE id = ?
    ");
    if (!$stmt) {
        error_log('updateInventoryItem prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param(
        'ssssssssi',
        $finalItemId,
        $data['item_name'],
        $data['description'],
        $data['type'],
        $data['item_condition'],
        $data['remarks'],
        $data['item_image_path'],
        $data['date_arrived'],
        $id
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function deleteInventoryItem(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
    if (!$stmt) {
        error_log('deleteInventoryItem prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function listInventoryItems(mysqli $conn): array
{
    $items = [];
    $result = $conn->query("
        SELECT id, item_id, item_name, description, `type` AS type, item_condition, remarks, item_image_path, date_arrived
        FROM inventory_items
        ORDER BY id DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function getInventoryItemById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("
        SELECT id, item_id, item_name, description, `type` AS type, item_condition, remarks, item_image_path, date_arrived
        FROM inventory_items
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        error_log('getInventoryItemById prepare failed: ' . $conn->error);
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = null;
    if ($result && $row = $result->fetch_assoc()) {
        $item = $row;
    }
    $stmt->close();
    return $item;
}
