<?php

namespace App\Services;

use App\Models\EmployeeDocumentUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentArchiveService
{
    public function schemaReady(): bool
    {
        return Schema::hasTable('document_archive')
            && Schema::hasColumn('employee_document_uploads', 'deletion_requested_at');
    }

    /**
     * @return \Illuminate\Support\Collection<int, EmployeeDocumentUpload>
     */
    public function pendingRemovals()
    {
        if (! $this->schemaReady()) {
            return collect();
        }

        return EmployeeDocumentUpload::query()
            ->with('employee')
            ->where('status', 'Approved')
            ->whereNotNull('deletion_requested_at')
            ->orderByDesc('deletion_requested_at')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function archivedList(int $limit = 200)
    {
        if (! Schema::hasTable('document_archive')) {
            return collect();
        }

        return DB::table('document_archive')
            ->orderByDesc('archived_at')
            ->limit($limit)
            ->get();
    }

    public function rejectRemoval(EmployeeDocumentUpload $upload): void
    {
        if (! $this->isPendingRemoval($upload)) {
            throw new \RuntimeException('This document is not pending removal.');
        }

        $upload->update(['deletion_requested_at' => null]);
    }

    public function approveRemoval(EmployeeDocumentUpload $upload, int $adminId, string $adminName): void
    {
        if (! $this->schemaReady()) {
            throw new \RuntimeException('Archive tables are not set up. Run database/setup_document_deletion_archive.php first.');
        }

        if (! $this->isPendingRemoval($upload)) {
            throw new \RuntimeException('This document is not pending removal.');
        }

        $upload->loadMissing('employee');
        $employeeId = (int) $upload->employee_id;
        $empName = $upload->employee?->full_name ?? 'Unknown';
        $docType = (string) $upload->document_type;
        $filePathRel = (string) $upload->file_path;

        DB::transaction(function () use ($upload, $adminId, $adminName, $employeeId, $empName, $docType, $filePathRel) {
            $uploadRoot = base_path('uploads/');
            $src = $uploadRoot.ltrim($filePathRel, '/');
            $archiveDir = $uploadRoot.'employee_documents_archive/';

            if (! is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $ext = pathinfo($filePathRel, PATHINFO_EXTENSION);
            $safeExt = $ext !== '' ? '.'.preg_replace('/[^a-z0-9]+/i', '', $ext) : '';
            $newBase = $employeeId.'_'.$upload->id.'_'.time().$safeExt;
            $archiveRel = 'employee_documents_archive/'.$newBase;
            $dest = $uploadRoot.$archiveRel;

            if (is_file($src)) {
                if (! rename($src, $dest)) {
                    if (! copy($src, $dest)) {
                        throw new \RuntimeException('Could not move file to archive.');
                    }
                    @unlink($src);
                }
            } else {
                $archiveRel = $filePathRel;
            }

            DB::table('document_archive')->insert([
                'employee_id' => $employeeId,
                'employee_full_name' => $empName,
                'document_type' => $docType,
                'file_path' => $archiveRel,
                'source_upload_id' => $upload->id,
                'deletion_requested_at' => $upload->deletion_requested_at,
                'archived_by' => $adminId,
                'archived_by_name' => $adminName,
                'archived_at' => now(),
            ]);

            if (Schema::hasTable('document_files')) {
                DB::table('document_files')
                    ->where('employee_id', $employeeId)
                    ->where('file_path', $filePathRel)
                    ->delete();
            }

            $upload->delete();
        });
    }

    public function resolveArchivePath(int $archiveId): ?string
    {
        if (! Schema::hasTable('document_archive')) {
            return null;
        }

        $rel = DB::table('document_archive')->where('id', $archiveId)->value('file_path');
        if (! $rel) {
            return null;
        }

        $path = base_path('uploads/'.ltrim((string) $rel, '/'));

        return is_file($path) ? $path : null;
    }

    private function isPendingRemoval(EmployeeDocumentUpload $upload): bool
    {
        return ($upload->status ?? '') === 'Approved'
            && ! empty($upload->deletion_requested_at);
    }
}
