<?php

/**
 * ID card is only issued for EMPLOYEE type.
 * Prefer master employment_types.name; if empty, use employee_compensation.employment_type.
 */
function hr_employee_is_regular_for_id_card(array $row): bool
{
    $master = trim((string)($row['employment_type_name'] ?? ''));
    $comp   = trim((string)($row['compensation_employment_type'] ?? ''));
    if ($master !== '') {
        return strcasecmp($master, 'EMPLOYEE') === 0;
    }
    return $comp !== '' && strcasecmp($comp, 'EMPLOYEE') === 0;
}
