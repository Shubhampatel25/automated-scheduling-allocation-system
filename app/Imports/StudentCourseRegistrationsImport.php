<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Import student_course_registrations from Excel.
 *
 * Expected columns (heading row, case-insensitive):
 *   student_id   OR  roll_no         – identifies the student
 *   course_section_id                – must exist in course_sections
 *   status                           – enrolled | completed | dropped  (default: enrolled)
 *   result                           – pass | fail | null              (default: null)
 *   registered_at                    – optional datetime               (default: now)
 *
 * Behaviour:
 *  - Skips rows where the student or course section cannot be resolved.
 *  - Skips duplicate active/completed records for the same student + section.
 *  - Increments enrolled_students on the section when status = enrolled.
 */
class StudentCourseRegistrationsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row): ?Model
    {
        $row = $this->normalize($row);

        // ── Resolve student_id ────────────────────────────────────────────────
        // Accept numeric student_id directly, or look up by roll_no.
        $studentId = null;
        if (!empty($row['student_id']) && is_numeric($row['student_id'])) {
            $studentId = (int) $row['student_id'];
        } elseif (!empty($row['roll_no'])) {
            $studentId = DB::table('students')
                ->where('roll_no', trim((string) $row['roll_no']))
                ->value('id');
        }

        if (!$studentId) {
            $this->skipped++;
            return null;
        }

        // ── Resolve course_section_id ─────────────────────────────────────────
        $courseSectionId = isset($row['course_section_id']) && is_numeric($row['course_section_id'])
            ? (int) $row['course_section_id']
            : null;

        if (!$courseSectionId || !DB::table('course_sections')->where('id', $courseSectionId)->exists()) {
            $this->skipped++;
            return null;
        }

        // ── Validate status / result ──────────────────────────────────────────
        $status = strtolower(trim((string) ($row['status'] ?? 'enrolled')));
        if (!in_array($status, ['enrolled', 'completed', 'dropped'])) {
            $status = 'enrolled';
        }

        $result = (isset($row['result']) && $row['result'] !== '')
            ? strtolower(trim((string) $row['result']))
            : null;
        if ($result !== null && !in_array($result, ['pass', 'fail'])) {
            $result = null;
        }

        // ── Skip duplicates ───────────────────────────────────────────────────
        // Prevent re-importing an active or completed record for the same pair.
        $exists = DB::table('student_course_registrations')
            ->where('student_id', $studentId)
            ->where('course_section_id', $courseSectionId)
            ->whereIn('status', ['enrolled', 'completed'])
            ->exists();

        if ($exists) {
            $this->skipped++;
            return null;
        }

        // ── Insert ────────────────────────────────────────────────────────────
        DB::table('student_course_registrations')->insert([
            'student_id'        => $studentId,
            'course_section_id' => $courseSectionId,
            'status'            => $status,
            'result'            => $result,
            'registered_at'     => $row['registered_at'] ?? now(),
        ]);

        // Keep the section's enrolled_students counter in sync.
        if ($status === 'enrolled') {
            DB::table('course_sections')
                ->where('id', $courseSectionId)
                ->increment('enrolled_students');
        }

        $this->imported++;
        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normalize(array $row): array
    {
        return array_combine(
            array_map(fn($k) => strtolower(trim((string) $k)), array_keys($row)),
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
