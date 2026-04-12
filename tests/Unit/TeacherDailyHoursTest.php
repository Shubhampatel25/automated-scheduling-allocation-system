<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for the teacher 6-hour daily teaching-limit rule.
 *
 * The tests verify the pure arithmetic used by
 * TimetableConstraintService::teacherExceedsDailyHours().
 *
 * They run entirely in-memory (no DB, no migrations needed) by reproducing
 * the exact same calculation inline.
 *
 * Run: php artisan test tests/Unit/TeacherDailyHoursTest.php
 */
class TeacherDailyHoursTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Fake slot object with start_time / end_time strings, matching what
     * Eloquent would return from the DB query in teacherExceedsDailyHours().
     */
    private function slot(string $start, string $end): object
    {
        return (object) ['start_time' => $start, 'end_time' => $end];
    }

    /**
     * The exact arithmetic from TimetableConstraintService::teacherExceedsDailyHours().
     *
     * @param object[] $existingSlots  Already-booked slots on that day
     * @param string   $startTime      Proposed slot start (HH:MM:SS)
     * @param string   $endTime        Proposed slot end   (HH:MM:SS)
     * @param float    $maxHours       Daily cap (default 6.0)
     */
    private function wouldExceed(
        array  $existingSlots,
        string $startTime,
        string $endTime,
        float  $maxHours = 6.0
    ): bool {
        $existingHours = array_sum(array_map(
            fn($s) => (strtotime($s->end_time) - strtotime($s->start_time)) / 3600,
            $existingSlots
        ));

        $proposedHours = (strtotime($endTime) - strtotime($startTime)) / 3600;

        return ($existingHours + $proposedHours) > $maxHours;
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * CASE 1: No existing slots + 1.5 h proposed (08:00–09:30) → under limit.
     */
    public function test_single_slot_under_limit(): void
    {
        $result = $this->wouldExceed([], '08:00:00', '09:30:00');
        $this->assertFalse($result);
    }

    /**
     * CASE 2: Exactly 6 h of existing slots + any 0-second slot → still under
     * (limit is strictly >, not >=).
     * 4 × 90-minute slots = 6 h.
     */
    public function test_exactly_six_hours_is_not_exceeded(): void
    {
        $existing = [
            $this->slot('08:00:00', '09:30:00'), // 1.5 h
            $this->slot('09:40:00', '11:10:00'), // 1.5 h
            $this->slot('11:20:00', '12:50:00'), // 1.5 h
            $this->slot('13:50:00', '15:20:00'), // 1.5 h  → total 6.0 h
        ];

        // Proposing a zero-duration slot (edge case) → still exactly 6.0 → NOT exceeded
        $result = $this->wouldExceed($existing, '15:30:00', '15:30:00');
        $this->assertFalse($result, 'Exactly 6 h should not be blocked (limit is strictly >)');
    }

    /**
     * CASE 3: 4 × 90 min existing (6 h) + 1 more 90-min slot → over limit.
     */
    public function test_five_slots_exceeds_six_hours(): void
    {
        $existing = [
            $this->slot('08:00:00', '09:30:00'),
            $this->slot('09:40:00', '11:10:00'),
            $this->slot('11:20:00', '12:50:00'),
            $this->slot('13:50:00', '15:20:00'),
        ];

        $result = $this->wouldExceed($existing, '15:30:00', '17:00:00'); // +1.5 h → 7.5 h total
        $this->assertTrue($result);
    }

    /**
     * CASE 4: 3 × 90 min existing (4.5 h) + 90 min proposed = exactly 6 h → NOT exceeded.
     */
    public function test_three_plus_one_exactly_six(): void
    {
        $existing = [
            $this->slot('08:00:00', '09:30:00'),
            $this->slot('09:40:00', '11:10:00'),
            $this->slot('11:20:00', '12:50:00'),
        ];

        $result = $this->wouldExceed($existing, '13:50:00', '15:20:00'); // 4.5 + 1.5 = 6.0
        $this->assertFalse($result, '4.5 + 1.5 = exactly 6 h — should not be blocked');
    }

    /**
     * CASE 5: Non-standard slot length (2 h). Two 2-h slots + one 2.5-h slot = 6.5 h → exceeded.
     */
    public function test_non_standard_slot_duration(): void
    {
        $existing = [
            $this->slot('08:00:00', '10:00:00'), // 2 h
            $this->slot('10:00:00', '12:00:00'), // 2 h   → 4 h so far
        ];

        $result = $this->wouldExceed($existing, '13:00:00', '15:30:00'); // +2.5 h → 6.5 h
        $this->assertTrue($result);
    }

    /**
     * CASE 6: Non-standard slot — one 3-h slot existing + 3-h proposed = 6 h → NOT exceeded.
     */
    public function test_non_standard_exactly_six(): void
    {
        $existing = [$this->slot('08:00:00', '11:00:00')]; // 3 h

        $result = $this->wouldExceed($existing, '12:00:00', '15:00:00'); // +3 h → 6.0 h
        $this->assertFalse($result);
    }

    /**
     * CASE 7: Custom cap — test with maxHours = 4.0.
     * 2 × 90 min (3 h) + 90 min proposed = 4.5 h → exceeds 4-h cap.
     */
    public function test_custom_max_hours_cap(): void
    {
        $existing = [
            $this->slot('08:00:00', '09:30:00'),
            $this->slot('09:40:00', '11:10:00'),
        ];

        $result = $this->wouldExceed($existing, '11:20:00', '12:50:00', maxHours: 4.0);
        $this->assertTrue($result);
    }

    /**
     * CASE 8: excludeSlotId simulation — removing the old slot before summing
     * means the reschedule does NOT double-count the booking being replaced.
     *
     * Scenario: teacher has 4 slots (6 h). HOD moves slot #4 to a new time.
     * After exclusion: 3 slots (4.5 h) + 1.5 h proposed = 6.0 h → NOT exceeded.
     */
    public function test_exclude_slot_prevents_double_counting(): void
    {
        // Simulates what the query returns AFTER WHERE id != $excludeSlotId
        $slotsAfterExclusion = [
            $this->slot('08:00:00', '09:30:00'),
            $this->slot('09:40:00', '11:10:00'),
            $this->slot('11:20:00', '12:50:00'),
            // slot 4 (13:50–15:20) is excluded — it's the one being moved
        ];

        $result = $this->wouldExceed($slotsAfterExclusion, '13:50:00', '15:20:00'); // 4.5 + 1.5 = 6.0
        $this->assertFalse($result, 'Replacing a slot should not double-count the removed booking');
    }

    /**
     * CASE 9: Minute-precision overflow — 5 h 55 min existing + 10-min slot = 6 h 5 min → exceeded.
     */
    public function test_minute_precision_just_over(): void
    {
        // 5 h 55 min = 355 min
        $existing = [$this->slot('08:00:00', '13:55:00')];

        // +10 min → 6 h 5 min
        $result = $this->wouldExceed($existing, '14:00:00', '14:10:00');
        $this->assertTrue($result);
    }

    /**
     * CASE 10: Standard campus day — all 5 × 90-min slots (7.5 h) → always exceeded.
     */
    public function test_full_campus_day_always_exceeded(): void
    {
        $existing = [
            $this->slot('08:00:00', '09:30:00'),
            $this->slot('09:40:00', '11:10:00'),
            $this->slot('11:20:00', '12:50:00'),
            $this->slot('13:50:00', '15:20:00'),
        ];

        // Teacher already has 6 h; any positive addition exceeds the cap
        $result = $this->wouldExceed($existing, '15:30:00', '17:00:00');
        $this->assertTrue($result, '4 booked (6 h) + 5th slot (1.5 h) = 7.5 h must be blocked');
    }
}
