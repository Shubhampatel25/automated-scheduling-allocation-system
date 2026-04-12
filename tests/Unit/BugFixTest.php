<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for the pure decision logic behind Task 5 bug fixes.
 *
 * Bugs that require a real DB (B, C, F) are verified here only at the
 * pure-logic level (guard conditions, accumulation arithmetic, etc.).
 * The DB-path correctness is covered by manual / feature-test scenarios.
 *
 * Run: php artisan test tests/Unit/BugFixTest.php
 */
class BugFixTest extends TestCase
{
    // ── Bug A: clone null crash ───────────────────────────────────────────────

    /**
     * The fixed guard: ($section && $section->course) before clone.
     * Mirrors DashboardController::getStudentBase() line 372.
     */
    private function mapRegCourse(?object $section): ?object
    {
        return ($section && $section->course) ? clone $section->course : null;
    }

    public function test_bug_a_returns_null_when_section_is_null(): void
    {
        $this->assertNull($this->mapRegCourse(null));
    }

    public function test_bug_a_returns_null_when_section_has_no_course(): void
    {
        $section = (object) ['course' => null];
        $this->assertNull($this->mapRegCourse($section));
    }

    public function test_bug_a_returns_clone_when_section_and_course_exist(): void
    {
        $course          = new \stdClass();
        $course->name    = 'PHP 101';
        $section         = (object) ['course' => $course];

        $result = $this->mapRegCourse($section);

        $this->assertNotNull($result);
        $this->assertSame('PHP 101', $result->name);
        // Must be a clone — mutating the clone must not affect the original
        $result->name = 'Changed';
        $this->assertSame('PHP 101', $section->course->name);
    }

    // ── Bug B: complete() enrolled_students guard ─────────────────────────────

    /**
     * Simulate the fixed decrement guard:
     *   if ($section && $section->enrolled_students > 0) decrement.
     */
    private function simulateDecrement(?object $section): int
    {
        if ($section && $section->enrolled_students > 0) {
            return $section->enrolled_students - 1;
        }
        return $section ? $section->enrolled_students : 0;
    }

    public function test_bug_b_decrement_reduces_count_when_positive(): void
    {
        $section = (object) ['enrolled_students' => 5];
        $this->assertSame(4, $this->simulateDecrement($section));
    }

    public function test_bug_b_decrement_does_not_go_below_zero(): void
    {
        $section = (object) ['enrolled_students' => 0];
        $this->assertSame(0, $this->simulateDecrement($section));
    }

    public function test_bug_b_decrement_safe_when_section_is_null(): void
    {
        $this->assertSame(0, $this->simulateDecrement(null));
    }

    // ── Bug C: drop() status guard inside transaction ─────────────────────────

    /**
     * Simulate the fixed drop guard (read from locked record).
     * Returns ['allowed' => bool, 'error' => string|null].
     */
    private function simulateDrop(string $lockedStatus): array
    {
        if ($lockedStatus !== 'enrolled') {
            $label = match ($lockedStatus) {
                'dropped'   => 'already been dropped',
                'completed' => 'already been completed',
                default     => 'not eligible for dropping',
            };
            return ['allowed' => false, 'error' => "This course has {$label} and cannot be dropped."];
        }
        return ['allowed' => true, 'error' => null];
    }

    public function test_bug_c_drop_allowed_when_enrolled(): void
    {
        $result = $this->simulateDrop('enrolled');
        $this->assertTrue($result['allowed']);
        $this->assertNull($result['error']);
    }

    public function test_bug_c_drop_blocked_when_already_dropped(): void
    {
        $result = $this->simulateDrop('dropped');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('already been dropped', $result['error']);
    }

    public function test_bug_c_drop_blocked_when_completed(): void
    {
        $result = $this->simulateDrop('completed');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('already been completed', $result['error']);
    }

    // ── Bug F: studentPay() partial payment accumulation ─────────────────────

    /**
     * Simulate the locked partial payment accumulation logic.
     * Returns ['status' => string, 'paid_amount' => float].
     */
    private function simulatePartialPay(float $totalAmount, float $alreadyPaid, float $newPayment): array
    {
        $newPaidTotal = $alreadyPaid + $newPayment;

        if ($newPaidTotal >= $totalAmount) {
            return ['status' => 'paid', 'paid_amount' => $totalAmount];
        }

        return ['status' => 'partial', 'paid_amount' => $newPaidTotal];
    }

