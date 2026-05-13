<?php

/**
 * mysqli_stmt::get_result() only exists when PHP is built with mysqlnd.
 * Many shared hosts (e.g. Hostinger) ship mysqli without mysqlnd — calling get_result() fatals.
 * These helpers work after $stmt->execute() on a SELECT.
 */

/**
 * @return array|null
 */
function inventory_stmt_fetch_one_assoc(mysqli_stmt $stmt)
{
    // Always use store_result + bind_result: avoids mysqlnd/get_result edge cases on shared hosts.
    if (!$stmt->store_result()) {
        return null;
    }

    $meta = $stmt->result_metadata();
    if (!$meta) {
        return null;
    }

    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $bind[] = &$row[$field->name];
    }
    $meta->close();

    if ($bind === [] || !call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return null;
    }

    if (!$stmt->fetch()) {
        return null;
    }

    $out = [];
    foreach ($row as $k => $v) {
        $out[$k] = $v;
    }

    return $out;
}

function inventory_stmt_fetch_all_assoc(mysqli_stmt $stmt): array
{
    if (!$stmt->store_result()) {
        return [];
    }

    $meta = $stmt->result_metadata();
    if (!$meta) {
        return [];
    }

    $template = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $template[$field->name] = null;
        $bind[] = &$template[$field->name];
    }
    $meta->close();

    if ($bind === [] || !call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return [];
    }

    $rows = [];
    while ($stmt->fetch()) {
        $copy = [];
        foreach ($template as $k => $v) {
            $copy[$k] = $v;
        }
        $rows[] = $copy;
    }

    return $rows;
}
