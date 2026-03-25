<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class HodsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row   = $this->normalize($row);
        $deptId = isset($row['department_id']) && $row['department_id'] !== '' ? (int)$row['department_id'] : null;

        if (empty($deptId)) { $this->skipped++; return null; }

        // Each department can only have one HOD
        if (DB::table('hods')->where('department_id', $deptId)->exists()) {
            $this->skipped++;
            return null;
        }

        DB::table('hods')->insertOrIgnore([
            'id'             => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'user_id'        => isset($row['user_id'])    && $row['user_id']    !== '' ? (int)$row['user_id']    : null,
            'teacher_id'     => isset($row['teacher_id']) && $row['teacher_id'] !== '' ? (int)$row['teacher_id'] : null,
            'department_id'  => $deptId,
            'appointed_date' => $row['appointed_date'] ?? now()->toDateString(),
            'status'         => strtolower(trim((string)($row['status'] ?? 'active'))),
            'created_at'     => $row['created_at'] ?? now(),
        ]);

        $this->imported++;
        return null;
    }

    private function normalize(array $row): array
    {
        return array_combine(
            array_map(fn($k) => strtolower(trim((string)$k)), array_keys($row)),
            array_values($row)
        );
    }

    public function onError(Throwable $e): void
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $f) {
            $this->failures[] = "Row {$f->row()}: " . implode(', ', $f->errors());
        }
    }
}
