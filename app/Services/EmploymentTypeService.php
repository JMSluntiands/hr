<?php

namespace App\Services;

use App\Models\EmploymentType;
use App\Services\LegacyDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class EmploymentTypeService
{
    public function ensureTable(): void
    {
        LegacyDatabase::ensureStaffSchema();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, EmploymentType>
     */
    public function list()
    {
        $this->ensureTable();

        if (! Schema::hasTable('employment_types')) {
            return collect();
        }

        return EmploymentType::query()->orderBy('name')->get();
    }

    public function create(string $name): EmploymentType
    {
        $this->ensureTable();
        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Employment type name is required.');
        }

        try {
            return EmploymentType::query()->create(['name' => $name]);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new \RuntimeException('Employment type name already exists.');
            }
            throw $e;
        }
    }

    public function update(int $id, string $name): void
    {
        $this->ensureTable();
        $name = trim($name);
        if ($id <= 0 || $name === '') {
            throw new \RuntimeException('Invalid request.');
        }

        $type = EmploymentType::query()->find($id);
        if (! $type) {
            throw new \RuntimeException('Employment type not found.');
        }

        try {
            $type->update(['name' => $name]);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new \RuntimeException('Employment type name already exists.');
            }
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->ensureTable();
        if ($id <= 0) {
            throw new \RuntimeException('Invalid request.');
        }

        $deleted = EmploymentType::query()->where('id', $id)->delete();
        if (! $deleted) {
            throw new \RuntimeException('Employment type not found or could not be deleted.');
        }
    }
}
