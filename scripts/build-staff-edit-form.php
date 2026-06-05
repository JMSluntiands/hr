<?php

$src = file_get_contents(__DIR__.'/../legacy/admin/staff-edit.php');
if (! preg_match('/<form method="POST".*?<\/form>/s', $src, $m)) {
    exit(1);
}
$form = $m[0];
$form = str_replace('method="POST" action=""', 'method="POST" action="{{ route(\'admin.staff.update\', $employee->id) }}"', $form);
$form = "@csrf\n@method('PUT')\n".$form;
$form = preg_replace('/value="<\?php echo \(int\)\$employee\[\'id\'\]; \?>"/', 'value="{{ $employee->id }}"', $form);
$form = preg_replace('/htmlspecialchars\(\$_POST\[\'([^\']+)\'\] \?\? \(\$employee\[\'\1\'\] \?\? \'\'\)\)/', "old('$1', \$employee->$1 ?? '')", $form);
$form = preg_replace('/htmlspecialchars\(\$_POST\[\'([^\']+)\'\] \?\? \(\$employeeCompensation\[\'([^\']+)\'\] \?\? 0\)\)/', "old('$1', \$employeeCompensation->$2 ?? 0)", $form);
$form = preg_replace('/htmlspecialchars\(preg_replace\(\'\/\^09\/\', \'\', \$_POST\[\'([^\']+)\'\] \?\? \(\$employee\[\'\1\'\] \?\? \'\'\)\)\)/', "{{ preg_replace('/^09/', '', old('$1', \$employee->$1 ?? '')) }}", $form);
$form = preg_replace('/href="staff"/', 'href="{{ route(\'admin.staff.index\') }}"', $form);
$form = preg_replace('/\.\.\/uploads\/<\?php echo htmlspecialchars\(\$employee\[\'resignation_letter_path\'\]\); \?>/', '{{ \\App\\Services\\StaffProfileService::uploadUrl($employee->resignation_letter_path) }}', $form);
$form = preg_replace('/<\?php echo \$hasResignationOnFile \? \'true\' : \'false\'; \?>/', '{{ $hasResignationOnFile ? \'true\' : \'false\' }}', $form);

