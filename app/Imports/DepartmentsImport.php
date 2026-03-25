<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class DepartmentsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row  = $this->normalize($row);
        $code = strtoupper(trim((string)($row['code'] ?? '')));

        if (empty($code)) { $this->skipped++; return null; }

        if (DB::table('departments')->where('code', $code)->exists()) {
            $this->skipped++;
            return null;
        }

        DB::table('departments')->insertOrIgnore([
            'id'          => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'code'        => $code,
            'name'        => trim((string)($row['name'] ?? '')),
            'description' => $row['description'] ?? null,
            'created_at'  => $row['created_at'] ?? now(),
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
