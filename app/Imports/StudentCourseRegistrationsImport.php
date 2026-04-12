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
 * Hard rules enforced (each violation skips the row with a reason):
 *   1. student must be found (by id or roll_no) and actually exist in the DB
 *   2. course_section_id must exist
 *   3. status=completed MUST have result=pass or result=fail
 *   4. status=enrolled MUST NOT carry a result (result is cleared automatically)
 *   5. student.department_id must match the section's course.department_id
 *   6. status=enrolled: course.semester must not exceed student.semester
 *      (prevents forward-semester pre-registration; retakes at lower semesters are allowed)
 *   7. No active/completed duplicate for the same student + section pair
 */
class StudentCourseRegistrationsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors      = [];
    public array $failures    = [];
    public array $skipReasons = [];  // human-readable per-row skip messages
    public int   $imported    = 0;
    public int   $skipped     = 0;

    // Row 1 is the heading; data rows start at 2.
    private int $currentRow = 1;

    public function model(array $row): ?Model
    {
        $rowNum = ++$this->currentRow;
        $row    = $this->normalize($row);

        // ── 1. Resolve student ────────────────────────────────────────────────
        $studentId = null;
        if (!empty($row['student_id']) && is_numeric($row['student_id'])) {
            $studentId = (int) $row['student_id'];
            // Verify the numeric ID actually exists (don't blindly trust the sheet)
            if (!DB::table('students')->where('id', $studentId)->exists()) {
                return $this->skip($rowNum, "student_id={$studentId} does not exist");
            }
        } elseif (!empty($row['roll_no'])) {
            $rollNo    = trim((string) $row['roll_no']);
            $studentId = DB::table('students')->where('roll_no', $rollNo)->value('id');
            if (!$studentId) {
                return $this->skip($rowNum, "roll_no='{$rollNo}' not found in students");
            }
        } else {
            return $this->skip($rowNum, 'student_id or roll_no is required');
        }

        // ── 2. Resolve course_section_id ──────────────────────────────────────
        if (empty($row['course_section_id']) || !is_numeric($row['course_section_id'])) {
            return $this->skip($rowNum, 'course_section_id is required and must be numeric');
        }

        $courseSectionId = (int) $row['course_section_id'];

        // Fetch course metadata in one query (department + semester)
        $courseInfo = DB::table('course_sections')
            ->join('courses', 'courses.id', '=', 'course_sections.course_id')
            ->where('course_sections.id', $courseSectionId)
            ->select('courses.department_id as course_dept_id', 'courses.semester as course_semester')
            ->first();

        if (!$courseInfo) {
            return $this->skip($rowNum, "course_section_id={$courseSectionId} not found");
        }

        // ── 3. Normalize status ───────────────────────────────────────────────
        $status = strtolower(trim((string) ($row['status'] ?? 'enrolled')));
        if (!in_array($status, ['enrolled', 'completed', 'dropped'], true)) {
            // Silently coerce unknown status to 'enrolled' (common data-entry error)
            $status = 'enrolled';
        }

        // ── 4. Normalize result ───────────────────────────────────────────────
        $rawResult = isset($row['result']) && $row['result'] !== ''
            ? strtolower(trim((string) $row['result']))
            : null;

        if ($rawResult !== null && !in_array($rawResult, ['pass', 'fail'], true)) {
            $rawResult = null; // treat unrecognised result values as absent
        }

        // ── RULE: completed must have pass or fail ────────────────────────────
        if ($status === 'completed' && !in_array($rawResult, ['pass', 'fail'], true)) {
            return $this->skip(
                $rowNum,
                "status=completed requires result=pass or result=fail (got: " . ($rawResult ?? 'null') . ")"
            );
        }

        // ── RULE: enrolled must NOT carry a result ────────────────────────────
        // Clear silently — an enrolled row with a result is a data-entry error,
        // not a reason to reject the entire row.
        if ($status === 'enrolled' && $rawResult !== null) {
            $rawResult = null;
        }

        // ── 5. Department match ───────────────────────────────────────────────
        $studentDeptId = DB::table('students')->where('id', $studentId)->value('department_id');
        if ($studentDeptId && $courseInfo->course_dept_id && (int)$studentDeptId !== (int)$courseInfo->course_dept_id) {
            return $this->skip(
                $rowNum,
                "department mismatch: student department_id={$studentDeptId}"
                . " does not match section department_id={$courseInfo->course_dept_id}"
            );
        }

        // ── 6. Semester consistency (enrolled rows only) ──────────────────────
        // A student cannot be enrolled in a course from a future semester.
        // Completed rows are historical data and are exempt from this check.
        // Retakes (lower-semester courses) are explicitly allowed.
        if ($status === 'enrolled' && $courseInfo->course_semester !== null) {
            $studentSemester = DB::table('students')->where('id', $studentId)->value('semester');
            if ($studentSemester !== null && (int)$courseInfo->course_semester > (int)$studentSemester) {
                return $this->skip(
                    $rowNum,
                    "semester mismatch: course belongs to semester {$courseInfo->course_semester}"
                    . " but student is in semester {$studentSemester}"
                    . " (forward enrollment is not allowed)"
                );
            }
        }

        // ── 7. Duplicate check ────────────────────────────────────────────────
        // Skip if an active or completed record already exists for this pair.
        $exists = DB::table('student_course_registrations')
            ->where('student_id', $studentId)
            ->where('course_section_id', $courseSectionId)
            ->whereIn('status', ['enrolled', 'completed'])
            ->exists();

        if ($exists) {
            return $this->skip(
                $rowNum,
                "duplicate: student_id={$studentId} already has an active/completed record"
                . " for course_section_id={$courseSectionId}"
            );
        }

        // ── 8. Insert ─────────────────────────────────────────────────────────
        DB::table('student_course_registrations')->insert([
            'student_id'        => $studentId,
            'course_section_id' => $courseSectionId,
            'status'            => $status,
            'result'            => $rawResult,
            'registered_at'     => $row['registered_at'] ?? now(),
        ]);

        // Keep enrolled_students counter in sync for enrolled rows only.
        if ($status === 'enrolled') {
            DB::table('course_sections')
                ->where('id', $courseSectionId)
                ->increment('enrolled_students');
        }

        $this->imported++;
        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Record a skip with a human-readable reason and return null (to satisfy ToModel). */
    private function skip(int $rowNum, string $reason)
    {
        $this->skipped++;
        $this->skipReasons[] = "Row {$rowNum}: {$reason}";
        return null;
    }

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
