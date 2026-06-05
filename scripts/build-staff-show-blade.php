<?php

use App\Services\StaffProfileService;

$src = file_get_contents(__DIR__.'/../legacy/admin/staff-view.php');
if (! preg_match('/<div class="max-w-5xl mx-auto space-y-6">(.*)<\/div>\s*<\/main>/s', $src, $m)) {
    fwrite(STDERR, "content block not found\n");
    exit(1);
}
$html = $m[1];

// Links
$html = str_replace('staff-edit.php?id=<?php echo $employeeId; ?>', '{{ route(\'admin.staff.edit\', $employeeId) }}', $html);
$html = str_replace('href="staff"', 'href="{{ route(\'admin.staff.index\') }}"', $html);
$html = preg_replace(
    '/staff-add-document\.php\?id=<\?php echo \(int\)\$employeeId; \?>/',
    "{{ url('/admin/staff-add-document.php?id=' . $employeeId) }}",
    $html
);

// Flash
$html = preg_replace(
    '/<\?php if \(\$staffDocumentAdded\): \?>.*?<\?php endif; \?>/s',
    "@if(\$staffDocumentAdded)\n        <div class=\"rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-3 text-emerald-800 text-sm mb-4\">\n            <svg class=\"w-5 h-5 flex-shrink-0 text-emerald-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\" /></svg>\n            <span class=\"font-medium\">Document added successfully.</span>\n        </div>\n        @endif",
    $html
);

