<?php

require_once __DIR__ . '/item-config.php';
require_once __DIR__ . '/setup_inventory_items_table.php';
require_once __DIR__ . '/mysqli-stmt-fetch.php';

/** Empty string → null for nullable DB columns (bind as SQL NULL in PHP; avoids NULLIF collation errors online). */
function inventory_empty_string_to_null(?string $value): ?string
{
    $trimmed = trim((string)($value ?? ''));
    return $trimmed === '' ? null : $trimmed;
}

/** @return list<string> */
function inventory_item_image_paths_list_from_row(array $row): array
{
    $json = isset($row['item_image_paths']) ? trim((string)$row['item_image_paths']) : '';
    $legacy = trim((string)($row['item_image_path'] ?? ''));
    $paths = [];
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $p) {
                if (is_string($p) && trim($p) !== '') {
                    $paths[] = trim($p);
                }
            }
        }
    }
    if (empty($paths) && $legacy !== '') {
        $paths[] = $legacy;
    }
    return array_values(array_unique($paths));
}

/** @param list<string>|null $paths */
function inventory_item_image_paths_to_json(?array $paths): ?string
{
    if ($paths === null || $paths === []) {
        return null;
    }
    $clean = [];
    foreach ($paths as $p) {
        $t = trim((string)$p);
        if ($t !== '') {
            $clean[] = $t;
        }
    }
    if ($clean === []) {
        return null;
    }
    return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_SLASHES);
}

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
    $row = inventory_stmt_fetch_one_assoc($stmt);
    $lastItemId = $row ? (string)($row['item_id'] ?? '') : '';
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
    $pathsList = [];
    if (!empty($data['item_image_paths']) && is_array($data['item_image_paths'])) {
        foreach ($data['item_image_paths'] as $p) {
            $t = trim((string)$p);
            if ($t !== '') {
                $pathsList[] = $t;
            }
        }
        $pathsList = array_values(array_unique($pathsList));
    } else {
        $single = inventory_empty_string_to_null($data['item_image_path'] ?? null);
        if ($single !== null) {
            $pathsList = [$single];
        }
    }
    $itemImagePathsJson = inventory_item_image_paths_to_json($pathsList);
    $itemImagePath = inventory_empty_string_to_null($pathsList[0] ?? null);
    $dateArrived = inventory_empty_string_to_null($data['date_arrived'] ?? null);

    $stmt = $conn->prepare("
        INSERT INTO inventory_items (item_id, item_name, description, `type`, item_condition, remarks, item_image_path, item_image_paths, date_arrived)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        error_log('createInventoryItem prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param(
        'sssssssss',
        $itemId,
        $data['item_name'],
        $data['description'],
        $data['type'],
        $data['item_condition'],
        $data['remarks'],
        $itemImagePath,
        $itemImagePathsJson,
        $dateArrived
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
    $row = inventory_stmt_fetch_one_assoc($stmtGet);
    if ($row) {
        $currentItemId = (string)($row['item_id'] ?? '');
    }
    $stmtGet->close();

    $expectedPrefix = $prefixes[$data['item_name']];
    $finalItemId = (strpos($currentItemId, $expectedPrefix) === 0 && $currentItemId !== '')
        ? $currentItemId
        : generateInventoryItemId($conn, $expectedPrefix);

    $pathsList = [];
    if (!empty($data['item_image_paths']) && is_array($data['item_image_paths'])) {
        foreach ($data['item_image_paths'] as $p) {
            $t = trim((string)$p);
            if ($t !== '') {
                $pathsList[] = $t;
            }
        }
        $pathsList = array_values(array_unique($pathsList));
    } else {
        $single = inventory_empty_string_to_null($data['item_image_path'] ?? null);
        if ($single !== null) {
            $pathsList = [$single];
        }
    }
    $itemImagePathsJson = inventory_item_image_paths_to_json($pathsList);
    $itemImagePath = inventory_empty_string_to_null($pathsList[0] ?? null);
    $dateArrived = inventory_empty_string_to_null($data['date_arrived'] ?? null);

    $stmt = $conn->prepare("
        UPDATE inventory_items
        SET item_id = ?, item_name = ?, description = ?, `type` = ?, item_condition = ?, remarks = ?, item_image_path = ?, item_image_paths = ?, date_arrived = ?
        WHERE id = ?
    ");
    if (!$stmt) {
        error_log('updateInventoryItem prepare failed: ' . $conn->error);
        return false;
    }
    $stmt->bind_param(
        'sssssssssi',
        $finalItemId,
        $data['item_name'],
        $data['description'],
        $data['type'],
        $data['item_condition'],
        $data['remarks'],
        $itemImagePath,
        $itemImagePathsJson,
        $dateArrived,
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
        SELECT id, item_id, item_name, description, `type` AS type, item_condition, remarks, item_image_path, item_image_paths, date_arrived
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
        SELECT id, item_id, item_name, description, `type` AS type, item_condition, remarks, item_image_path, item_image_paths, date_arrived
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
    $item = inventory_stmt_fetch_one_assoc($stmt);
    $stmt->close();
    return $item;
}
