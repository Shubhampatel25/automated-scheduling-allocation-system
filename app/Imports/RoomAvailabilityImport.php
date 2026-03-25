<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class RoomAvailabilityImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row    = $this->normalize($row);
        $roomId = isset($row['room_id']) && $row['room_id'] !== '' ? (int)$row['room_id'] : null;

        if (empty($roomId)) { $this->skipped++; return null; }

        $exists = DB::table('room_availability')
            ->where('room_id',     $roomId)
            ->where('day_of_week', $row['day_of_week'] ?? null)
            ->where('start_time',  $row['start_time']  ?? null)
            ->exists();

        if ($exists) { $this->skipped++; return null; }

        DB::table('room_availability')->insertOrIgnore([
            'id'          => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'room_id'     => $roomId,
            'day_of_week' => ucfirst(strtolower(trim((string)($row['day_of_week'] ?? '')))),
            'start_time'  => trim((string)($row['start_time'] ?? '')),
            'end_time'    => trim((string)($row['end_time']   ?? '')),
            'status'      => strtolower(trim((string)($row['status'] ?? 'available'))),
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
