@csrf
@php
    $signatureFileInputClass = 'block w-full max-w-md text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100';
    $stripPhonePrefix = static function ($value): string {
        $digits = (string) $value;
        return str_starts_with($digits, '09') ? substr($digits, 2) : $digits;
    };
    $phoneDisplay = $stripPhonePrefix(old('phone', ''));
    $emergencyPhoneDisplay = $stripPhonePrefix(old('emergency_contact_phone', ''));
@endphp
<form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-0" id="employeeForm" enctype="multipart/form-data" novalidate>
                <!-- Personal Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-gradient-to-b from-slate-50/80 to-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
                            <p class="text-sm text-slate-500">Basic details and contact information</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Employee ID</label>
                            <input type="text" value="{{ $nextEmployeeId }}" readonly
                                   class="w-full max-w-xs px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 font-mono text-sm cursor-not-allowed">
                            <p class="text-xs text-slate-500 mt-1.5">Auto-generated when you submit.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-amber-600">*</span></label>
                            <input type="text" name="full_name" id="full_name" required 
                                   value="{{ old('full_name', '') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                   minlength="3" placeholder="Juan Dela Cruz">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-amber-600">*</span></label>
                            <input type="email" name="email" id="email" required 
                                   value="{{ old('email', '') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                   placeholder="name@company.com">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number <span class="text-amber-600">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">09</span>
                                <input type="tel" name="phone" id="phone" required
                                       value="{{ $phoneDisplay }}"
                                       pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                       data-hr-digits-max="9"
                                       class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">09 + 9 digits</p>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate"
                                   value="{{ old('birthdate', '') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                            <select name="gender" id="gender" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Gender</option>
                                <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                                <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Signature (optional)</label>
                            <input type="file" name="employee_signature" id="employee_signature" accept="image/png"
                                   class="{{ $signatureFileInputClass }}">
                            <p class="text-xs text-slate-500 mt-1.5">PNG only, max 2MB. Used on forms and profile like the employee self-upload.</p>
                        </div>
                        <!-- Workplaces -->
                        <div class="md:col-span-3 p-4 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">Work Locations</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Primary Workplace</label>
                                    <textarea name="address" id="address" rows="2"
                                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow resize-none"
                                              placeholder="Primary work location / address">{{ old('address', '') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Secondary Workplace</label>
                                    <textarea name="secondary_workplace" id="secondary_workplace" rows="2"
                                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow resize-none"
                                              placeholder="Optional second work location">{{ old('secondary_workplace', '') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <!-- Emergency Contact -->
                        <div class="md:col-span-3 p-4 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">Emergency Contact</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Person</label>
                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                           value="{{ old('emergency_contact_name', '') }}"
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                           placeholder="Full name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Relationship</label>
                                    <select name="emergency_contact_relationship" id="emergency_contact_relationship" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                        <option value="">Select</option>
                                        <option value="Spouse" @selected(old('emergency_contact_relationship') === 'Spouse')>Spouse</option>
                                        <option value="Partner" @selected(old('emergency_contact_relationship') === 'Partner')>Partner</option>
                                        <option value="Parent" @selected(old('emergency_contact_relationship') === 'Parent')>Parent</option>
                                        <option value="Grandmother" @selected(old('emergency_contact_relationship') === 'Grandmother')>Grandmother</option>
                                        <option value="Grandfather" @selected(old('emergency_contact_relationship') === 'Grandfather')>Grandfather</option>
                                        <option value="Sibling" @selected(old('emergency_contact_relationship') === 'Sibling')>Sibling</option>
                                        <option value="Child" @selected(old('emergency_contact_relationship') === 'Child')>Child</option>
                                        <option value="Friend" @selected(old('emergency_contact_relationship') === 'Friend')>Friend</option>
                                        <option value="Other" @selected(old('emergency_contact_relationship') === 'Other')>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">09</span>
                                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                               value="{{ $emergencyPhoneDisplay }}"
                                               pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                               data-hr-digits-max="9"
                                               class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1.5">09 + 9 digits (optional)</p>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 mb-2">
                                        <input type="checkbox" id="emergencySameAsPrimary" name="emergency_same_as_primary" value="1" @if(old('emergency_same_as_primary') == '1') checked @endif class="rounded border-slate-300 text-amber-600 focus:ring-amber-500/30">
                                        <span>Same as primary address</span>
                                    </label>
                                    <div id="emergencyAddressWrap" class="mt-1">
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Emergency Contact Address</label>
                                        <textarea name="emergency_contact_address" id="emergency_contact_address" rows="2"
                                                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow resize-none"
                                                  placeholder="Enter emergency contact address">{{ old('emergency_contact_address', '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Employment Information</h2>
                            <p class="text-sm text-slate-500">Position, department, and start date</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Position <span class="text-amber-600">*</span></label>
                            <input type="text" name="position" id="position" required 
                                   value="{{ old('position', '') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Department <span class="text-amber-600">*</span></label>
                            <select name="department" id="department" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Department</option>
                                @foreach($departmentOptions as $deptName)
                                    <option value="{{ $deptName }}" @selected(old('department') === $deptName)>{{ $deptName }}</option>
                                @endforeach
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Employment Type <span class="text-amber-600">*</span></label>
                            <select name="employment_type" id="employment_type" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Employment Type</option>
                                @foreach($employmentTypeOptions as $type)
                                    <option value="{{ $type['id'] }}" @selected((int) old('employment_type', 0) === (int) $type['id'])>{{ $type['name'] }}</option>
                                @endforeach
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date Hired <span class="text-amber-600">*</span></label>
                            <input type="date" name="date_hired" id="date_hired" required 
                                   value="{{ old('date_hired', '') }}"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Status <span class="text-amber-600">*</span></label>
                            <select name="status" id="status" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                                <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <input type="checkbox" name="performance_review_supervisor" value="1" class="mt-1 rounded border-slate-300 text-amber-600 focus:ring-amber-500" @if(old('performance_review_supervisor')) checked @endif>
                                <span>
                                    <span class="block text-sm font-medium text-slate-800">Performance review supervisor</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">When enabled (and the department uses performance review), this employee sees <strong>Form Review</strong> in the employee portal to evaluate staff and view submissions that name them as supervisor.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Government Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Government Information</h2>
                            <p class="text-sm text-slate-500">SSS, PhilHealth, Pag-IBIG, TIN, and clearances</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">SSS Number</label>
                            <input type="text" name="sss" id="sss" 
                                   value="{{ old('sss', '') }}"
                                   pattern="[0-9]{2}-[0-9]{7}-[0-9]" placeholder="XX-XXXXXXX-X" maxlength="13"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XX-XXXXXXX-X</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">PhilHealth Number</label>
                            <input type="text" name="philhealth" id="philhealth" 
                                   value="{{ old('philhealth', '') }}"
                                   pattern="[0-9]{2}-[0-9]{9}-[0-9]" placeholder="XX-XXXXXXXXX-X" maxlength="14"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XX-XXXXXXXXX-X</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Pag-IBIG (HDMF) Number</label>
                            <input type="text" name="pagibig" id="pagibig" 
                                   value="{{ old('pagibig', '') }}"
                                   pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}" placeholder="XXXX-XXXX-XXXX" maxlength="14"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XXXX-XXXX-XXXX</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">TIN Number</label>
                            <input type="text" name="tin" id="tin" 
                                   value="{{ old('tin', '') }}"
                                   pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}" placeholder="XXX-XXX-XXX-XXX" maxlength="15"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XXX-XXX-XXX-XXX</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">NBI Clearance</label>
                            <input type="text" name="nbi_clearance" id="nbi_clearance" 
                                   value="{{ old('nbi_clearance', '') }}"
                                   placeholder="Clearance number or reference"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Police Clearance</label>
                            <input type="text" name="police_clearance" id="police_clearance" 
                                   value="{{ old('police_clearance', '') }}"
                                   placeholder="Clearance number or reference"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                    </div>
                </div>

                <!-- Compensation Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-gradient-to-b from-slate-50/50 to-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Compensation Information</h2>
                            <p class="text-sm text-slate-500">Daily rate, allowances, and gross figures</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Daily Compensation</label>
                            <input type="number" name="basic_salary_daily" id="basic_salary_daily"
                                   value="{{ old('basic_salary_daily', '0') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Internet</label>
                            <input type="number" name="allowance_internet" id="allowance_internet"
                                   value="{{ old('allowance_internet', '0') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Meal</label>
                            <input type="number" name="allowance_meal" id="allowance_meal"
                                   value="{{ old('allowance_meal', '0') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Position</label>
                            <input type="number" name="allowance_position" id="allowance_position"
                                   value="{{ old('allowance_position', '0') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Transportation</label>
                            <input type="number" name="allowance_transportation" id="allowance_transportation"
                                   value="{{ old('allowance_transportation', '0') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Monthly Gross (Auto)</label>
                            <input type="text" id="basic_salary_monthly_preview" readonly
                                   value="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 cursor-not-allowed font-medium">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Annual Gross (Auto)</label>
                            <input type="text" id="basic_salary_annual_preview" readonly
                                   value="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 cursor-not-allowed font-medium">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">Auto-compute: Monthly = Daily × 26, Annual = Monthly × 12. Allowances are saved separately.</p>
                </div>

                <div class="p-6 md:p-8 flex flex-col sm:flex-row justify-end gap-3 bg-slate-50/80 border-t border-slate-200">
                    <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-white hover:border-slate-400 font-medium transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-medium shadow-sm hover:shadow transition-all">
                        Add Employee
                    </button>
                </div>
            </form>