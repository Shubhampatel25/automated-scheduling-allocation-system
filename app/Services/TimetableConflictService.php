<?php

namespace App\Services;

use App\Models\Conflict;
use App\Models\Timetable;
use App\Models\TimetableSlot;

/**
 * TimetableConflictService
 *
 * Centralises all conflict creation and post-generation scanning so the
 * generation engine and the controller do not need to know the Conflict
 * table's internals.
 *
 * Conflict types stored in the database:
 *
 *   Scheduling failures (no slot was created):
 *     missing_assignment           – section has no CourseAssignment
 *     missing_teacher_availability – teacher has no availability for term
 *     no_suitable_room             – no room matches type + capacity + availability
 *     no_feasible_slot             – every (day, slot) was blocked by hard constraints
 *
 *   Post-generation scan findings (two slots collide):
 *     teacher_conflict             – teacher booked in two places at once
 *     room_conflict                – room booked in two places at once
 *     section_overlap              – same section has two slots at the same time
 */
class TimetableConflictService
{
    /**
     * Record a conflict for an assignment that could not be scheduled.
     * slot_id_1 and slot_id_2 are NULL because no slot was created.
     *
     * @param int         $timetableId
     * @param string      $conflictType  One of the scheduling-failure types above
     * @param string      $description   Human-readable explanation for the HOD
     * @param int|null    $courseSectionId  Section that could not be placed
     */
    public function recordUnschedulable(
        int    $timetableId,
        string $conflictType,
        string $description,
        ?int   $courseSectionId = null
    ): void {
        Conflict::create([
            'timetable_id'      => $timetableId,
            'conflict_type'     => $conflictType,
            'description'       => $description,
            'slot_id_1'         => null,
            'slot_id_2'         => null,
            'course_section_id' => $courseSectionId,
            'status'            => 'unresolved',
            'detected_at'       => now(),
        ]);
    }

    /**
     * Record a conflict between two existing timetable slots.
     * Used both during generation (immediate detection) and post-generation scan.
     *
     * @param int         $timetableId
     * @param string      $conflictType
     * @param string      $description
     * @param int         $slotId1
     * @param int|null    $slotId2        Second slot involved (null for capacity issues)
     * @param int|null    $courseSectionId
     */
    public function recordSlotConflict(
        int    $timetableId,
        string $conflictType,
        string $description,
        int    $slotId1,
        ?int   $slotId2         = null,
        ?int   $courseSectionId = null
    ): void {
        Conflict::create([
            'timetable_id'      => $timetableId,
            'conflict_type'     => $conflictType,
            'description'       => $description,
            'slot_id_1'         => $slotId1,
            'slot_id_2'         => $slotId2,
            'course_section_id' => $courseSectionId,
            'status'            => 'unresolved',
            'detected_at'       => now(),
        ]);
    }