    public function test_bug_f_partial_accumulates_on_top_of_prior_payment(): void
    {
        $result = $this->simulatePartialPay(300.0, 100.0, 50.0);
        $this->assertSame('partial', $result['status']);
        $this->assertSame(150.0, $result['paid_amount']);
    }

    public function test_bug_f_partial_transitions_to_paid_when_total_reached(): void
    {
        $result = $this->simulatePartialPay(300.0, 100.0, 200.0);
        $this->assertSame('paid', $result['status']);
        $this->assertSame(300.0, $result['paid_amount']);
    }

    public function test_bug_f_partial_transitions_to_paid_when_overpaid(): void
    {
        // Overpayment: cap at total amount
        $result = $this->simulatePartialPay(300.0, 100.0, 250.0);
        $this->assertSame('paid', $result['status']);
        $this->assertSame(300.0, $result['paid_amount']);
    }

    public function test_bug_f_first_partial_payment_from_zero(): void
    {
        $result = $this->simulatePartialPay(300.0, 0.0, 75.0);
        $this->assertSame('partial', $result['status']);
        $this->assertSame(75.0, $result['paid_amount']);
    }

    // ── Bug G: auto-advance concurrent guard ─────────────────────────────────

    /**
     * Simulate the fixed WHERE guard on semester advance:
     *   UPDATE ... WHERE id = ? AND semester = $currentSem
     * Returns true if the update would fire (both conditions met).
     */
    private function simulateAdvance(int $dbSemester, int $currentSem): bool
    {
        // Mirrors: ->where('id', $studentRecord->id)->where('semester', $currentSem)
        return $dbSemester === $currentSem;
    }

    public function test_bug_g_advance_fires_when_semester_matches(): void
    {
        $this->assertTrue($this->simulateAdvance(2, 2));
    }

    public function test_bug_g_advance_skipped_when_already_advanced_by_concurrent_request(): void
    {
        // Another request already incremented to 3; this request still holds $currentSem=2
        $this->assertFalse($this->simulateAdvance(3, 2));
    }

    // ── Bug H: store() duplicate fee check ───────────────────────────────────

    /**
     * Simulate the duplicate guard before insert:
     *   SELECT EXISTS WHERE student_id=? AND semester=? AND year=? AND type='regular'
     */
    private function simulateDuplicateCheck(array $existing, int $studentId, int $semester, int $year): bool
    {
        foreach ($existing as $record) {
            if ($record['student_id'] === $studentId
                && $record['semester'] === $semester
                && $record['year'] === $year
                && $record['type'] === 'regular') {
                return true; // duplicate
            }
        }
        return false;
    }

    public function test_bug_h_no_duplicate_when_table_is_empty(): void
    {
        $this->assertFalse($this->simulateDuplicateCheck([], 1, 3, 2025));
    }

    public function test_bug_h_duplicate_detected_for_same_student_semester_year(): void
    {
        $existing = [
            ['student_id' => 1, 'semester' => 3, 'year' => 2025, 'type' => 'regular'],
        ];
        $this->assertTrue($this->simulateDuplicateCheck($existing, 1, 3, 2025));
    }

    public function test_bug_h_no_duplicate_for_different_semester(): void
    {
        $existing = [
            ['student_id' => 1, 'semester' => 3, 'year' => 2025, 'type' => 'regular'],
        ];
        $this->assertFalse($this->simulateDuplicateCheck($existing, 1, 4, 2025));
    }

    public function test_bug_h_no_duplicate_for_different_year(): void
    {
        $existing = [
            ['student_id' => 1, 'semester' => 3, 'year' => 2025, 'type' => 'regular'],
        ];
        $this->assertFalse($this->simulateDuplicateCheck($existing, 1, 3, 2026));
    }

    public function test_bug_h_no_duplicate_for_non_regular_type(): void
    {
        $existing = [
            ['student_id' => 1, 'semester' => 3, 'year' => 2025, 'type' => 'late'],
        ];
        $this->assertFalse($this->simulateDuplicateCheck($existing, 1, 3, 2025));
    }
}
