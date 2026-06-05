<?php

$src = file_get_contents(__DIR__.'/../legacy/admin/staff-add.php');
if (! preg_match('/<form method="POST".*?<\/form>/s', $src, $m)) {
    fwrite(STDERR, "form not found\n");
    exit(1);
}

$form = $m[0];
$form = str_replace('method="POST" action=""', 'method="POST" action="{{ route(\'admin.staff.store\') }}"', $form);
$form = preg_replace(
    '/<\?php echo htmlspecialchars\(\$_POST\[\'([^\']+)\'\] \?\? \'\'\); \?>/',
    '{{ old(\'$1\', \'\') }}',
    $form
);
$form = preg_replace(
    '/htmlspecialchars\(preg_replace\(\'\/\^09\/\', \'\', \$_POST\[\'([^\']+)\'\] \?\? \'\'\)\)/',
    "{{ preg_replace('/^09/', '', old('$1', '')) }}",
    $form
);
$form = preg_replace(
    '/\(isset\(\$_POST\[\'([^\']+)\'\]\) && \$_POST\[\'\1\'\] === \'([^\']+)\'\) \? \'selected\' : \'\'/',
    "@selected(old('$1') === '$2')",
    $form
);
$form = preg_replace(
    '/\(!isset\(\$_POST\[\'status\'\]\) \|\| \$_POST\[\'status\'\] === \'Active\'\) \? \'selected\'/',
    "@selected(old('status', 'Active') === 'Active')",
    $form
);
$form = preg_replace(
    '/\(isset\(\$_POST\[\'status\'\]\) && \$_POST\[\'status\'\] === \'Inactive\'\) \? \'selected\'/',
    "@selected(old('status') === 'Inactive')",
    $form
);
$form = preg_replace(
    '/\(isset\(\$_POST\[\'employment_type\'\]\) \? \(int\)\$_POST\[\'employment_type\'\] : 0\)/',
    '(int) old(\'employment_type\', 0)',
    $form
);
$form = preg_replace(
    '/\(isset\(\$_POST\[\'emergency_same_as_primary\'\]\) && \(string\)\$_POST\[\'emergency_same_as_primary\'\] === \'1\'\) \? \'checked\'/',
    "@checked(old('emergency_same_as_primary') == '1')",
    $form
);
$form = preg_replace(
    '/!empty\(\$_POST\[\'performance_review_supervisor\'\]\) \? \'checked\'/',
    "@checked(old('performance_review_supervisor'))",
    $form
);

// Department foreach -> blade
$form = preg_replace(
    '/<\?php\s+\$selectedDept = \$_POST\[\'department\'\] \?\? \'\';\s+foreach \(\$departmentOptions as \$deptName\):\s+\?>\s+<option value="<\?php echo htmlspecialchars\(\$deptName\); \?>" <\?php echo \(\$selectedDept === \$deptName\) \? \'selected\' : \'\'; \?>>\s+<\?php echo htmlspecialchars\(\$deptName\); \?>\s+<\/option>\s+<\?php endforeach; \?>/s',
    '@foreach($departmentOptions as $deptName)
                                    <option value="{{ $deptName }}" @selected(old(\'department\') === $deptName)>{{ $deptName }}</option>
                                @endforeach',
    $form
);

$form = preg_replace(
    '/<\?php\s+\$selectedTypeId = isset\(\$_POST\[\'employment_type\'\]\) \? \(int\)\$_POST\[\'employment_type\'\] : 0;\s+foreach \(\$employmentTypeOptions as \$type\):\s+\?>\s+<option value="<\?php echo \(int\)\$type\[\'id\'\]; \?>" <\?php echo \(\$selectedTypeId === \(int\)\$type\[\'id\'\]\) \? \'selected\' : \'\'; \?>>\s+<\?php echo htmlspecialchars\(\$type\[\'name\'\]\); \?>\s+<\/option>\s+<\?php endforeach; \?>/s',
    '@foreach($employmentTypeOptions as $type)
                                    <option value="{{ $type[\'id\'] }}" @selected((int) old(\'employment_type\', 0) === (int) $type[\'id\'])>{{ $type[\'name\'] }}</option>
                                @endforeach',
    $form
);

$form = preg_replace(
    '/value="<\?php echo htmlspecialchars\(\$nextEmployeeId\); \?>" readonly/',
    'value="{{ $nextEmployeeId }}" readonly',
    $form
);

$form = str_replace('href="staff"', 'href="{{ route(\'admin.staff.index\') }}"', $form);
$form = "@csrf\n".$form;

$out = __DIR__.'/../resources/views/admin/staff/_form.blade.php';
file_put_contents($out, $form);
echo "Wrote {$out} (".strlen($form)." bytes)\n";
