<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffProfileService
{
    public const DOCUMENT_TYPES = [
        'SSS',
        'Philhealth',
        'Pag-Ibig',
        'TIN',
        'NBI Clearance',
        'Police Clearance',
        'Bank Account',
        'Employee Agreement Contract',
        'Contractual Agreement Contract',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function load(int $employeeId): ?array
    {
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return null;
        }

        $employmentTypeName = null;
        if ($employee->employment_type_id && Schema::hasTable('employment_types')) {
            $employmentTypeName = DB::table('employment_types')
                ->where('id', $employee->employment_type_id)
                ->value('name');
        }

        $documents = $this->loadDocuments($employeeId);
        $compensation = Schema::hasTable('employee_compensation')
            ? DB::table('employee_compensation')->where('employee_id', $employeeId)->first()
            : null;

        $latestAdjustment = null;
        $salaryAdjustments = [];
        if (Schema::hasTable('employee_salary_adjustments')) {
            $latestAdjustment = DB::table('employee_salary_adjustments')
                ->where('employee_id', $employeeId)
                ->orderByDesc('date_approved')
                ->orderByDesc('created_at')
                ->first();

            $salaryAdjustments = DB::table('employee_salary_adjustments')
                ->where('employee_id', $employeeId)
                ->orderByDesc('date_approved')
                ->orderByDesc('created_at')
                ->get(['previous_salary', 'new_salary', 'reason', 'approved_by', 'date_approved', 'created_at'])
                ->all();
        }

        $bankDetails = Schema::hasTable('employee_bank_details')
            ? DB::table('employee_bank_details')->where('employee_id', $employeeId)->first()
            : null;

        $currentSalary = null;
        if ($latestAdjustment) {
            $currentSalary = $latestAdjustment->new_salary ?? null;
        } elseif ($compensation) {
            $currentSalary = $compensation->basic_salary_monthly ?? null;
        }

        $dailyGross = null;
        if ($currentSalary !== null) {
            $dailyGross = ! empty($compensation?->basic_salary_daily)
                ? (float) $compensation->basic_salary_daily
                : ((float) $currentSalary / 26);
        }

        $documentsByType = [];
        foreach ($documents as $doc) {
            $type = $doc->document_type ?? '';
            if ($type !== '' && ! isset($documentsByType[$type])) {
                $documentsByType[$type] = $doc;
            }
        }

        return [
            'employee' => $employee,
            'employeeId' => $employeeId,
            'employmentTypeName' => $employmentTypeName,
            'documents' => $documents,
            'documentsByType' => $documentsByType,
            'documentTypes' => self::DOCUMENT_TYPES,
            'compensation' => $compensation,
            'latestAdjustment' => $latestAdjustment,
            'salaryAdjustments' => $salaryAdjustments,
            'bankDetails' => $bankDetails,
            'currentSalary' => $currentSalary,
            'dailyGross' => $dailyGross,
        ];
    }

    /**
     * @return list<object>
     */
    private function loadDocuments(int $employeeId): array
    {
        if (! Schema::hasTable('employee_document_uploads')) {
            return [];
        }

        $columns = ['id', 'document_type', 'file_path', 'status', 'created_at', 'updated_at'];
        if (Schema::hasColumn('employee_document_uploads', 'deletion_requested_at')) {
            $columns[] = 'deletion_requested_at';
        }

        return DB::table('employee_document_uploads')
            ->where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get($columns)
            ->all();
    }

    public static function uploadUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return asset('uploads/'.ltrim($relativePath, '/'));
    }

    public static function uploadExists(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') {
            return false;
        }

        return is_file(base_path('uploads/'.ltrim($relativePath, '/')));
    }
}