    /**
     * Cross-timetable conflict scan.
     *
     * Compares every slot in $timetable against every slot in other ACTIVE
     * timetables for the same term/year (any semester, any department).
     *
     * This catches room and teacher double-bookings that span semester boundaries —
     * e.g. Room 101 used by Sem 1 and Sem 2 at the same day/time.
     *
     * Called:
     *   - After generation (draft vs. other active timetables)
     *   - After activation (newly active timetable vs. already-active ones)
     *
     * Returns the number of new conflict records created.
     */
    public function detectCrossTimetableConflicts(Timetable $timetable): int
    {
        $ownSlots = TimetableSlot::where('timetable_id', $timetable->id)
            ->with(['teacher', 'room', 'courseSection'])
            ->get();

        if ($ownSlots->isEmpty()) {
            return 0;
        }

        // Slots from all OTHER non-archived timetables in the same term/year
        $otherSlots = TimetableSlot::whereHas('timetable', function ($q) use ($timetable) {
                $q->where('term', $timetable->term)
                  ->where('year', $timetable->year)
                  ->whereIn('status', ['active', 'draft'])
                  ->where('id', '!=', $timetable->id);
            })
            ->with(['teacher', 'room', 'timetable'])
            ->get();

        if ($otherSlots->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($ownSlots as $slotA) {
            foreach ($otherSlots as $slotB) {
                if ($slotA->day_of_week !== $slotB->day_of_week) {
                    continue;
                }

                // Half-open interval overlap
                if (!($slotA->start_time < $slotB->end_time && $slotA->end_time > $slotB->start_time)) {
                    continue;
                }

                $day    = $slotA->day_of_week;
                $rangeA = substr($slotA->start_time, 0, 5) . '–' . substr($slotA->end_time, 0, 5);
                $rangeB = substr($slotB->start_time, 0, 5) . '–' . substr($slotB->end_time, 0, 5);
                $semB   = $slotB->timetable->semester ?? '?';

                // Teacher double-booking across semesters
                if ($slotA->teacher_id && $slotA->teacher_id === $slotB->teacher_id) {
                    $name = $slotA->teacher->name ?? "ID {$slotA->teacher_id}";
                    $alreadyRecorded = Conflict::where('timetable_id', $timetable->id)
                        ->where('conflict_type', 'teacher_conflict')
                        ->where('slot_id_1', $slotA->id)
                        ->where('slot_id_2', $slotB->id)
                        ->exists();
                    if (!$alreadyRecorded) {
                        $this->recordSlotConflict(
                            $timetable->id,
                            'teacher_conflict',
                            "Teacher {$name} is double-booked on {$day} at {$rangeA} (this timetable) and {$rangeB} (Semester {$semB} timetable)",
                            $slotA->id,
                            $slotB->id,
                            $slotA->course_section_id
                        );
                        $count++;
                    }
                }

                // Room double-booking across semesters
                if ($slotA->room_id && $slotA->room_id === $slotB->room_id) {
                    $roomNo = $slotA->room->room_number ?? "ID {$slotA->room_id}";
                    $alreadyRecorded = Conflict::where('timetable_id', $timetable->id)
                        ->where('conflict_type', 'room_conflict')
                        ->where('slot_id_1', $slotA->id)
                        ->where('slot_id_2', $slotB->id)
                        ->exists();
                    if (!$alreadyRecorded) {
                        $this->recordSlotConflict(
                            $timetable->id,
                            'room_conflict',
                            "Room {$roomNo} is double-booked on {$day} at {$rangeA} (this timetable) and {$rangeB} (Semester {$semB} timetable)",
                            $slotA->id,
                            $slotB->id,
                            $slotA->course_section_id
                        );
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Post-generation conflict scan.
     *
     * Compares every pair of slots within the timetable using proper half-open
     * interval overlap logic (not just exact start_time matching).  Detects:
     *   - Teacher double-booking
     *   - Room double-booking
     *   - Section scheduled twice at the same time
     *
     * Returns the number of new conflict records created.
     *
     * NOTE: The generation engine already prevents these via constraint checks,
     * so this scan is a safety net that should normally return 0.  It becomes
     * useful after manual edits that bypass the engine.
     */
    public function detectAndRecordConflicts(Timetable $timetable): int
    {
        $slots = TimetableSlot::where('timetable_id', $timetable->id)
            ->with(['courseSection.course', 'teacher', 'room'])
            ->get();

        $count = 0;

        // O(n²) pair comparison — acceptable for typical timetable sizes (< 200 slots)
        foreach ($slots as $i => $slotA) {
            foreach ($slots as $j => $slotB) {
                if ($j <= $i) {
                    continue; // avoid self-comparison and duplicate pairs
                }

                if ($slotA->day_of_week !== $slotB->day_of_week) {
                    continue; // different days can never overlap
                }

                // Half-open interval overlap
                if (!($slotA->start_time < $slotB->end_time && $slotA->end_time > $slotB->start_time)) {
                    continue;
                }

                $dayLabel   = $slotA->day_of_week;
                $rangeA     = "{$slotA->start_time}–{$slotA->end_time}";
                $rangeB     = "{$slotB->start_time}–{$slotB->end_time}";

                // ── Teacher double-booking ────────────────────────────────────
                if ($slotA->teacher_id === $slotB->teacher_id) {
                    $name = $slotA->teacher->name ?? "ID {$slotA->teacher_id}";
                    $this->recordSlotConflict(
                        $timetable->id,
                        'teacher_conflict',
                        "Teacher {$name} has overlapping classes on {$dayLabel} ({$rangeA} and {$rangeB})",
                        $slotA->id,
                        $slotB->id,
                        $slotA->course_section_id
                    );
                    $count++;
                }

                // ── Room double-booking ───────────────────────────────────────
                if ($slotA->room_id === $slotB->room_id) {
                    $roomNo = $slotA->room->room_number ?? "ID {$slotA->room_id}";
                    $this->recordSlotConflict(
                        $timetable->id,
                        'room_conflict',
                        "Room {$roomNo} is double-booked on {$dayLabel} ({$rangeA} and {$rangeB})",
                        $slotA->id,
                        $slotB->id,
                        $slotA->course_section_id
                    );
                    $count++;
                }

                // ── Section scheduled twice simultaneously ────────────────────
                if ($slotA->course_section_id === $slotB->course_section_id) {
                    $courseName = $slotA->courseSection->course->name
                                  ?? "Section ID {$slotA->course_section_id}";
                    $secNo = $slotA->courseSection->section_number ?? '';
                    $this->recordSlotConflict(
                        $timetable->id,
                        'section_overlap',
                        "Section {$secNo} of {$courseName} is scheduled twice on {$dayLabel} ({$rangeA} and {$rangeB})",
                        $slotA->id,
                        $slotB->id,
                        $slotA->course_section_id
                    );
                    $count++;
                }
            }
        }

        return $count;
    }
}
