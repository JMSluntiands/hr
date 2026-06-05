<?php

namespace App\Services;

use App\Models\EmployeeDocumentUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentUploadService
{
    public function approve(EmployeeDocumentUpload $upload, int $adminId, string $adminName): void
    {
        if ($upload->status !== 'Pending') {
            throw new \RuntimeException('Document upload not found or already processed.');
        }

        DB::transaction(function () use ($upload, $adminId, $adminName) {
            $update = [
                'status' => 'Approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'rejection_reason' => null,
            ];
            if (Schema::hasColumn('employee_document_uploads', 'approved_by_name')) {
                $update['approved_by_name'] = $adminName;
            }
            $upload->update($update);

            if (! Schema::hasTable('document_files')) {
                return;
            }

            $employeeId = (int) $upload->employee_id;
            $docType = (string) $upload->document_type;
            $filePath = (string) $upload->file_path;

            $exists = DB::table('document_files')
                ->where('employee_id', $employeeId)
                ->where('document_type', $docType)
                ->where('file_path', $filePath)
                ->exists();

            if ($exists) {
                return;
            }

            $insert = [
                'employee_id' => $employeeId,
                'document_type' => $docType,
                'file_path' => $filePath,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'created_at' => now(),
            ];
            if (Schema::hasColumn('document_files', 'approved_by_name')) {
                $insert['approved_by_name'] = $adminName;
            }

            DB::table('document_files')->insert($insert);
        });
    }

    public function decline(EmployeeDocumentUpload $upload, int $adminId, string $adminName, string $reason): void
    {
        if ($upload->status !== 'Pending') {
            throw new \RuntimeException('Document upload not found or already processed.');
        }

        $update = [
            'status' => 'Rejected',
            'rejection_reason' => $reason,
            'approved_by' => $adminId,
            'approved_at' => now(),
        ];
        if (Schema::hasColumn('employee_document_uploads', 'approved_by_name')) {
            $update['approved_by_name'] = $adminName;
        }
        $upload->update($update);
    }

    public function resolveDiskPath(EmployeeDocumentUpload $upload): ?string
    {
        $relative = ltrim((string) $upload->file_path, '/');
        if ($relative === '') {
            return null;
        }

        $path = base_path('uploads/'.$relative);

        return is_file($path) ? $path : null;
    }
}
