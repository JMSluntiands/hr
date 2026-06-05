<?php

namespace App\Services;

use App\Models\Department;
use App\Services\LegacyDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class DepartmentService
{
    public function ensureSchema(): void
    {
        LegacyDatabase::ensureStaffSchema();
    }

    public function hasPerformanceReviewColumn(): bool
    {
        $this->ensureSchema();

        return Schema::hasTable('departments')
            && Schema::hasColumn('departments', 'additional_performance_review');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Department>
     */
    public function list()
    {
        $this->ensureSchema();

        if (! Schema::hasTable('departments')) {
            return collect();
        }

        return Department::query()->orderBy('name')->get();
    }

    public function create(string $name, bool $additionalPerformanceReview = false): Department
    {
        $this->ensureSchema();
        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Department name is required.');
        }

        $data = ['name' => $name];
        if ($this->hasPerformanceReviewColumn()) {
            $data['additional_performance_review'] = $additionalPerformanceReview ? 1 : 0;
        }

        try {
            return Department::query()->create($data);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new \RuntimeException('Department name already exists.');
            }
            throw $e;
        }
    }

    public function update(int $id, string $name, bool $additionalPerformanceReview = false): void
    {
        $this->ensureSchema();
        $name = trim($name);
        if ($id <= 0 || $name === '') {
            throw new \RuntimeException('Invalid request.');
        }

        $dept = Department::query()->find($id);
        if (! $dept) {
            throw new \RuntimeException('Department not found.');
        }

        $data = ['name' => $name];
        if ($this->hasPerformanceReviewColumn()) {
            $data['additional_performance_review'] = $additionalPerformanceReview ? 1 : 0;
        }

        try {
            $dept->update($data);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new \RuntimeException('Department name already exists.');
            }
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->ensureSchema();
        if ($id <= 0) {
            throw new \RuntimeException('Invalid request.');
        }

        $deleted = Department::query()->where('id', $id)->delete();
        if (! $deleted) {
            throw new \RuntimeException('Department not found or could not be deleted.');
        }
    }
}
