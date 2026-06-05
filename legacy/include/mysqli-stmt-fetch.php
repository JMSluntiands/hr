<?php

/**
 * mysqli_stmt::get_result() needs mysqlnd — often missing on shared hosts (e.g. Hostinger).
 * Use store_result + bind_result after execute() on SELECT statements.
 */

if (!function_exists('hr_stmt_fetch_one_assoc')) {
    /**
     * @return array<string, mixed>|null
     */
    function hr_stmt_fetch_one_assoc(mysqli_stmt $stmt): ?array
    {
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
        foreach ($row as $key => $value) {
            $out[$key] = $value;
        }

        return $out;
    }
}

if (!function_exists('hr_stmt_fetch_all_assoc')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function hr_stmt_fetch_all_assoc(mysqli_stmt $stmt): array
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
            foreach ($template as $key => $value) {
                $copy[$key] = $value;
            }
            $rows[] = $copy;
        }

        return $rows;
    }
}
