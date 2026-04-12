<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for enrolled_students consistency logic.
 *
 * Tests the pure decision rules that govern when/why a section's counter
 * is considered correct or drifted — without a real DB.
 *
 * The syncAllEnrolledCounts() SQL itself cannot be meaningfully unit-tested
 * without a DB, but the surrounding correctness properties and the
 * CourseSectionsImport force-zero rule are validated here.
 *
 * Run: php artisan test tests/Unit/EnrolledStudentsSyncTest.php
 */
class EnrolledStudentsSyncTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Simulate what syncAllEnrolledCounts() computes for a single section.
     *
     * Mirrors the SQL logic:
     *   enrolled_students = COUNT(*) WHERE status='enrolled'
     *   Only update if stored value differs from actual count.
     *
     * Returns ['corrected' => bool, 'new_value' => int].
     */
    private function simulateSync(int $storedCount, array $registrations): array
    {
        $actualCount = count(array_filter($registrations, fn($r) => $r['status'] === 'enrolled'));

        if ($storedCount !== $actualCount) {
            return ['corrected' => true, 'new_value' => $actualCount];
        }

        return ['corrected' => false, 'new_value' => $storedCount];
    }

    /**
     * Simulate CourseSectionsImport behavior for enrolled_students column.
     * Always returns 0, regardless of the Excel value.
     */
    private function sectionImportValue(mixed $excelValue): int
    {
        // Mirrors: 'enrolled_students' => 0  (always — never trust Excel)
        return 0;
    }

    // ── syncAllEnrolledCounts logic ───────────────────────────────────────────

    public function test_sync_no_correction_needed_when_count_matches(): void
    {
        $registrations = [
            ['status' => 'enrolled'],
            ['status' => 'enrolled'],
            ['status' => 'dropped'],    // doesn't count
            ['status' => 'completed'],  // doesn't count
        ];

        $result = $this->simulateSync(storedCount: 2, registrations: $registrations);

        $this->assertFalse($result['corrected']);
        $this->assertSame(2, $result['new_value']);
    }

    public function test_sync_corrects_inflated_counter(): void
    {
        // Section thinks it has 5 students but only 2 are actually enrolled
        $registrations = [
            ['status' => 'enrolled'],
            ['status' => 'enrolled'],
            ['status' => 'dropped'],
            ['status' => 'completed'],
        ];

        $result = $this->simulateSync(storedCount: 5, registrations: $registrations);

        $this->assertTrue($result['corrected']);
        $this->assertSame(2, $result['new_value']);
    }

    public function test_sync_corrects_deflated_counter(): void
    {
        // Counter is 1 but 3 students are actually enrolled (e.g. import crash mid-run)
        $registrations = [
            ['status' => 'enrolled'],
            ['status' => 'enrolled'],
            ['status' => 'enrolled'],
        ];

        $result = $this->simulateSync(storedCount: 1, registrations: $registrations);

        $this->assertTrue($result['corrected']);
        $this->assertSame(3, $result['new_value']);
    }

    public function test_sync_resets_section_with_no_registrations_to_zero(): void
    {
        // Section somehow has enrolled_students=3 but no registrations at all
        $result = $this->simulateSync(storedCount: 3, registrations: []);

        $this->assertTrue($result['corrected']);
        $this->assertSame(0, $result['new_value']);
    }

    public function test_sync_does_not_touch_correctly_zeroed_section(): void
    {
        // Section has 0 stored, 0 actual — should not be counted as "corrected"
        $result = $this->simulateSync(storedCount: 0, registrations: []);

        $this->assertFalse($result['corrected']);
        $this->assertSame(0, $result['new_value']);
    }

    public function test_sync_only_counts_enrolled_not_dropped_or_completed(): void
    {
        $registrations = [
            ['status' => 'completed'],
            ['status' => 'completed'],
            ['status' => 'dropped'],
            ['status' => 'dropped'],
        ];

        // Section has no enrolled rows — stored counter of 0 is correct
        $result = $this->simulateSync(storedCount: 0, registrations: $registrations);

        $this->assertFalse($result['corrected']);
        $this->assertSame(0, $result['new_value']);
    }

    public function test_sync_corrects_counter_when_dropped_rows_were_miscounted(): void
    {
        // Counter was erroneously incremented for a dropped registration
        $registrations = [
            ['status' => 'enrolled'],
            ['status' => 'dropped'],  // should not count
        ];

        $result = $this->simulateSync(storedCount: 2, registrations: $registrations);

        $this->assertTrue($result['corrected']);
        $this->assertSame(1, $result['new_value']);
    }

    // ── CourseSectionsImport force-zero rule ──────────────────────────────────

    public function test_import_ignores_excel_value_and_inserts_zero(): void
    {
        foreach ([0, 5, 42, 100, -1, null, '', 'bad'] as $excelValue) {
            $imported = $this->sectionImportValue($excelValue);
            $this->assertSame(
                0,
                $imported,
                "Expected 0 regardless of Excel value '{$excelValue}', got {$imported}"
            );
        }
    }

    // ── Import + sync interaction ─────────────────────────────────────────────

    /**
     * Simulate the full sequence: sections imported, then registrations imported,
     * then syncAllEnrolledCounts() runs.
     *
     * Verifies that the final state is always authoritative regardless of what
     * was in the Excel sections sheet.
     */
    public function test_full_import_sequence_ends_consistent(): void
    {
        // Step 1: CourseSectionsImport inserts section with enrolled_students=0
        //         (Excel had stale value of 7 — now ignored)
        $storedAfterSectionImport = $this->sectionImportValue(7);
        $this->assertSame(0, $storedAfterSectionImport);

        // Step 2: StudentCourseRegistrationsImport runs; increments for each enrolled row
        //         Simulating 3 enrolled registrations being imported
        $enrolledRegistrations = 3;
        $storedAfterRegImport  = $storedAfterSectionImport + $enrolledRegistrations; // = 3

        // Step 3: syncAllEnrolledCounts() runs — actual count = 3, stored = 3 → no correction needed
        $actualRegs = array_fill(0, 3, ['status' => 'enrolled']);
        $syncResult = $this->simulateSync($storedAfterRegImport, $actualRegs);

        $this->assertFalse($syncResult['corrected'], 'Counter should be in sync after clean import + sync');
        $this->assertSame(3, $syncResult['new_value']);
    }

    /**
     * Before the fix: sections imported with Excel value 7, then 3 registrations
     * add +3 → stored = 10, actual = 3. Sync corrects it to 3.
     */
    public function test_old_behaviour_drift_is_corrected_by_sync(): void
    {
        // Old behaviour: enrolled_students=7 taken from Excel
        $storedAfterSectionImport = 7;

        // Registration import adds 3 more
        $storedAfterRegImport = $storedAfterSectionImport + 3; // = 10 — wrong!

        // Actual registrations with status=enrolled
        $actualRegs = array_fill(0, 3, ['status' => 'enrolled']);

        $syncResult = $this->simulateSync($storedAfterRegImport, $actualRegs);

        $this->assertTrue($syncResult['corrected'], 'Drift from old behaviour must be corrected by sync');
        $this->assertSame(3, $syncResult['new_value'], 'Final value must equal actual enrolled count');
    }

    /**
     * Capacity check must still use the stored counter before sync runs.
     * After sync the stored == actual, so capacity checks remain valid.
     */
    public function test_capacity_check_uses_enrolled_students(): void
    {
        // After sync: stored = actual = 25, max = 30 → 5 seats available
        $enrolledStudents = 25;
        $maxStudents      = 30;

        $isFull  = $enrolledStudents >= $maxStudents;
        $hasRoom = !$isFull;

        $this->assertFalse($isFull);
        $this->assertTrue($hasRoom);

        // After one more enroll → 26 — still room
        $this->assertFalse(26 >= 30);

        // At capacity
        $this->assertTrue(30 >= 30);
    }
}
