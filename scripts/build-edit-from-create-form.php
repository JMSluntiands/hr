<?php

$form = file_get_contents(__DIR__.'/../resources/views/admin/staff/_form.blade.php');
$form = str_replace("route('admin.staff.store')", "route('admin.staff.update', \$employee->id)", $form);
$form = str_replace("@csrf\n", "@csrf\n@method('PUT')\n<input type=\"hidden\" name=\"id\" value=\"{{ \$employee->id }}\">\n", $form);

// Remove employee ID preview block
$form = preg_replace(
    '/<div class="md:col-span-2">\s*<label[^>]+>Employee ID.*?<\/div>\s*/s',
    '',
    $form
);

// old('field', '') -> old('field', $employee->field ?? '')
$form = preg_replace(
    "/old\('([^']+)', ''\)/",
    "old('$1', \$employee->$1 ?? '')",
    $form
);
$form = preg_replace(
    "/old\('([^']+)', '0'\)/",
    "old('$1', \$employeeCompensation->$1 ?? 0)",
    $form
);
$form = str_replace("old('phone', '')", "preg_replace('/^09/', '', old('phone', \$employee->phone ?? ''))", $form);
$form = str_replace("old('emergency_contact_phone', '')", "preg_replace('/^09/', '', old('emergency_contact_phone', \$employee->emergency_contact_phone ?? ''))", $form);

// Department / employment loops already use $departmentOptions - good
// Fix @selected for gender/status - create form uses old only; need employee defaults
$form = str_replace("@selected(old('gender') === 'Male')", "@selected(old('gender', \$employee->gender) === 'Male')", $form);
$form = str_replace("@selected(old('gender') === 'Female')", "@selected(old('gender', \$employee->gender) === 'Female')", $form);
$form = str_replace("@selected(old('department') ===", "@selected(old('department', \$employee->department) ===", $form);
$form = str_replace("old('employment_type', 0)", "old('employment_type', \$employee->employment_type_id ?? 0)", $form);
$form = str_replace("@selected(old('status', 'Active') === 'Active')", "@selected(old('status', \$employee->status ?? 'Active') === 'Active')", $form);
$form = str_replace("@selected(old('status') === 'Inactive')", "@selected(old('status', \$employee->status) === 'Inactive')", $form);
$form = str_replace("@checked(old('emergency_same_as_primary') == '1')", "@checked(\$isEmergencySameAsPrimary ?? false)", $form);
$form = str_replace("@checked(old('performance_review_supervisor'))", "@checked(old('performance_review_supervisor', \$employee->performance_review_supervisor))", $form);

// Emergency relationship selected
$form = preg_replace(
    "/@selected\(old\('emergency_contact_relationship'\) === '([^']+)'\)/",
    "@selected(old('emergency_contact_relationship', \$employee->emergency_contact_relationship) === '$1')",
    $form
);

$form = str_replace('Add Employee', 'Update Employee', $form);
$form = str_replace('route(\'admin.staff.index\')', "route('admin.staff.index')", $form);

// Insert inactive fields after performance review checkbox section - find performance_review_supervisor block end
$inactiveBlock = <<<'BLADE'

                        @php $selStatus = old('status', $employee->status ?? 'Active'); @endphp
                        <div id="inactiveEmploymentFields" class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-dashed border-slate-200 mt-2 {{ $selStatus === 'Inactive' ? '' : 'hidden' }}">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Date inactive <span class="text-amber-600">*</span></label>
                                <input type="date" name="date_inactive" id="date_inactive" value="{{ old('date_inactive', $employee->date_inactive) }}"
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Resignation letter <span class="text-amber-600">*</span></label>
                                <input type="file" name="resignation_letter" id="resignation_letter" accept=".pdf,.jpg,.jpeg,.png"
                                       class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-800">
                                <p class="text-xs text-slate-500 mt-1">{{ $hasResignationOnFile ? 'Leave empty to keep current file.' : 'Required when Inactive.' }}</p>
                                @if($hasResignationOnFile)
                                <a href="{{ \App\Services\StaffProfileService::uploadUrl($employee->resignation_letter_path) }}" target="_blank" class="text-xs text-amber-700 font-medium hover:underline mt-1 inline-block">View current resignation letter</a>
                                @endif
                            </div>
                        </div>
BLADE;

$form = preg_replace(
    '/(<input type="checkbox" name="performance_review_supervisor".*?<\/label>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>)/s',
    '$1'.$inactiveBlock,
    $form,
    1
);

// Remove signature file field in edit (legacy edit doesn't have it)
$form = preg_replace('/<div class="md:col-span-3">\s*<label[^>]+>Signature.*?<\/div>\s*/s', '', $form);

file_put_contents(__DIR__.'/../resources/views/admin/staff/partials/_edit-form.blade.php', $form);
echo strlen($form);