// Remove PHP blocks for emergency address resolution - replace with blade vars passed from parent
$form = preg_replace(
    '/<\?php\s+\$resolvedEmergencyAddress.*?<\?php\s+\$isEmergencySameAsPrimary.*?\?>/s',
    '@php
    $resolvedEmergencyAddress = old(\'emergency_contact_address\', $employee->emergency_contact_address ?? \'\');
    $resolvedPrimaryAddress = old(\'address\', $employee->address ?? \'\');
    $isEmergencySameAsPrimary = old(\'emergency_same_as_primary\') !== null
        ? old(\'emergency_same_as_primary\') == \'1\'
        : (trim($resolvedEmergencyAddress) !== \'\' && trim($resolvedEmergencyAddress) === trim($resolvedPrimaryAddress));
@endphp',
    $form
);

$form = preg_replace('/htmlspecialchars\(\$resolvedEmergencyAddress\)/', '{{ $resolvedEmergencyAddress }}', $form);
$form = preg_replace('/<\?php echo \$isEmergencySameAsPrimary \? \'checked\' : \'\'; \?>/', '@checked($isEmergencySameAsPrimary)', $form);

// Status select
$form = preg_replace(
    '/<\?php \$selStatus = isset\(\$_POST\[\'status\'\]\) \? \$_POST\[\'status\'\] : \(\$employee\[\'status\'\] \?\? \'Active\'\); \?>/',
    '@php $selStatus = old(\'status\', $employee->status ?? \'Active\'); @endphp',
    $form
);
$form = preg_replace('/\(\$selStatus === \'Active\'\) \? \'selected\'/', "@selected(\$selStatus === 'Active')", $form);
$form = preg_replace('/\(\$selStatus === \'Inactive\'\) \? \'selected\'/', "@selected(\$selStatus === 'Inactive')", $form);

// inactive fields visibility
$form = preg_replace(
    '/class="md:col-span-2 grid[^"]*<\?php echo \(\$selStatus === \'Inactive\'\) \? \'\' : \'hidden\'; \?>/',
    'class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-dashed border-slate-200 mt-2 {{ $selStatus === \'Inactive\' ? \'\' : \'hidden\' }}"',
    $form
);

// Department loop
$form = preg_replace(
    '/<\?php\s+\$selDept = isset\(\$_POST\[\'department\'\]\) \? \$_POST\[\'department\'\] : \(\$employee\[\'department\'\] \?\? \'\'\);\s+foreach \(\$departmentOptions as \$deptName\):\s+\?>\s+<option value="<\?php echo htmlspecialchars\(\$deptName\); \?>" <\?php echo \(\$selDept === \$deptName\) \? \'selected\' : \'\'; \?>>\s+<\?php echo htmlspecialchars\(\$deptName\); \?>\s+<\/option>\s+<\?php endforeach; \?>/s',
    '@foreach($departmentOptions as $deptName)<option value="{{ $deptName }}" @selected(old(\'department\', $employee->department) === $deptName)>{{ $deptName }}</option>@endforeach',
    $form
);

// Employment type loop
$form = preg_replace(
    '/<\?php\s+\$currentEmploymentTypeId = \(isset\(\$_POST\[\'employment_type\'\]\).*?endforeach; \?>/s',
    '@foreach($employmentTypeOptions as $type)<option value="{{ $type[\'id\'] }}" @selected((int) old(\'employment_type\', $employee->employment_type_id ?? 0) === (int) $type[\'id\'])>{{ $type[\'name\'] }}</option>@endforeach',
    $form
);

// perf supervisor
$form = preg_replace(
    '/<\?php\s+\$perfSupChecked = isset\(\$_POST\[\'performance_review_supervisor\'\]\).*?\?>\s+<label class="flex items-start.*?<input type="checkbox" name="performance_review_supervisor" value="1"[^>]+<\?php echo \$perfSupChecked \? \'checked\' : \'\'; \?>/s',
    '<label class="flex items-start gap-3 cursor-pointer rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3"><input type="checkbox" name="performance_review_supervisor" value="1" class="mt-1 rounded border-slate-300 text-amber-600 focus:ring-amber-500" @checked(old(\'performance_review_supervisor\', $employee->performance_review_supervisor))>',
    $form
);

$form = preg_replace('/<\?php echo \$hasResignationOnFile \? \'Leave empty.*?\?>/', '{{ $hasResignationOnFile ? \'Leave empty to keep the current file on record.\' : \'Upload required for Inactive.\' }}', $form);
$form = preg_replace('/<\?php if \(\$hasResignationOnFile\): \?>/', '@if($hasResignationOnFile)', $form);
$form = preg_replace('/<\?php endif; \?>/', '@endif', $form);
$form = preg_replace('/<\?php echo htmlspecialchars\(\$postDateInactive\); \?>/', '{{ old(\'date_inactive\', $employee->date_inactive) }}', $form);
$form = preg_replace('/<\?php\s+\$hasResignationOnFile.*?<\?php\s+\$postDateInactive.*?\?>/s', '', $form);

// gender options - simplify
$form = preg_replace(
    '/<option value="Male" <\?php echo \(\(isset\(\$_POST\[\'gender\'\]\).*?>Male<\/option>/',
    '<option value="Male" @selected(old(\'gender\', $employee->gender) === \'Male\')>Male</option>',
    $form
);
$form = preg_replace(
    '/<option value="Female" <\?php echo \(\(isset\(\$_POST\[\'gender\'\]\).*?>Female<\/option>/',
    '<option value="Female" @selected(old(\'gender\', $employee->gender) === \'Female\')>Female</option>',
    $form
);

// emergency relationship options - batch replace selected php
$form = preg_replace(
    '/<option value="([^"]+)" <\?php echo \(\(isset\(\$_POST\[\'emergency_contact_relationship\'\]\)[^>]+\?>>/',
    '<option value="$1" @selected(old(\'emergency_contact_relationship\', $employee->emergency_contact_relationship) === \'$1\')>',
    $form
);

// monthly preview values
$form = preg_replace(
    '/value="<\?php echo number_format\(\(float\)\(\$employeeCompensation\[\'basic_salary_monthly\'\] \?\? 0\), 2, \'.\', \'\'\); \?>"/',
    'value="{{ number_format((float) old(\'basic_salary_daily\', $employeeCompensation->basic_salary_daily ?? 0) * 26, 2, \'.\', \'\') }}"',
    $form
);
$form = preg_replace(
    '/value="<\?php echo number_format\(\(float\)\(\$employeeCompensation\[\'basic_salary_annually\'\] \?\? 0\), 2, \'.\', \'\'\); \?>"/',
    'value="{{ number_format((float) old(\'basic_salary_daily\', $employeeCompensation->basic_salary_daily ?? 0) * 26 * 12, 2, \'.\', \'\') }}"',
    $form
);

file_put_contents(__DIR__.'/../resources/views/admin/staff/partials/_edit-form.blade.php', $form);
echo "done ".strlen($form)."\n";
