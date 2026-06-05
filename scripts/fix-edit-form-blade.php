<?php

$f = file_get_contents(__DIR__.'/../resources/views/admin/staff/partials/_edit-form.blade.php');

$f = preg_replace(
    '/value="<\?php echo old\(\'([^\']+)\', \$employee->\1 \?\? \'\'\); \?>"/',
    'value="{{ old(\'$1\', $employee->$1 ?? \'\') }}"',
    $f
);
$f = preg_replace(
    '/value="<\?php echo old\(\'([^\']+)\', \$employeeCompensation->\1 \?\? 0\); \?>"/',
    'value="{{ old(\'$1\', $employeeCompensation->$1 ?? 0) }}"',
    $f
);
$f = preg_replace(
    '/value="<\?php echo \{\{ preg_replace\(\'\/\^09\/\', \'\', old\(\'([^\']+)\', \$employee->\1 \?\? \'\'\)\) \}\}; \?>"/',
    'value="{{ preg_replace(\'/^09/\', \'\', old(\'$1\', $employee->$1 ?? \'\')) }}"',
    $f
);
$f = preg_replace(
    '/>\<\?php echo old\(\'([^\']+)\', \$employee->\1 \?\? \'\'\); \?><\/textarea>/',
    '>{{ old(\'$1\', $employee->$1 ?? \'\') }}</textarea>',
    $f
);
$f = str_replace('><?php echo {{ $resolvedEmergencyAddress }}; ?></textarea>', '>{{ $resolvedEmergencyAddress }}</textarea>', $f);
$f = str_replace("<?php if (!empty(\$employee['id'])): ?>\n", '', $f);
$f = str_replace('<option value="Active" <?php echo @selected($selStatus === \'Active\') : \'\'; ?>>', '<option value="Active" @selected($selStatus === \'Active\')>', $f);
$f = str_replace('<option value="Inactive" <?php echo @selected($selStatus === \'Inactive\') : \'\'; ?>>', '<option value="Inactive" @selected($selStatus === \'Inactive\')>', $f);
$f = preg_replace('/<\?php\s+\$hasResignationOnFile.*?<\?php\s+\$postDateInactive.*?\?>\s*/s', '', $f);
$f = str_replace("hidden' }}\"\"", "hidden' }}", $f);

if (! str_contains($f, 'resolvedEmergencyAddress = old')) {
    $f = preg_replace(
        '/<\?php\s+\$resolvedEmergencyAddress.*?@endphp/s',
        '@php
    $resolvedEmergencyAddress = old(\'emergency_contact_address\', $employee->emergency_contact_address ?? \'\');
    $resolvedPrimaryAddress = old(\'address\', $employee->address ?? \'\');
    $isEmergencySameAsPrimary = old(\'emergency_same_as_primary\') !== null
        ? old(\'emergency_same_as_primary\') == \'1\'
        : (trim($resolvedEmergencyAddress) !== \'\' && trim($resolvedEmergencyAddress) === trim($resolvedPrimaryAddress));
@endphp',
        $f,
        1
    );
}

file_put_contents(__DIR__.'/../resources/views/admin/staff/partials/_edit-form.blade.php', $f);
echo "remaining php: ".substr_count($f, '<?php')."\n";
