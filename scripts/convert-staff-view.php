<?php

$lines = file(__DIR__.'/../legacy/admin/staff-view.php');
$body = implode('', array_slice($lines, 172, 580)); // lines 173-752 (1-based)

$body = preg_replace('/\<\?php include __DIR__ \. \'\/include\/sidebar-admin\.php\'; \?\>/', '', $body);
$body = str_replace('staff-edit.php?id=<?php echo $employeeId; ?>', "{{ route('admin.staff.edit', $employeeId) }}", $body);
$body = str_replace('href="staff"', 'href="{{ route(\'admin.staff.index\') }}"', $body);
$body = str_replace('staff-add-document.php?id=<?php echo (int)$employeeId; ?>', "{{ url('/admin/staff-add-document.php?id='.$employeeId) }}", $body);

// Remove php blocks for staffDocumentAdded - handle in wrapper
$body = preg_replace('/\<\?php if \(\$staffDocumentAdded\): \?\>.*?\<\?php endif; \?\>/s', '@if($staffDocumentAdded){{--flash--}}@endif', $body);

// Simple echo htmlspecialchars($employee['key'])
$body = preg_replace(
    '/\<\?php echo htmlspecialchars\(\$employee\[\'([^\']+)\'\] \?\? \'([^\']*)\'\); \?\>/',
    '{{ $employee->$1 ?? \'$2\' }}',
    $body
);
$body = preg_replace(
    '/\<\?php echo htmlspecialchars\(\$employee\[\'([^\']+)\'\] \?\? \'\'\); \?\>/',
    '{{ $employee->$1 ?? \'\' }}',
    $body
);
$body = preg_replace(
    '/\<\?php echo strtoupper\(substr\(\$employee\[\'full_name\'\] \?\? \'\?\', 0, 1\)\); \?\>/',
    '{{ strtoupper(substr($employee->full_name ?? \'?\', 0, 1)) }}',
    $body
);

file_put_contents(__DIR__.'/../storage/staff-view-converted.html', $body);
echo "Wrote partial conversion - manual blade still needed\n";
