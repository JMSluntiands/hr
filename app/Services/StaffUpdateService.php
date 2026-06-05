<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffUpdateService
{
    /**
     * @return object{
     *   basic_salary_monthly: float,
     *   basic_salary_daily: float,
     *   basic_salary_annually: float,
     *   allowance_internet: float,
     *   allowance_meal: float,
     *   allowance_position: float,
     *   allowance_transportation: float
     * }
     */
    public function loadCompensation(int $employeeId): object
    {
        $defaults = (object) [
            'basic_salary_monthly' => 0.0,
            'basic_salary_daily' => 0.0,
            'basic_salary_annually' => 0.0,
            'allowance_internet' => 0.0,
            'allowance_meal' => 0.0,
            'allowance_position' => 0.0,
            'allowance_transportation' => 0.0,
        ];

        if (! Schema::hasTable('employee_compensation')) {
            return $defaults;
        }

        $row = DB::table('employee_compensation')
            ->where('employee_id', $employeeId)
            ->first([
                'basic_salary_monthly',
                'basic_salary_daily',
                'basic_salary_annually',
                'allowance_internet',
                'allowance_meal',
                'allowance_position',
                'allowance_transportation',
            ]);

        if (! $row) {
            return $defaults;
        }

        foreach ($defaults as $key => $val) {
            $defaults->$key = (float) ($row->$key ?? 0);
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, message?: string, errors?: list<string>}
     */
    public function update(
        int $employeeId,
        array $input,
        ?UploadedFile $resignationLetter,
        int $adminUserId,
        string $adminName,
    ): array {
        LegacyDatabase::ensureStaffSchema();

        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return ['ok' => false, 'errors' => ['Employee not found.']];
        }

        $employmentTypes = (new StaffOnboardingService)->formOptions()['employmentTypes'];
        $compensation = $this->loadCompensation($employeeId);
        $errors = $this->validate($input, $employee, $resignationLetter);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $parsed = $this->parseInput($input, $employmentTypes, $employee);
        $previousMonthly = (float) $compensation->basic_salary_monthly;

        try {
            DB::transaction(function () use (
                $employeeId,
                $employee,
                $parsed,
                $resignationLetter,
                $previousMonthly,
                $adminUserId,
                $adminName
            ) {
                $dateInactive = null;
                $resignationPath = null;

                if ($parsed['status'] === 'Inactive') {
                    $dateInactive = $parsed['date_inactive'];
                    $existingPath = trim((string) ($employee->resignation_letter_path ?? ''));

                    if ($resignationLetter && $resignationLetter->getError() === UPLOAD_ERR_OK) {
                        $ext = strtolower($resignationLetter->getClientOriginalExtension());
                        $dir = base_path('uploads/employee_documents');
                        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
                            throw new \RuntimeException('Could not create upload directory.');
                        }
                        $filename = $employeeId.'_resignation_'.time().'.'.$ext;
                        $resignationLetter->move($dir, $filename);
                        $resignationPath = 'employee_documents/'.$filename;
                    } else {
                        $resignationPath = $existingPath !== '' ? $existingPath : null;
                    }
                }

                DB::table('employees')->where('id', $employeeId)->update([
                    'full_name' => $parsed['full_name'],
                    'email' => $parsed['email'],
                    'phone' => $parsed['phone'],
                    'position' => $parsed['position'],
                    'department' => $parsed['department'],
                    'employment_type_id' => $parsed['employment_type_id'],
                    'date_hired' => $parsed['date_hired'],
                    'status' => $parsed['status'],
                    'date_inactive' => $dateInactive,
                    'resignation_letter_path' => $resignationPath,
                    'address' => $parsed['address'],
                    'secondary_workplace' => $parsed['secondary_workplace'],
                    'emergency_contact_name' => $parsed['emergency_contact_name'],
                    'emergency_contact_phone' => $parsed['emergency_contact_phone'],
                    'emergency_contact_relationship' => $parsed['emergency_contact_relationship'],
                    'emergency_contact_address' => $parsed['emergency_contact_address'],
                    'birthdate' => $parsed['birthdate'] ?: null,
                    'gender' => $parsed['gender'] ?: null,
                    'sss' => $parsed['sss'],
                    'philhealth' => $parsed['philhealth'],
                    'pagibig' => $parsed['pagibig'],
                    'tin' => $parsed['tin'],
                    'nbi_clearance' => $parsed['nbi_clearance'],
                    'police_clearance' => $parsed['police_clearance'],
                    'performance_review_supervisor' => $parsed['performance_review_supervisor'],
                ]);

                $currentEmail = (string) $employee->email;
                if ($parsed['email'] !== $currentEmail && Schema::hasTable('user_login')) {
                    DB::table('user_login')->where('email', $currentEmail)->update(['email' => $parsed['email']]);
                }

                $this->ensureCompensationTable();
                $this->ensureSalaryAdjustmentsTable();

                $effectiveDate = $parsed['date_hired'] ?: now()->toDateString();
                $compExists = DB::table('employee_compensation')->where('employee_id', $employeeId)->exists();

                if ($compExists) {
                    DB::table('employee_compensation')->where('employee_id', $employeeId)->update([
                        'basic_salary_monthly' => $parsed['basic_salary_monthly'],
                        'basic_salary_daily' => $parsed['basic_salary_daily'],
                        'basic_salary_annually' => $parsed['basic_salary_annual'],
                        'effective_date' => $effectiveDate,
                        'allowance_internet' => $parsed['allowance_internet'],
                        'allowance_meal' => $parsed['allowance_meal'],
                        'allowance_position' => $parsed['allowance_position'],
                        'allowance_transportation' => $parsed['allowance_transportation'],
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('employee_compensation')->insert([
                        'employee_id' => $employeeId,
                        'basic_salary_monthly' => $parsed['basic_salary_monthly'],
                        'basic_salary_daily' => $parsed['basic_salary_daily'],
                        'basic_salary_annually' => $parsed['basic_salary_annual'],
                        'employment_type' => 'Regular',
                        'effective_date' => $effectiveDate,
                        'allowance_internet' => $parsed['allowance_internet'],
                        'allowance_meal' => $parsed['allowance_meal'],
                        'allowance_position' => $parsed['allowance_position'],
                        'allowance_transportation' => $parsed['allowance_transportation'],
                        'created_at' => now(),
                    ]);
                }

                if (abs($parsed['basic_salary_monthly'] - $previousMonthly) > 0.009) {
                    DB::table('employee_salary_adjustments')->insert([
                        'employee_id' => $employeeId,
                        'previous_salary' => $previousMonthly,
                        'new_salary' => $parsed['basic_salary_monthly'],
                        'reason' => 'Adjustment',
                        'approved_by' => $adminName,
                        'date_approved' => now()->toDateString(),
                        'created_at' => now(),
                    ]);
                }

                if (Schema::hasTable('activity_logs')) {
                    ActivityLog::query()->create([
                        'user_id' => $adminUserId,
                        'user_name' => $adminName,
                        'action' => 'Edit Employee',
                        'entity_type' => 'Employee',
                        'entity_id' => $employeeId,
                        'description' => "Updated employee: {$parsed['full_name']}",
                        'ip_address' => request()->ip(),
                        'created_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }

        return ['ok' => true, 'message' => 'Employee updated successfully.'];
    }

    /**
     * @return list<string>
     */
    private function validate(array $input, Employee $employee, ?UploadedFile $resignationLetter): array
    {
        $errors = [];
        $employeeId = (int) $employee->id;

        foreach ([
            'sss' => [10, 'SSS number must be 10 digits (format: XX-XXXXXXX-X)'],
            'pagibig' => [12, 'Pag-IBIG number must be 12 digits (format: XXXX-XXXX-XXXX)'],
            'philhealth' => [12, 'PhilHealth number must be 12 digits (format: XX-XXXXXXXXX-X)'],
            'tin' => [12, 'TIN number must be 12 digits (format: XXX-XXX-XXX-XXX)'],
        ] as $field => [$len, $msg]) {
            $val = trim((string) ($input[$field] ?? ''));
            if ($val !== '' && strlen(preg_replace('/[^0-9]/', '', $val)) !== $len) {
                $errors[] = $msg;
            }
        }

        $dailyInput = trim((string) ($input['basic_salary_daily'] ?? ''));
        if ($dailyInput !== '' && (! is_numeric($dailyInput) || (float) $dailyInput < 0)) {
            $errors[] = 'Daily compensation must be a valid non-negative number';
        }

        foreach ([
            'allowance_internet' => 'Internet allowance',
            'allowance_meal' => 'Meal allowance',
            'allowance_position' => 'Position allowance',
            'allowance_transportation' => 'Transportation allowance',
        ] as $field => $label) {
            $v = trim((string) ($input[$field] ?? '0'));
            if ($v !== '' && (! is_numeric($v) || (float) $v < 0)) {
                $errors[] = $label.' must be a valid non-negative number';
            }
        }

        if (trim((string) ($input['full_name'] ?? '')) === '') {
            $errors[] = 'Full Name is required';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email === '') {
            $errors[] = 'Email is required';
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (DB::table('employees')->where('email', $email)->where('id', '!=', $employeeId)->exists()) {
            $errors[] = 'Email already exists';
        } elseif ($email !== (string) $employee->email && Schema::hasTable('user_login')
            && DB::table('user_login')->where('email', $email)->exists()) {
            $errors[] = 'Email already exists in user accounts';
        }

        if (trim((string) ($input['position'] ?? '')) === '') {
            $errors[] = 'Position is required';
        }
        if (trim((string) ($input['department'] ?? '')) === '') {
            $errors[] = 'Department is required';
        }

        $postedType = isset($input['employment_type']) ? (string) $input['employment_type'] : '';
        $employmentTypeId = ($postedType !== '' && (int) $postedType > 0)
            ? (int) $postedType
            : (int) ($employee->employment_type_id ?? 0);
        if (! $employmentTypeId) {
            $errors[] = 'Employment type is required';
        }

        if (empty($input['date_hired'])) {
            $errors[] = 'Date Hired is required';
        }

        $phone = $this->normalizePhone((string) ($input['phone'] ?? ''));
        if ($phone === '') {
            $errors[] = 'Phone number is required';
        } elseif (! preg_match('/^09[0-9]{9}$/', $phone)) {
            $errors[] = 'Phone number must start with 09 and have 9 digits after (e.g., 09123456789)';
        }

        $status = (string) ($input['status'] ?? 'Active');
        $dateInactive = trim((string) ($input['date_inactive'] ?? ''));
        $existingResignation = trim((string) ($employee->resignation_letter_path ?? ''));

        if ($status === 'Inactive') {
            if ($dateInactive === '') {
                $errors[] = 'Date inactive is required when status is Inactive.';
            } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInactive)) {
                $errors[] = 'Invalid date inactive.';
            }

            $hasNew = $resignationLetter && $resignationLetter->getError() === UPLOAD_ERR_OK;
            if (! $hasNew && $existingResignation === '') {
                $errors[] = 'Please attach the resignation letter (PDF, JPG, or PNG, max 5MB).';
            }
            if ($hasNew) {
                $ext = strtolower($resignationLetter->getClientOriginalExtension());
                if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    $errors[] = 'Resignation letter must be PDF, JPG, JPEG, or PNG.';
                }
                if ($resignationLetter->getSize() > 5 * 1024 * 1024) {
                    $errors[] = 'Resignation letter must be 5MB or smaller.';
                }
            } elseif ($resignationLetter && $resignationLetter->getError() !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Error uploading resignation letter.';
            }
        }

        return $errors;
    }

    /**
     * @param  list<array{id: int, name: string}>  $employmentTypes
     * @return array<string, mixed>
     */
    private function parseInput(array $input, array $employmentTypes, Employee $employee): array
    {
        $postedType = isset($input['employment_type']) ? (string) $input['employment_type'] : '';
        $employmentTypeId = ($postedType !== '' && (int) $postedType > 0)
            ? (int) $postedType
            : (int) ($employee->employment_type_id ?? 0);

        $address = trim((string) ($input['address'] ?? ''));
        $emergencySame = isset($input['emergency_same_as_primary']) && (string) $input['emergency_same_as_primary'] === '1';
        $emergencyAddress = $emergencySame
            ? $address
            : trim((string) ($input['emergency_contact_address'] ?? ''));

        $daily = 0.0;
        $dailyInput = trim((string) ($input['basic_salary_daily'] ?? ''));
        if ($dailyInput !== '' && is_numeric($dailyInput)) {
            $daily = max(0, (float) $dailyInput);
        }
        $monthly = round($daily * 26, 2);
        $annual = round($monthly * 12, 2);

        return [
            'full_name' => trim((string) ($input['full_name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'phone' => $this->normalizePhone((string) ($input['phone'] ?? '')),
            'position' => trim((string) ($input['position'] ?? '')),
            'department' => trim((string) ($input['department'] ?? '')),
            'employment_type_id' => $employmentTypeId,
            'date_hired' => (string) ($input['date_hired'] ?? ''),
            'status' => (string) ($input['status'] ?? 'Active'),
            'date_inactive' => trim((string) ($input['date_inactive'] ?? '')),
            'address' => $address,
            'secondary_workplace' => trim((string) ($input['secondary_workplace'] ?? '')),
            'emergency_contact_name' => trim((string) ($input['emergency_contact_name'] ?? '')),
            'emergency_contact_phone' => $this->normalizePhone((string) ($input['emergency_contact_phone'] ?? '')),
            'emergency_contact_relationship' => trim((string) ($input['emergency_contact_relationship'] ?? '')),
            'emergency_contact_address' => $emergencyAddress,
            'birthdate' => (string) ($input['birthdate'] ?? ''),
            'gender' => (string) ($input['gender'] ?? ''),
            'sss' => trim((string) ($input['sss'] ?? '')),
            'philhealth' => trim((string) ($input['philhealth'] ?? '')),
            'pagibig' => trim((string) ($input['pagibig'] ?? '')),
            'tin' => trim((string) ($input['tin'] ?? '')),
            'nbi_clearance' => trim((string) ($input['nbi_clearance'] ?? '')),
            'police_clearance' => trim((string) ($input['police_clearance'] ?? '')),
            'performance_review_supervisor' => isset($input['performance_review_supervisor']) ? 1 : 0,
            'basic_salary_daily' => $daily,
            'basic_salary_monthly' => $monthly,
            'basic_salary_annual' => $annual,
            'allowance_internet' => (float) ($input['allowance_internet'] ?? 0),
            'allowance_meal' => (float) ($input['allowance_meal'] ?? 0),
            'allowance_position' => (float) ($input['allowance_position'] ?? 0),
            'allowance_transportation' => (float) ($input['allowance_transportation'] ?? 0),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', trim($phone)) ?? '';
        if ($phone !== '' && ! preg_match('/^09/', $phone)) {
            $phone = '09'.$phone;
        }

        return $phone;
    }

    private function ensureCompensationTable(): void
    {
        if (Schema::hasTable('employee_compensation')) {
            return;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `employee_compensation` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `basic_salary_monthly` decimal(10,2) DEFAULT NULL,
            `basic_salary_daily` decimal(10,2) DEFAULT NULL,
            `basic_salary_annually` decimal(10,2) DEFAULT NULL,
            `employment_type` enum('Regular','Contractual','Probationary','Part-time') DEFAULT 'Regular',
            `effective_date` date NOT NULL,
            `allowance_internet` decimal(10,2) DEFAULT 0.00,
            `allowance_meal` decimal(10,2) DEFAULT 0.00,
            `allowance_position` decimal(10,2) DEFAULT 0.00,
            `allowance_transportation` decimal(10,2) DEFAULT 0.00,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_employee_compensation` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureSalaryAdjustmentsTable(): void
    {
        if (Schema::hasTable('employee_salary_adjustments')) {
            return;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `previous_salary` decimal(10,2) NOT NULL,
            `new_salary` decimal(10,2) NOT NULL,
            `reason` enum('Promotion','Annual Increase','Adjustment','Other') DEFAULT 'Adjustment',
            `approved_by` varchar(255) DEFAULT NULL,
            `date_approved` date NOT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee_id` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
