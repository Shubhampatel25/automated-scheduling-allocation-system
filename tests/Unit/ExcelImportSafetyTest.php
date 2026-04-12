<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for Excel import validation rules.
 *
 * These tests exercise the pure validation logic from both importers
 * in-memory without a real DB or Excel file. They replicate the exact
 * decision tree in model() through thin helpers so they run fast and
 * remain 1-to-1 with production code paths.
 *
 * Run: php artisan test tests/Unit/ExcelImportSafetyTest.php
 */
class ExcelImportSafetyTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════════════════
    // Helpers — inline the pure validation logic from both importers
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Run StudentsImport validation rules against a row and return
     * ['ok' => bool, 'reason' => string|null].
     *
     * $dbState simulates DB lookups:
     *   exists_roll_no   : bool   — whether the roll_no already exists
     *   dept_id_exists   : bool   — whether the department_id exists
     *   dept_code_id     : int|null — resolved id from department_code
     *   user_id_exists   : bool   — whether user_id exists
     */
    private function validateStudentRow(array $row, array $dbState = []): array
    {
        $row = array_combine(
            array_map(fn($k) => strtolower(trim($k)), array_keys($row)),
            array_values($row)
        );

        $rollNo = trim((string) ($row['roll_no'] ?? ''));
        if ($rollNo === '') return ['ok' => false, 'reason' => 'roll_no is required'];

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') return ['ok' => false, 'reason' => "roll_no={$rollNo}: name is required"];

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if ($email === '') return ['ok' => false, 'reason' => "roll_no={$rollNo}: email is required"];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'reason' => "roll_no={$rollNo}: email '{$email}' is not valid"];
        }

        $semester = isset($row['semester']) && $row['semester'] !== '' ? (int) $row['semester'] : 1;
        if ($semester < 1 || $semester > 8) {
            return ['ok' => false, 'reason' => "roll_no={$rollNo}: semester={$semester} is out of range (1–8)"];
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive'], true)) {
            return ['ok' => false, 'reason' => "roll_no={$rollNo}: status='{$status}' is invalid (active | inactive)"];
        }

        // Department resolution
        if (!empty($row['department_id']) && is_numeric($row['department_id'])) {
            $deptId = (int) $row['department_id'];
            if (!($dbState['dept_id_exists'] ?? true)) {
                return ['ok' => false, 'reason' => "roll_no={$rollNo}: department_id={$deptId} not found"];
            }
        } elseif (!empty($row['department_code'])) {
            $code   = strtoupper(trim((string) $row['department_code']));
            $deptId = $dbState['dept_code_id'] ?? null;
            if (!$deptId) {
                return ['ok' => false, 'reason' => "roll_no={$rollNo}: department_code='{$code}' not found"];
            }
        } else {
            return ['ok' => false, 'reason' => "roll_no={$rollNo}: department_id or department_code is required"];
        }

        // user_id
        if (isset($row['user_id']) && $row['user_id'] !== '') {
            if (!is_numeric($row['user_id'])) {
                return ['ok' => false, 'reason' => "roll_no={$rollNo}: user_id='{$row['user_id']}' is not numeric"];
            }
            if (!($dbState['user_id_exists'] ?? true)) {
                return ['ok' => false, 'reason' => "roll_no={$rollNo}: user_id={$row['user_id']} not found in users"];
            }
        }

        // Duplicate
        if ($dbState['exists_roll_no'] ?? false) {
            return ['ok' => false, 'reason' => "roll_no={$rollNo}: already exists (duplicate)"];
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * Run StudentCourseRegistrationsImport validation rules against a row.
     *
     * $dbState simulates DB lookups:
     *   student_exists       : bool    — student_id found in DB
     *   roll_no_student_id   : int|null — resolved id from roll_no
     *   section_exists       : bool    — course_section_id found
     *   course_dept_id       : int     — department of the section's course
     *   course_semester      : int|null — semester of the section's course
     *   student_dept_id      : int     — department of the student
     *   student_semester     : int     — current semester of the student
     *   duplicate_exists     : bool    — active/completed record already exists
     */
    private function validateRegistrationRow(array $row, array $dbState = []): array
    {
        $row = array_combine(
            array_map(fn($k) => strtolower(trim($k)), array_keys($row)),
            array_values($row)
        );

        // Resolve student
        $studentId = null;
        if (!empty($row['student_id']) && is_numeric($row['student_id'])) {
            $studentId = (int) $row['student_id'];
            if (!($dbState['student_exists'] ?? true)) {
                return ['ok' => false, 'reason' => "student_id={$studentId} does not exist"];
            }
        } elseif (!empty($row['roll_no'])) {
            $studentId = $dbState['roll_no_student_id'] ?? null;
            if (!$studentId) {
                return ['ok' => false, 'reason' => "roll_no='{$row['roll_no']}' not found in students"];
            }
        } else {
            return ['ok' => false, 'reason' => 'student_id or roll_no is required'];
        }

        // Resolve section
        if (empty($row['course_section_id']) || !is_numeric($row['course_section_id'])) {
            return ['ok' => false, 'reason' => 'course_section_id is required and must be numeric'];
        }
        if (!($dbState['section_exists'] ?? true)) {
            return ['ok' => false, 'reason' => "course_section_id={$row['course_section_id']} not found"];
        }

        // Status
        $status = strtolower(trim((string) ($row['status'] ?? 'enrolled')));
        if (!in_array($status, ['enrolled', 'completed', 'dropped'], true)) {
            $status = 'enrolled';
        }

        // Result
        $rawResult = isset($row['result']) && $row['result'] !== ''
            ? strtolower(trim((string) $row['result']))
            : null;
        if ($rawResult !== null && !in_array($rawResult, ['pass', 'fail'], true)) {
            $rawResult = null;
        }

        // RULE: completed must have pass|fail
        if ($status === 'completed' && !in_array($rawResult, ['pass', 'fail'], true)) {
            return ['ok' => false, 'reason' => "status=completed requires result=pass or result=fail (got: " . ($rawResult ?? 'null') . ")"];
        }

        // RULE: enrolled clears result silently
        if ($status === 'enrolled') {
            $rawResult = null;
        }

        // Department match
        $studentDeptId  = $dbState['student_dept_id']  ?? null;
        $courseDeptId   = $dbState['course_dept_id']   ?? null;
        if ($studentDeptId && $courseDeptId && (int)$studentDeptId !== (int)$courseDeptId) {
            return ['ok' => false, 'reason' => "department mismatch: student department_id={$studentDeptId} does not match section department_id={$courseDeptId}"];
        }

        // Semester consistency (enrolled only)
        if ($status === 'enrolled') {
            $courseSem   = $dbState['course_semester']  ?? null;
            $studentSem  = $dbState['student_semester'] ?? null;
            if ($courseSem !== null && $studentSem !== null && (int)$courseSem > (int)$studentSem) {
                return ['ok' => false, 'reason' => "semester mismatch: course belongs to semester {$courseSem} but student is in semester {$studentSem} (forward enrollment is not allowed)"];
            }
        }

        // Duplicate
        if ($dbState['duplicate_exists'] ?? false) {
            return ['ok' => false, 'reason' => "duplicate: student_id={$studentId} already has an active/completed record for course_section_id={$row['course_section_id']}"];
        }

        return ['ok' => true, 'reason' => null, 'result' => $rawResult, 'status' => $status];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // StudentsImport tests
    // ═══════════════════════════════════════════════════════════════════════

    public function test_student_valid_row_passes(): void
    {
        $result = $this->validateStudentRow([
            'roll_no'       => 'S-CS-001',
            'name'          => 'Alice',
            'email'         => 'alice@example.com',
            'semester'      => 2,
            'status'        => 'active',
            'department_id' => 1,
        ], ['dept_id_exists' => true, 'exists_roll_no' => false]);

        $this->assertTrue($result['ok']);
    }

    public function test_student_skip_missing_roll_no(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => '',
            'name'    => 'Alice',
            'email'   => 'alice@example.com',
            'department_id' => 1,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('roll_no is required', $result['reason']);
    }

    public function test_student_skip_missing_name(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-002', 'name' => '', 'email' => 'x@x.com', 'department_id' => 1,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('name is required', $result['reason']);
    }

    public function test_student_skip_missing_email(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-003', 'name' => 'Bob', 'email' => '', 'department_id' => 1,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('email is required', $result['reason']);
    }

    public function test_student_skip_invalid_email_format(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-003', 'name' => 'Bob', 'email' => 'not-an-email', 'department_id' => 1,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('is not valid', $result['reason']);
    }

    public function test_student_skip_semester_zero(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-004', 'name' => 'Carol', 'email' => 'c@c.com',
            'department_id' => 1, 'semester' => 0,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('out of range', $result['reason']);
    }

    public function test_student_skip_semester_nine(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-005', 'name' => 'Dave', 'email' => 'd@d.com',
            'department_id' => 1, 'semester' => 9,
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('out of range', $result['reason']);
    }

    public function test_student_skip_invalid_status(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-006', 'name' => 'Eve', 'email' => 'e@e.com',
            'department_id' => 1, 'status' => 'graduated',
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString("status='graduated' is invalid", $result['reason']);
    }

    public function test_student_skip_department_id_not_found(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-007', 'name' => 'Frank', 'email' => 'f@f.com',
            'department_id' => 99,
        ], ['dept_id_exists' => false]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('department_id=99 not found', $result['reason']);
    }

    public function test_student_skip_department_code_not_found(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-008', 'name' => 'Grace', 'email' => 'g@g.com',
            'department_code' => 'BADCODE',
        ], ['dept_code_id' => null]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString("department_code='BADCODE' not found", $result['reason']);
    }

    public function test_student_skip_missing_department(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-009', 'name' => 'Hank', 'email' => 'h@h.com',
        ]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('department_id or department_code is required', $result['reason']);
    }

    public function test_student_skip_user_id_not_found(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-010', 'name' => 'Iris', 'email' => 'i@i.com',
            'department_id' => 1, 'user_id' => 999,
        ], ['dept_id_exists' => true, 'user_id_exists' => false]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('user_id=999 not found in users', $result['reason']);
    }

    public function test_student_skip_duplicate_roll_no(): void
    {
        $result = $this->validateStudentRow([
            'roll_no' => 'S-CS-001', 'name' => 'Alice', 'email' => 'a@a.com', 'department_id' => 1,
        ], ['dept_id_exists' => true, 'exists_roll_no' => true]);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('already exists (duplicate)', $result['reason']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // StudentCourseRegistrationsImport tests
    // ═══════════════════════════════════════════════════════════════════════

    private function baseDbState(): array
    {
        return [
            'student_exists'     => true,
            'section_exists'     => true,
            'course_dept_id'     => 1,
            'course_semester'    => 2,
            'student_dept_id'    => 1,
            'student_semester'   => 2,
            'duplicate_exists'   => false,
        ];
    }

    public function test_registration_valid_enrolled_row(): void
    {
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $this->baseDbState());
        $this->assertTrue($result['ok']);
        $this->assertNull($result['result']); // enrolled has no result
    }

    public function test_registration_valid_completed_pass(): void
    {
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5,
            'status' => 'completed', 'result' => 'pass',
        ], $this->baseDbState());
        $this->assertTrue($result['ok']);
    }

    public function test_registration_valid_completed_fail(): void
    {
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5,
            'status' => 'completed', 'result' => 'fail',
        ], $this->baseDbState());
        $this->assertTrue($result['ok']);
    }

    public function test_registration_skip_completed_no_result(): void
    {
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'completed',
        ], $this->baseDbState());
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('requires result=pass or result=fail', $result['reason']);
    }

    public function test_registration_skip_completed_garbage_result(): void
    {
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5,
            'status' => 'completed', 'result' => 'grade_A',
        ], $this->baseDbState());
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('requires result=pass or result=fail', $result['reason']);
    }

    public function test_registration_enrolled_with_result_is_cleared_silently(): void
    {
        // enrolled + result='pass' → result is cleared, row is accepted
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5,
            'status' => 'enrolled', 'result' => 'pass',
        ], $this->baseDbState());
        $this->assertTrue($result['ok']);
        $this->assertNull($result['result'], 'result must be cleared for enrolled rows');
    }

    public function test_registration_skip_student_not_found_by_id(): void
    {
        $state = array_merge($this->baseDbState(), ['student_exists' => false]);
        $result = $this->validateRegistrationRow([
            'student_id' => 9999, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('student_id=9999 does not exist', $result['reason']);
    }

    public function test_registration_skip_student_not_found_by_roll_no(): void
    {
        $state = array_merge($this->baseDbState(), ['roll_no_student_id' => null]);
        $result = $this->validateRegistrationRow([
            'roll_no' => 'GHOST-001', 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString("roll_no='GHOST-001' not found", $result['reason']);
    }

    public function test_registration_skip_section_not_found(): void
    {
        $state = array_merge($this->baseDbState(), ['section_exists' => false]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 999, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('not found', $result['reason']);
    }

    public function test_registration_skip_department_mismatch(): void
    {
        $state = array_merge($this->baseDbState(), [
            'student_dept_id' => 1,
            'course_dept_id'  => 2,
        ]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('department mismatch', $result['reason']);
    }

    public function test_registration_skip_forward_semester_enrolled(): void
    {
        // Student is in semester 2, course belongs to semester 4 → block
        $state = array_merge($this->baseDbState(), [
            'student_semester' => 2,
            'course_semester'  => 4,
        ]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('forward enrollment is not allowed', $result['reason']);
    }

    public function test_registration_allow_retake_lower_semester_enrolled(): void
    {
        // Student is in semester 4, course belongs to semester 2 (retake) → allowed
        $state = array_merge($this->baseDbState(), [
            'student_semester' => 4,
            'course_semester'  => 2,
        ]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertTrue($result['ok'], 'Retakes at a lower semester must be allowed');
    }

    public function test_registration_completed_ignores_semester_check(): void
    {
        // Historical completed import: course sem 6, student now in sem 2 → allowed
        $state = array_merge($this->baseDbState(), [
            'student_semester' => 2,
            'course_semester'  => 6,
        ]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5,
            'status' => 'completed', 'result' => 'pass',
        ], $state);
        $this->assertTrue($result['ok'], 'Semester check must be skipped for completed rows (historical data)');
    }

    public function test_registration_skip_duplicate(): void
    {
        $state = array_merge($this->baseDbState(), ['duplicate_exists' => true]);
        $result = $this->validateRegistrationRow([
            'student_id' => 1, 'course_section_id' => 5, 'status' => 'enrolled',
        ], $state);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('duplicate', $result['reason']);
    }

    public function test_registration_skip_no_student_identifier(): void
    {
        $result = $this->validateRegistrationRow([
            'course_section_id' => 5, 'status' => 'enrolled',
        ], $this->baseDbState());
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('student_id or roll_no is required', $result['reason']);
    }
}
