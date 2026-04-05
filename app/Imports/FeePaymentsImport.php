<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Import fee_payments from Excel.
 *
 * Expected columns (heading row, case-insensitive):
 *   student_id   OR  roll_no         – identifies the student
 *   type                             – regular | supplemental          (default: regular)
 *   course_id    OR  course_code     – required only for supplemental fees
 *   semester                         – integer semester number
 *   year                             – integer year                    (default: current year)
 *   amount                           – total fee amount                (default: 0)
 *   paid_amount                      – amount already paid             (default: 0)
 *   status                           – pending | partial | paid | overdue (auto-corrected from amounts)
 *   paid_at                          – optional datetime
 *   created_at                       – optional datetime               (default: now)
 *
 * Auto-correction rules (mirror existing system behaviour):
 *  - paid_amount >= amount > 0  → status forced to 'paid'
 *  - 0 < paid_amount < amount   → status forced to 'partial'
 *  - Duplicate check: same student + type + semester + year (+ course_id for supplemental).
 */
class FeePaymentsImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    public array $errors   = [];
    public array $failures = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function model(array $row): ?Model
    {
        $row = $this->normalize($row);

        // ── Resolve student_id ────────────────────────────────────────────────
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

        // ── Resolve fee type ──────────────────────────────────────────────────
        $type = strtolower(trim((string) ($row['type'] ?? 'regular')));
        if (!in_array($type, ['regular', 'supplemental'])) {
            $type = 'regular';
        }

        // ── Resolve semester / year ───────────────────────────────────────────
        $semester = (isset($row['semester']) && $row['semester'] !== '')
            ? (int) $row['semester']
            : null;
        $year = (isset($row['year']) && $row['year'] !== '')
            ? (int) $row['year']
            : now()->year;

        // ── Resolve course_id (supplemental fees only) ────────────────────────
        // Accept course_id directly, or look up by course_code.
        $courseId = null;
        if ($type === 'supplemental') {
            if (!empty($row['course_id']) && is_numeric($row['course_id'])) {
                $courseId = (int) $row['course_id'];
            } elseif (!empty($row['course_code'])) {
                $courseId = DB::table('courses')
                    ->where('code', strtoupper(trim((string) $row['course_code'])))
                    ->value('id');
            }
        }

        // ── Skip duplicates ───────────────────────────────────────────────────
        $query = DB::table('fee_payments')
            ->where('student_id', $studentId)
            ->where('type', $type)
            ->where('semester', $semester)
            ->where('year', $year);

        if ($type === 'supplemental') {
            $query->where('course_id', $courseId);
        }

        if ($query->exists()) {
            $this->skipped++;
            return null;
        }

        // ── Amounts & status ──────────────────────────────────────────────────
        $amount     = (isset($row['amount'])      && $row['amount']      !== '') ? (float) $row['amount']      : 0.0;
        $paidAmount = (isset($row['paid_amount']) && $row['paid_amount'] !== '') ? (float) $row['paid_amount'] : 0.0;

        $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
        if (!in_array($status, ['pending', 'partial', 'paid', 'overdue'])) {
            $status = 'pending';
        }

        // Mirror the system's auto-correction logic from getStudentBase() / FeePaymentController.
        if ($amount > 0 && $paidAmount >= $amount) {
            $status = 'paid';
        } elseif ($paidAmount > 0 && $paidAmount < $amount) {
            $status = 'partial';
        }

        // ── Resolve paid_at ───────────────────────────────────────────────────
        $paidAt = null;
        if ($status === 'paid') {
            $paidAt = (!empty($row['paid_at'])) ? $row['paid_at'] : now();
        }

        // ── Insert ────────────────────────────────────────────────────────────
        DB::table('fee_payments')->insert([
            'student_id'  => $studentId,
            'type'        => $type,
            'course_id'   => $courseId,
            'semester'    => $semester,
            'year'        => $year,
            'amount'      => $amount,
            'paid_amount' => $paidAmount,
            'status'      => $status,
            'paid_at'     => $paidAt,
            'created_at'  => $row['created_at'] ?? now(),
        ]);

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
