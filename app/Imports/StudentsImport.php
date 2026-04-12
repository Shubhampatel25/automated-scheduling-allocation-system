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
    public array $errors      = [];
    public array $failures    = [];
    public array $skipReasons = [];  // human-readable per-row skip messages
    public int   $imported    = 0;
    public int   $skipped     = 0;

    // Row 1 is the heading; data rows start at 2.
    private int $currentRow = 1;

    public function model(array $row)
    {
        $rowNum = ++$this->currentRow;
        $row    = $this->normalize($row);

        // ── 1. roll_no required ───────────────────────────────────────────────
        $rollNo = trim((string) ($row['roll_no'] ?? ''));
        if ($rollNo === '') {
            return $this->skip($rowNum, 'roll_no is required');
        }

        // ── 2. name required ─────────────────────────────────────────────────
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return $this->skip($rowNum, "roll_no={$rollNo}: name is required");
        }

        // ── 3. email – required and must look like an email ───────────────────
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if ($email === '') {
            return $this->skip($rowNum, "roll_no={$rollNo}: email is required");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->skip($rowNum, "roll_no={$rollNo}: email '{$email}' is not valid");
        }

        // ── 4. semester must be 1–8 ───────────────────────────────────────────
        $semester = isset($row['semester']) && $row['semester'] !== '' ? (int) $row['semester'] : 1;
        if ($semester < 1 || $semester > 8) {
            return $this->skip($rowNum, "roll_no={$rollNo}: semester={$semester} is out of range (1–8)");
        }

        // ── 5. status must be active | inactive ───────────────────────────────
        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive'], true)) {
            return $this->skip($rowNum, "roll_no={$rollNo}: status='{$status}' is invalid (active | inactive)");
        }

        // ── 6. Resolve department (id or code) — must exist ──────────────────
        $deptId = null;
        if (!empty($row['department_id']) && is_numeric($row['department_id'])) {
            $deptId = (int) $row['department_id'];
            if (!DB::table('departments')->where('id', $deptId)->exists()) {
                return $this->skip($rowNum, "roll_no={$rollNo}: department_id={$deptId} not found");
            }
        } elseif (!empty($row['department_code'])) {
            $code   = strtoupper(trim((string) $row['department_code']));
            $deptId = DB::table('departments')->where('code', $code)->value('id');
            if (!$deptId) {
                return $this->skip($rowNum, "roll_no={$rollNo}: department_code='{$code}' not found");
            }
        } else {
            return $this->skip($rowNum, "roll_no={$rollNo}: department_id or department_code is required");
        }

        // ── 7. user_id — must exist in users if supplied ──────────────────────
        $userId = null;
        if (isset($row['user_id']) && $row['user_id'] !== '') {
            if (!is_numeric($row['user_id'])) {
                return $this->skip($rowNum, "roll_no={$rollNo}: user_id='{$row['user_id']}' is not numeric");
            }
            $userId = (int) $row['user_id'];
            if (!DB::table('users')->where('id', $userId)->exists()) {
                return $this->skip($rowNum, "roll_no={$rollNo}: user_id={$userId} not found in users");
            }
        }

        // ── 8. Duplicate check on roll_no ─────────────────────────────────────
        if (DB::table('students')->where('roll_no', $rollNo)->exists()) {
            return $this->skip($rowNum, "roll_no={$rollNo}: already exists (duplicate)");
        }

        // ── 9. Insert ─────────────────────────────────────────────────────────
        $insertId = isset($row['id']) && is_numeric($row['id']) && (int)$row['id'] > 0
            ? (int) $row['id']
            : null;

        DB::table('students')->insertOrIgnore([
            'id'              => $insertId,
            'user_id'         => $userId,
            'roll_no'         => $rollNo,
            'name'            => $name,
            'email'           => $email,
            'department_id'   => $deptId,
            'semester'        => $semester,
            'semester_1_result' => isset($row['semester_1_result']) && $row['semester_1_result'] !== ''
                                    ? trim((string) $row['semester_1_result'])
                                    : null,
            'status'          => $status,
            'created_at'      => $row['created_at'] ?? now(),
        ]);

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
