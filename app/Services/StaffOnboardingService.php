<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffOnboardingService
{
    /**
     * @return array{departments: list<string>, employmentTypes: list<array{id: int, name: string}>}
     */
    public function formOptions(): array
    {
        LegacyDatabase::ensureStaffSchema();

        $departments = [];
        if (Schema::hasTable('departments')) {
            $departments = DB::table('departments')->orderBy('name')->pluck('name')->filter()->values()->all();
        }

        $employmentTypes = [];
        if (Schema::hasTable('employment_types')) {
            $employmentTypes = DB::table('employment_types')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name])
                ->all();
        }

        return ['departments' => $departments, 'employmentTypes' => $employmentTypes];
    }

    public function previewEmployeeId(): string
    {
        if (! Schema::hasTable('employees')) {
            return '';
        }

        do {
            $id = 'EMP-'.date('Ymd').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        } while (DB::table('employees')->where('employee_id', $id)->exists());

        return $id;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ok: bool, message?: string, errors?: list<string>, employeeId?: string, previewId?: string}
     */
    public function store(array $input, ?UploadedFile $signature, int $adminUserId, string $adminName): array
    {
        LegacyDatabase::ensureStaffSchema();

        $employmentTypes = $this->formOptions()['employmentTypes'];
        $errors = $this->validate($input, $employmentTypes);

        $sigValid = false;
        if ($signature && $signature->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($signature->getError() !== UPLOAD_ERR_OK) {
                $errors[] = 'Signature file could not be uploaded. Use a PNG file under 2MB.';
            } elseif ($signature->getMimeType() !== 'image/png') {
                $errors[] = 'Signature must be a PNG image.';
            } elseif ($signature->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Signature image must be 2MB or smaller.';
            } else {
                $sigValid = true;
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $parsed = $this->parseInput($input, $employmentTypes);
        $employeeCode = $this->generateUniqueEmployeeId();

        $newId = 0;
        $signatureSkipped = false;

        try {
            DB::transaction(function () use ($parsed, $employeeCode, $signature, $sigValid, $adminUserId, $adminName, &$newId, &$signatureSkipped) {
                $now = now();

                $newId = (int) DB::table('employees')->insertGetId([
                    'employee_id' => $employeeCode,
                    'full_name' => $parsed['full_name'],
                    'email' => $parsed['email'],
                    'phone' => $parsed['phone'],
                    'position' => $parsed['position'],
                    'department' => $parsed['department'],
                    'employment_type_id' => $parsed['employment_type_id'],
                    'date_hired' => $parsed['date_hired'],
                    'status' => $parsed['status'],
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
                    'created_at' => $now,
                ]);

                if ($sigValid && $signature && Schema::hasColumn('employees', 'signature')) {
                    $dir = base_path('uploads/signatures');
                    if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
                        throw new \RuntimeException('Could not create signatures upload directory.');
                    }
                    $filename = $newId.'_sig_'.time().'.png';
                    $signature->move($dir, $filename);
                    DB::table('employees')->where('id', $newId)->update([
                        'signature' => 'signatures/'.$filename,
                    ]);
                } elseif ($sigValid && $signature && ! Schema::hasColumn('employees', 'signature')) {
                    $signatureSkipped = true;
                }

                $this->ensureCompensationTable();
                DB::table('employee_compensation')->insert([
                    'employee_id' => $newId,
                    'basic_salary_monthly' => $parsed['basic_salary_monthly'],
                    'basic_salary_daily' => $parsed['basic_salary_daily'],
                    'basic_salary_annually' => $parsed['basic_salary_annual'],
                    'employment_type' => $parsed['comp_employment_type'],
                    'effective_date' => $parsed['date_hired'] ?: now()->toDateString(),
                    'allowance_internet' => $parsed['allowance_internet'],
                    'allowance_meal' => $parsed['allowance_meal'],
                    'allowance_position' => $parsed['allowance_position'],
                    'allowance_transportation' => $parsed['allowance_transportation'],
                    'created_at' => $now,
                ]);

                if (Schema::hasTable('activity_logs')) {
                    ActivityLog::query()->create([
                        'user_id' => $adminUserId,
                        'user_name' => $adminName,
                        'action' => 'Add Employee',
                        'entity_type' => 'Employee',
                        'entity_id' => $newId,
                        'description' => "Added employee: {$parsed['full_name']} (ID: {$employeeCode})",
                        'ip_address' => request()->ip(),
                        'created_at' => $now,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }

        $message = 'Employee added successfully! Employee ID: '.$employeeCode.'. Create the login account from Accounts page to generate a password.';
        if (! empty($signatureSkipped)) {
            $message .= ' The signature file was not stored: the employees table has no signature column yet. Open database/alter_add_signature.php once in your browser, then add the signature from Edit Employee.';
        }

        return [
            'ok' => true,
            'message' => $message,
            'employeeId' => $employeeCode,
            'previewId' => $this->previewEmployeeId(),
        ];
    }

    private function generateUniqueEmployeeId(): string
    {
        do {
            $id = 'EMP-'.date('Ymd').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
        } while (DB::table('employees')->where('employee_id', $id)->exists());

        return $id;
    }

    /**
     * @param  list<array{id: int, name: string}>  $employmentTypes
     * @return list<string>
     */
    private function validate(array $input, array $employmentTypes): array
    {
        $errors = [];

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
        } elseif (DB::table('employees')->where('email', $email)->exists()) {
            $errors[] = 'Email already exists';
        }

        if (trim((string) ($input['position'] ?? '')) === '') {
            $errors[] = 'Position is required';
        }
        if (trim((string) ($input['department'] ?? '')) === '') {
            $errors[] = 'Department is required';
        }
        if (! (int) ($input['employment_type'] ?? 0)) {
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

        return $errors;
    }

    /**
     * @param  list<array{id: int, name: string}>  $employmentTypes
     * @return array<string, mixed>
     */
    private function parseInput(array $input, array $employmentTypes): array
    {
        $address = trim((string) ($input['address'] ?? ''));
        $emergencySame = isset($input['emergency_same_as_primary']) && (string) $input['emergency_same_as_primary'] === '1';
        $emergencyAddress = $emergencySame
            ? $address
            : trim((string) ($input['emergency_contact_address'] ?? ''));

        $employmentTypeId = (int) ($input['employment_type'] ?? 0);
        $compType = 'Regular';
        foreach ($employmentTypes as $opt) {
            if ((int) $opt['id'] === $employmentTypeId) {
                $nameLower = strtolower($opt['name']);
                if (str_contains($nameLower, 'contract')) {
                    $compType = 'Contractual';
                } elseif (str_contains($nameLower, 'probation')) {
                    $compType = 'Probationary';
                } elseif (str_contains($nameLower, 'part')) {
                    $compType = 'Part-time';
                }
                break;
            }
        }

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
            'comp_employment_type' => $compType,
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
            UNIQUE KEY `unique_employee_compensation` (`employee_id`),
            KEY `idx_employee_id` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
