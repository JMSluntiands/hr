<?php

namespace App\Services;

use App\Models\BankAccountChangeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BankRequestService
{
    public function ensureEmployeeBankDetailsTable(): void
    {
        if (Schema::hasTable('employee_bank_details')) {
            return;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `employee_bank_details` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `bank_name` varchar(255) NOT NULL,
            `account_number` varchar(100) NOT NULL,
            `account_name` varchar(255) NOT NULL,
            `account_type` enum('Savings','Checking','Current') DEFAULT 'Savings',
            `branch` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_employee_bank` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function approve(BankAccountChangeRequest $request, int $adminId, string $adminName): void
    {
        if ($request->status !== 'Pending') {
            throw new \RuntimeException('Request not found or already processed.');
        }

        DB::transaction(function () use ($request, $adminId, $adminName) {
            $this->ensureEmployeeBankDetailsTable();

            $employeeId = (int) $request->employee_id;
            $payload = [
                'bank_name' => (string) $request->bank_name,
                'account_number' => (string) $request->account_number,
                'account_name' => (string) $request->account_name,
                'account_type' => (string) ($request->account_type ?? 'Savings'),
                'branch' => (string) ($request->branch ?? ''),
                'updated_at' => now(),
            ];

            $exists = DB::table('employee_bank_details')->where('employee_id', $employeeId)->exists();
            if ($exists) {
                DB::table('employee_bank_details')->where('employee_id', $employeeId)->update($payload);
            } else {
                DB::table('employee_bank_details')->insert(array_merge($payload, [
                    'employee_id' => $employeeId,
                    'created_at' => now(),
                ]));
            }

            if (! DB::table('employee_bank_details')->where('employee_id', $employeeId)->exists()) {
                throw new \RuntimeException('Bank details were not saved. Please try again.');
            }

            $update = [
                'status' => 'Approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'rejection_reason' => null,
            ];
            if (Schema::hasColumn('bank_account_change_requests', 'approved_by_name')) {
                $update['approved_by_name'] = $adminName;
            }
            $request->update($update);
        });
    }

    public function decline(BankAccountChangeRequest $request, int $adminId, string $adminName, string $reason): void
    {
        if ($request->status !== 'Pending') {
            throw new \RuntimeException('Request not found or already processed.');
        }

        $update = [
            'status' => 'Rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ];
        if (Schema::hasColumn('bank_account_change_requests', 'approved_by_name')) {
            $update['approved_by_name'] = $adminName;
        }
        $request->update($update);
    }
}
