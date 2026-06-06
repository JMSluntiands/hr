<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocumentUpload;
use App\Services\EmployeeContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function upload(Request $request, EmployeeContext $context): JsonResponse
    {
        $employee = $context->requireEmployee();

        $request->validate([
            'document_type' => 'required|string|max:100',
            'document_file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $documentType = trim((string) $request->input('document_type'));
        $allowedTypes = [
            'SSS', 'Philhealth', 'Pag-Ibig', 'TIN', 'NBI Clearance', 'Police Clearance',
            'Bank Account', 'Employee Agreement Contract', 'Contractual Agreement Contract',
        ];

        if (! in_array($documentType, $allowedTypes, true)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid document type.']);
        }

        if (! Schema::hasTable('employee_document_uploads')) {
            return response()->json(['status' => 'error', 'message' => 'Document uploads are not available.']);
        }

        $file = $request->file('document_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $uploadDir = base_path('uploads/employee_documents');
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $multiUploadTypes = ['Employee Agreement Contract', 'Contractual Agreement Contract'];
        if (! in_array($documentType, $multiUploadTypes, true)) {
            $oldRows = DB::table('employee_document_uploads')
                ->where('employee_id', $employee->id)
                ->where('document_type', $documentType)
                ->get(['file_path']);

            foreach ($oldRows as $oldRow) {
                $oldPath = base_path('uploads/'.ltrim((string) $oldRow->file_path, '/'));
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            DB::table('employee_document_uploads')
                ->where('employee_id', $employee->id)
                ->where('document_type', $documentType)
                ->delete();
        }

        $docSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($documentType));
        $filename = $employee->id.'_'.$docSlug.'_'.time().'.'.$ext;
        $file->move($uploadDir, $filename);

        $relativePath = 'employee_documents/'.$filename;
        DB::table('employee_document_uploads')->insert([
            'employee_id' => $employee->id,
            'document_type' => $documentType,
            'file_path' => $relativePath,
            'status' => 'Pending',
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded successfully. Waiting for admin approval.',
        ]);
    }

    public function view(int $id, EmployeeContext $context): BinaryFileResponse
    {
        return $this->streamDocument($id, $context, inline: true);
    }

    public function download(int $id, EmployeeContext $context): BinaryFileResponse
    {
        return $this->streamDocument($id, $context, inline: false);
    }

    public function requestRemoval(int $id, EmployeeContext $context): JsonResponse
    {
        $employee = $context->requireEmployee();

        if (! Schema::hasColumn('employee_document_uploads', 'deletion_requested_at')) {
            return response()->json(['status' => 'error', 'message' => 'Document removal is not configured yet. Please contact HR.']);
        }

        $doc = EmployeeDocumentUpload::query()
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $doc) {
            return response()->json(['status' => 'error', 'message' => 'Document not found']);
        }

        if (($doc->status ?? '') !== 'Approved') {
            return response()->json(['status' => 'error', 'message' => 'Only verified documents can be requested for removal.']);
        }

        if (! empty($doc->deletion_requested_at)) {
            return response()->json(['status' => 'error', 'message' => 'A removal request is already pending for this document.']);
        }

        $doc->update(['deletion_requested_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Removal request sent. HR will review; the file will stay in admin archive if approved.',
        ]);
    }

    private function streamDocument(int $id, EmployeeContext $context, bool $inline): BinaryFileResponse
    {
        $employee = $context->requireEmployee();

        $doc = EmployeeDocumentUpload::query()
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $doc || empty($doc->file_path)) {
            abort(404);
        }

        if (! empty($doc->deletion_requested_at)) {
            abort(403);
        }

        $full = base_path('uploads/'.ltrim((string) $doc->file_path, '/'));
        if (! is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        $disposition = $inline ? 'inline' : 'attachment';

        return response()->file($full, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addslashes(basename($full)).'"',
        ]);
    }
}
