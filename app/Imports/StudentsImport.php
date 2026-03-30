<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class StudentsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row    = $this->normalize($row);
        $rollNo = trim((string)($row['roll_no'] ?? ''));

        if (empty($rollNo)) { $this->skipped++; return null; }

        if (DB::table('students')->where('roll_no', $rollNo)->exists()) {
            $this->skipped++;
            return null;
        }

        // Accept department_id (int) or department_code (string lookup)
        $deptId = null;
        if (!empty($row['department_id']) && is_numeric($row['department_id'])) {
            $deptId = (int) $row['department_id'];
        } elseif (!empty($row['department_code'])) {
            $deptId = DB::table('departments')->where('code', strtoupper(trim((string)$row['department_code'])))->value('id');
        }

        DB::table('students')->insertOrIgnore([
            'id'            => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'user_id'       => isset($row['user_id']) && $row['user_id'] !== '' ? (int)$row['user_id'] : null,
            'roll_no'       => $rollNo,
            'name'          => trim((string)($row['name']  ?? '')),
            'email'         => strtolower(trim((string)($row['email'] ?? ''))),
            'department_id' => $deptId,
            'semester'      => isset($row['semester']) && $row['semester'] !== '' ? (int)$row['semester'] : 1,
            'status'        => strtolower(trim((string)($row['status'] ?? 'active'))),
            'created_at'    => $row['created_at'] ?? now(),
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