// Photo / signature paths
$html = preg_replace(
    '/<\?php\s+\$photo = !empty\(\$employee\[\'profile_picture\'\]\) && file_exists\(__DIR__ \. \'\/\.\.\/uploads\/\' \. \$employee\[\'profile_picture\'\]\) \? \$employee\[\'profile_picture\'\] : null;\s+if \(\$photo\): \?>\s+<img src="\.\.\/uploads\/<\?php echo htmlspecialchars\(\$photo\); \?>"[^>]+>\s+<\?php else: \?>\s+<span[^>]+><\?php echo strtoupper\(substr\(\$employee\[\'full_name\'\] \?\? \'\?\', 0, 1\)\); \?><\/span>\s+<\?php endif; \?>/s',
    '@php $photo = $employee->profile_picture && \\App\\Services\\StaffProfileService::uploadExists($employee->profile_picture) ? $employee->profile_picture : null; @endphp
                            @if($photo)
                                <img src="{{ \\App\\Services\\StaffProfileService::uploadUrl($photo) }}" alt="" class="w-full h-full object-cover">
                            @else
                                <span class="text-3xl md:text-4xl font-bold text-amber-600">{{ strtoupper(substr($employee->full_name ?? \'?\', 0, 1)) }}</span>
                            @endif',
    $html
);

$html = preg_replace(
    '/<\?php\s+\$adminSigPath = !empty\(\$employee\[\'signature\'\]\) && file_exists\(__DIR__ \. \'\/\.\.\/uploads\/\' \. \$employee\[\'signature\'\]\)\s+\? \$employee\[\'signature\'\]\s+: null;\s+\?>\s+<\?php if \(\$adminSigPath\): \?>\s+<img src="\.\.\/uploads\/<\?php echo htmlspecialchars\(\$adminSigPath\); \?>"[^>]+>\s+<\?php else: \?>\s+<span[^>]+>No signature<\/span>\s+<\?php endif; \?>/s',
    '@if($employee->signature && \\App\\Services\\StaffProfileService::uploadExists($employee->signature))
                                <img src="{{ \\App\\Services\\StaffProfileService::uploadUrl($employee->signature) }}" alt="Employee signature" class="max-w-full max-h-full object-contain">
                            @else
                                <span class="text-slate-400 text-xs">No signature</span>
                            @endif',
    $html
);

// htmlspecialchars employee fields
$html = preg_replace('/<\?php echo htmlspecialchars\(\$employee\[\'([^\']+)\'\] \?\? \'([^\']*)\'\); \?>/', '{{ $employee->$1 ?? \'$2\' }}', $html);
$html = preg_replace('/<\?php echo htmlspecialchars\(\$employee\[\'([^\']+)\'\] \?\? \'\'\); \?>/', '{{ $employee->$1 ?? \'\' }}', $html);
$html = preg_replace('/<\?php echo htmlspecialchars\(\(string\)\$employee\[\'([^\']+)\'\]\); \?>/', '{{ $employee->$1 }}', $html);

// date fields employee
$html = preg_replace(
    '/<\?php echo !empty\(\$employee\[\'([^\']+)\'\]\) \? date\(\'M d, Y\', strtotime\(\$employee\[\'\1\'\]\)\) : \'N/A\'; \?>/',
    "@if(!empty(\$employee->$1)){{ \\Carbon\\Carbon::parse(\$employee->$1)->format('M d, Y') }}@else N/A @endif",
    $html
);
$html = preg_replace(
    '/<\?php echo !empty\(\$employee\[\'created_at\'\]\) \? date\(\'M d, Y H:i\', strtotime\(\$employee\[\'created_at\'\]\)\) : \'N/A\'; \?>/',
    "@if(!empty(\$employee->created_at)){{ \\Carbon\\Carbon::parse(\$employee->created_at)->format('M d, Y H:i') }}@else N/A @endif",
    $html
);

// employment type block
$html = preg_replace(
    '/<\?php\s+if \(\$employmentTypeName\) \{[^}]+\} elseif[^}]+\} else \{[^}]+\}\s+\?>/s',
    "@if(\$employmentTypeName){{ \$employmentTypeName }}@elseif(!empty(\$compensation->employment_type)){{ \$compensation->employment_type }}@else N/A @endif",
    $html
);

// inactive resignation link
$html = preg_replace(
    '/<\?php if \(\(\$employee\[\'status\'\] \?\? \'\'\) === \'Inactive\'\): \?>/',
    "@if(($employee->status ?? '') === 'Inactive')",
    $html
);
$html = preg_replace(
    '/<\?php if \(!empty\(\$employee\[\'resignation_letter_path\'\]\) && file_exists\(__DIR__ \. \'\/\.\.\/uploads\/\' \. \$employee\[\'resignation_letter_path\'\]\)\): \?>\s+<a href="\.\.\/uploads\/<\?php echo htmlspecialchars\(\$employee\[\'resignation_letter_path\'\]\); \?>"[^>]+>View file<\/a>\s+<\?php else: \?>\s+<p[^>]+>No file on record<\/p>\s+<\?php endif; \?>/s',
    '@if($employee->resignation_letter_path && \\App\\Services\\StaffProfileService::uploadExists($employee->resignation_letter_path))
                            <a href="{{ \\App\\Services\\StaffProfileService::uploadUrl($employee->resignation_letter_path) }}" target="_blank" rel="noopener" class="font-medium text-amber-700 hover:underline">View file</a>
                        @else
                            <p class="font-medium text-slate-600">No file on record</p>
                        @endif',
    $html
);
$html = str_replace('<?php endif; ?>', '@endif', $html);

// compensation section - replace php blocks at start
$html = preg_replace(
    '/<\?php\s+\/\/ Get current salary.*?<\?php endif; \?>\s+<\?php endif; \?>/s',
    '@include(\'admin.staff._show-compensation\')',
    $html
);

// Keep documents section - complex, include separate partial
$html = preg_replace(
    '/<!-- Documents Section -->.*$/s',
    "@include('admin.staff._show-documents')",
    $html
);

// salary modal foreach
$html = preg_replace(
    '/<!-- Salary Adjustments Modal -->.*?<!-- Documents Section -->/s',
    "@include('admin.staff._show-adjustments-modal')\n\n        ",
    $html
);

$wrapper = <<<'BLADE'
@extends('layouts.admin')
@section('title', 'View Employee')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
BLADE;

$out = $wrapper.$html."\n</div>\n@endsection\n@push('scripts')\n<script src=\"{{ asset('assets/js/admin-staff-view.js') }}\"></script>\n@endpush\n";

file_put_contents(__DIR__.'/../resources/views/admin/staff/show.blade.php', $out);
echo "Wrote show.blade.php (".strlen($out)." bytes)\n";

// Extract compensation block from original for partial
if (preg_match('/<!-- Compensation Details Card -->(.*?)<!-- Salary Adjustments Modal -->/s', $src, $cm)) {
    file_put_contents(__DIR__.'/../storage/comp-raw.html', $cm[1]);
}
