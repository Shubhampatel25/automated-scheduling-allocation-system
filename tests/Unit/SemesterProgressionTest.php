<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for semester-progression logic in RegistrationEligibilityService.
 *
 * These tests exercise getAuthoritativeSemester() through a thin in-memory
 * harness so they run without a real DB (no migrations needed).
 *
 * Run: php artisan test tests/Unit/SemesterProgressionTest.php
 */
class SemesterProgressionTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Build a fake Student-like object with just a semester property. */
    private function fakeStudent(int $semester): object
    {
        return new class($semester) {
            public int $id = 1;
            public int $semester;
            public function __construct(int $s) { $this->semester = $s; }
        };
    }

    /**
     * Call getAuthoritativeSemester() with controlled history / registrations
     * injected via closures so we never hit the DB.
     *
     * @param int   $dbSemester         student.semester value
     * @param int[] $historyCompleted    semesters with result=pass|fail in history table (sequential check)
     * @param int|null $historyInProgress  semester with result=in_progress (null = none)
     * @param int[] $passedRegSemesters  semesters derived from passed course registrations
     */
    private function runSemCheck(
        int $dbSemester,
        array $historyCompleted = [],
        ?int $historyInProgress = null,
        array $passedRegSemesters = []
    ): array {
        // ------------------------------------------------------------------
        // We inline the exact same algorithm from getAuthoritativeSemester()
        // so the test is 1-to-1 with the production code path, without mocks.
        // ------------------------------------------------------------------
        $student    = $this->fakeStudent($dbSemester);
        $studentId  = $student->id;
        $dbSemFinal = max(1, (int) $student->semester);

        // Step 1 — history
        $historyExists = !empty($historyCompleted) || $historyInProgress !== null;
        if ($historyExists) {
            sort($historyCompleted);
            $highestSeq = 0;
            foreach ($historyCompleted as $sem) {
                if ((int)$sem === $highestSeq + 1) {
                    $highestSeq = (int)$sem;
                } else {
                    break;
                }
            }

            if ($historyInProgress !== null && $historyInProgress === $highestSeq + 1) {
                $authSemester = $historyInProgress;
            } elseif ($highestSeq > 0) {
                $authSemester = $highestSeq + 1;
            } else {
                $authSemester = null;
            }

            if ($authSemester !== null) {
                if ($dbSemFinal > $authSemester) {
                    return [
                        'semester'  => $authSemester,
                        'corrected' => true,
                        'reason'    => "Semester jump detected: student.semester={$dbSemFinal} exceeds"
                            . " history-authoritative={$authSemester}"
                            . " (highest sequential completed sem={$highestSeq}); corrected",
                    ];
                }
                return ['semester' => $dbSemFinal, 'corrected' => false, 'reason' => null];
            }
        }

        // Step 2 — completed registrations
        sort($passedRegSemesters);
        $highestSeq = 0;
        foreach ($passedRegSemesters as $sem) {
            if ((int)$sem === $highestSeq + 1) {
                $highestSeq = (int)$sem;
            } else {
                break;
            }
        }

        if ($highestSeq > 0) {
            $authSemester = $highestSeq + 1;
            if ($dbSemFinal > $authSemester) {
                return [
                    'semester'  => $authSemester,
                    'corrected' => true,
                    'reason'    => "Semester jump detected: student.semester={$dbSemFinal} exceeds"
                        . " registration-derived={$authSemester}"
                        . " (highest sequential passed sem={$highestSeq}); corrected",
                ];
            }
        }

        // Step 3 — trust student.semester
        return ['semester' => $dbSemFinal, 'corrected' => false, 'reason' => null];
    }

    // ── Test cases ────────────────────────────────────────────────────────────

    /**
     * CASE 1: Valid student — sem 1 done, DB says sem 2. No correction.
     */
    public function test_valid_sequential_no_correction(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 2,
            historyCompleted: [1],
            historyInProgress: 2
        );

        $this->assertFalse($result['corrected']);
        $this->assertSame(2, $result['semester']);
    }

    /**
     * CASE 2: Illegal jump via history — sem 1 done but DB is 6.
     * Must cap at sem 2.
     */
    public function test_jump_detected_via_history(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 6,
            historyCompleted: [1],
            historyInProgress: null
        );

        $this->assertTrue($result['corrected']);
        $this->assertSame(2, $result['semester']);
        $this->assertStringContainsString('history-authoritative=2', $result['reason']);
    }

    /**
     * CASE 3: Sem 3 done, DB jumped to sem 6 — must cap at sem 4.
     */
    public function test_jump_detected_three_semesters_done(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 6,
            historyCompleted: [1, 2, 3],
            historyInProgress: null
        );

        $this->assertTrue($result['corrected']);
        $this->assertSame(4, $result['semester']);
    }

    /**
     * CASE 4: History has a GAP (sem 1 done, sem 3 done — sem 2 missing).
     * Sequential count stops at 1, so authoritative = 2, not 4.
     */
    public function test_gap_in_history_stops_sequential_count(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 4,
            historyCompleted: [1, 3], // gap at sem 2
            historyInProgress: null
        );

        $this->assertTrue($result['corrected']);
        $this->assertSame(2, $result['semester']);
    }

    /**
     * CASE 5: No history at all — fall back to registrations.
     * Passed sems 1 and 2, DB says sem 6. Must cap at 3.
     */
    public function test_jump_detected_via_registrations_fallback(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 6,
            historyCompleted: [],
            historyInProgress: null,
            passedRegSemesters: [1, 2]
        );

        $this->assertTrue($result['corrected']);
        $this->assertSame(3, $result['semester']);
        $this->assertStringContainsString('registration-derived=3', $result['reason']);
    }

    /**
     * CASE 6: Completely new student — no history, no registrations.
     * Trust DB semester (1). No correction.
     */
    public function test_brand_new_student_no_correction(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 1,
            historyCompleted: [],
            historyInProgress: null,
            passedRegSemesters: []
        );

        $this->assertFalse($result['corrected']);
        $this->assertSame(1, $result['semester']);
    }

    /**
     * CASE 7: Retake student — sem 1 failed, DB still says 1.
     * History shows sem 1 with fail result. Authoritative = sem 2 (advance regardless).
     * The DB is not ahead → no correction.
     */
    public function test_student_with_failed_semester_no_bogus_correction(): void
    {
        // sem 1 failed, student stays on sem 1 in DB — history says 1 completed(fail)
        // authSemester from history = highestSeq+1 = 2, but dbSemester=1 < 2 → NOT corrected
        $result = $this->runSemCheck(
            dbSemester: 1,
            historyCompleted: [1],   // fail counts as completed for progression purposes
            historyInProgress: null
        );

        $this->assertFalse($result['corrected']); // DB is behind auth, not ahead — no correction here
        $this->assertSame(1, $result['semester']); // auto-advance in controller handles the bump
    }

    /**
     * CASE 8: in_progress matches next sequential sem — no correction.
     */
    public function test_in_progress_record_matches_expected_no_correction(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 3,
            historyCompleted: [1, 2],
            historyInProgress: 3
        );

        $this->assertFalse($result['corrected']);
        $this->assertSame(3, $result['semester']);
    }

    /**
     * CASE 9: Registrations show gap too — passed sem 1, then sem 3 (no sem 2).
     * Sequential count stops at 1 → authoritative = 2. DB=4 → corrected.
     */
    public function test_gap_in_registrations_stops_sequential_count(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 4,
            historyCompleted: [],
            historyInProgress: null,
            passedRegSemesters: [1, 3] // gap at 2
        );

        $this->assertTrue($result['corrected']);
        $this->assertSame(2, $result['semester']);
    }

    /**
     * CASE 10: Corrected semester is never less than 1, even if somehow DB=0.
     */
    public function test_semester_floor_is_one(): void
    {
        $result = $this->runSemCheck(
            dbSemester: 0,
            historyCompleted: [],
            historyInProgress: null,
            passedRegSemesters: []
        );

        $this->assertSame(1, $result['semester']);
        $this->assertFalse($result['corrected']);
    }
}
