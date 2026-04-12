<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class CourseSectionsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row)
    {
        $row = $this->normalize($row);

        // Accept course_id (int) or course_code (string lookup)
        $courseId = null;
        if (!empty($row['course_id']) && is_numeric($row['course_id'])) {
            $courseId = (int) $row['course_id'];
        } elseif (!empty($row['course_code'])) {
            $courseId = DB::table('courses')->where('code', strtoupper(trim((string)$row['course_code'])))->value('id');
        }

        if (empty($courseId)) { $this->skipped++; return null; }

        // Duplicate check: same course + section + term + year
        $exists = DB::table('course_sections')
            ->where('course_id',       $courseId)
            ->where('section_number',  $row['section_number'] ?? null)
            ->where('term',            $row['term'] ?? null)
            ->where('year',            $row['year'] ?? null)
            ->exists();

        if ($exists) { $this->skipped++; return null; }

        DB::table('course_sections')->insertOrIgnore([
            'id'               => isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null,
            'course_id'        => $courseId,
            'section_number'   => isset($row['section_number'])   ? (int)$row['section_number']   : 1,
            'term'             => trim((string)($row['term']  ?? '')),
            'year'             => isset($row['year'])             ? (int)$row['year']             : date('Y'),
            'max_students'     => isset($row['max_students'])     ? (int)$row['max_students']     : 30,
            // Always start at 0 — never trust the Excel value.
            // StudentCourseRegistrationsImport increments this per enrolled row,
            // and ExcelImportController runs syncAllEnrolledCounts() after every
            // registration import to guarantee the final count is authoritative.
            // Importing a non-zero Excel value would double-count once registrations
            // are also imported.
            'enrolled_students'=> 0,
            'created_at'       => $row['created_at'] ?? now(),
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
