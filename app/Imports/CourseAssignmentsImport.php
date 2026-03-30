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
        $row = $this->normalize($row);

        // Accept course_section_id (int) or course_code + section_number (lookup)
        $sectionId = null;
        if (!empty($row['course_section_id']) && is_numeric($row['course_section_id'])) {
            $sectionId = (int) $row['course_section_id'];
        } elseif (!empty($row['course_code'])) {
            $courseId  = DB::table('courses')->where('code', strtoupper(trim((string)$row['course_code'])))->value('id');
            $secNum    = isset($row['section_number']) && $row['section_number'] !== '' ? (int)$row['section_number'] : 1;
            $term      = trim((string)($row['term'] ?? ''));
            $year      = isset($row['year']) && $row['year'] !== '' ? (int)$row['year'] : now()->year;
            if ($courseId) {
                $q = DB::table('course_sections')->where('course_id', $courseId)->where('section_number', $secNum);
                if ($term) $q->where('term', $term);
                if ($year) $q->where('year', $year);
                $sectionId = $q->value('id');
            }
        }

        // Accept teacher_id (int) or teacher_employee_id (string lookup)
        $teacherId = null;
        if (!empty($row['teacher_id']) && is_numeric($row['teacher_id'])) {
            $teacherId = (int) $row['teacher_id'];
        } elseif (!empty($row['teacher_employee_id'])) {
            $teacherId = DB::table('teachers')->where('employee_id', trim((string)$row['teacher_employee_id']))->value('id');
        }

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
