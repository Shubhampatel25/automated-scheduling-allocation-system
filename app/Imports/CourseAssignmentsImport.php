<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class CourseAssignmentsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row       = $this->normalize($row);
        $sectionId = isset($row['course_section_id']) && $row['course_section_id'] !== '' ? (int)$row['course_section_id'] : null;
        $teacherId = isset($row['teacher_id'])        && $row['teacher_id']        !== '' ? (int)$row['teacher_id']        : null;

        if (empty($sectionId) || empty($teacherId)) { $this->skipped++; return null; }

        $exists = DB::table('course_assignments')
            ->where('course_section_id', $sectionId)
            ->where('teacher_id',        $teacherId)
            ->where('component',         $row['component'] ?? null)
            ->exists();

        if ($exists) { $this->skipped++; return null; }

        DB::table('course_assignments')->insertOrIgnore([
            'id'                => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'course_section_id' => $sectionId,
            'teacher_id'        => $teacherId,
            'component'         => strtolower(trim((string)($row['component']    ?? 'theory'))),
            'assigned_date'     => $row['assigned_date'] ?? now()->toDateString(),
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
