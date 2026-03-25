<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class RoomsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row    = $this->normalize($row);
        $roomNo = trim((string)($row['room_number'] ?? ''));

        if (empty($roomNo)) { $this->skipped++; return null; }

        if (DB::table('rooms')->where('room_number', $roomNo)->exists()) {
            $this->skipped++;
            return null;
        }

        DB::table('rooms')->insertOrIgnore([
            'id'          => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'room_number' => $roomNo,
            'building'    => trim((string)($row['building']  ?? '')),
            'type'        => strtolower(trim((string)($row['type'] ?? 'classroom'))),
            'capacity'    => isset($row['capacity']) ? (int)$row['capacity'] : 0,
            'equipment'   => $row['equipment'] ?? null,
            'status'      => in_array(strtolower(trim((string)($row['status'] ?? ''))), ['available','unavailable','maintenance']) ? strtolower(trim((string)$row['status'])) : 'available',
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
