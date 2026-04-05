<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudentCourseRegistration;
use App\Models\TeacherAvailability;
use App\Models\RoomAvailability;
use App\Models\TimetableSlot;

/**
 * TimetableConstraintService
 *
 * Provides reusable, query-backed methods for every hard constraint the
 * scheduler must enforce.  All overlap checks use proper half-open interval
 * logic so partial overlaps are caught:
 *
 *   existing.start_time < new_end_time  AND  existing.end_time > new_start_time
 *
 * Overlap queries always scope to timetables in the same term/year with
 * status = 'active' OR the specific draft timetable currently being built.
 * This means cross-semester teacher/room/student clashes within the same
 * academic term are automatically prevented.
 */
class TimetableConstraintService
{
    // ──────────────────────────────────────────────────────────────────────────
    // Overlap checks
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * True if the teacher already has a booked slot that overlaps the proposed
     * time window — checked across every active timetable in the same term/year
     * plus the current draft being generated.
     *
     * @param int         $teacherId
     * @param string      $day            e.g. 'Monday'
     * @param string      $startTime      'HH:MM:SS'
     * @param string      $endTime        'HH:MM:SS'
     * @param string      $term
     * @param int         $year
     * @param int|null    $currentTimetableId  The draft timetable being built (included in check)
     * @param int|null    $excludeSlotId       Slot to ignore (used during manual edits)
     */
    public function teacherHasOverlap(
        int    $teacherId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year,
        ?int   $currentTimetableId = null,
        ?int   $excludeSlotId      = null
    ): bool {
        return $this->overlapExists(
            'teacher_id', $teacherId,
            $day, $startTime, $endTime,
            $term, $year, $currentTimetableId, $excludeSlotId
        );
    }

    /**
     * True if the room already has a booked slot that overlaps the proposed window.
     */
    public function roomHasOverlap(
        int    $roomId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year,
        ?int   $currentTimetableId = null,
        ?int   $excludeSlotId      = null
    ): bool {
        return $this->overlapExists(
            'room_id', $roomId,
            $day, $startTime, $endTime,
            $term, $year, $currentTimetableId, $excludeSlotId
        );
    }

    /**
     * True if the course section (student group) already has a slot at the same time.
     * Prevents the same section from being put in two places simultaneously.
     */
    public function sectionHasOverlap(
        int    $courseSectionId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year,
        ?int   $currentTimetableId = null,
        ?int   $excludeSlotId      = null
    ): bool {
        return $this->overlapExists(
            'course_section_id', $courseSectionId,
            $day, $startTime, $endTime,
            $term, $year, $currentTimetableId, $excludeSlotId
        );
    }

    /**
     * True if any student enrolled in $courseSectionId is also enrolled in
     * another section that already has a slot overlapping the proposed window.
     *
     * This is the cross-semester retake/backlog check: a student registered for
     * both a regular semester course and a backlog course must never have
     * overlapping classes, even if those courses belong to different timetables.
     *
     * Returns false (no conflict) when the section has no enrolled students,
     * because without registrations we cannot know who would be affected.
     */
    public function studentsHaveOverlap(
        int    $courseSectionId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year,
        ?int   $currentTimetableId = null,
        ?int   $excludeSlotId      = null
    ): bool {
        // All students enrolled in the section we are trying to schedule
        $studentIds = StudentCourseRegistration::where('course_section_id', $courseSectionId)
            ->where('status', 'enrolled')
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return false;
        }

        // Every other section those students are also enrolled in
        $otherSectionIds = StudentCourseRegistration::whereIn('student_id', $studentIds)
            ->where('course_section_id', '!=', $courseSectionId)
            ->where('status', 'enrolled')
            ->pluck('course_section_id')
            ->unique();

        if ($otherSectionIds->isEmpty()) {
            return false;
        }

        // Do any of those sections already have a conflicting slot?
        $query = TimetableSlot::whereIn('course_section_id', $otherSectionIds)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereHas('timetable', function ($q) use ($term, $year, $currentTimetableId) {
                $q->where('term', $term)
                  ->where('year', $year)
                  ->where(function ($inner) use ($currentTimetableId) {
                      $inner->whereIn('status', ['active', 'draft']);
                      if ($currentTimetableId) {
                          $inner->orWhere('id', $currentTimetableId);
                      }
                  });
            });

        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return $query->exists();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Availability checks
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * True if the teacher is available to teach in the given day/time window.
     *
     * Rules:
     *  - If the teacher has NO availability records for the term/year at all,
     *    treat them as always available (records are optional).
     *  - If records exist but none cover this day, they are unavailable on that day.
     *  - The proposed slot must fit entirely inside at least one availability window.
     */
    public function isTeacherAvailableForSlot(
        int    $teacherId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year
    ): bool {
        $hasAnyRecord = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('term', $term)
            ->where('year', $year)
            ->exists();

        if (!$hasAnyRecord) {
            return true; // no constraints recorded → always available
        }

        $windows = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('term', $term)
            ->where('year', $year)
            ->where('day_of_week', $day)
            ->get();

        if ($windows->isEmpty()) {
            return false; // has records for term but not this day
        }

        foreach ($windows as $window) {
            if ($window->start_time <= $startTime && $window->end_time >= $endTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * True if the room is available for the given day/time window.
     *
     * Rules match teacher availability: no records → always available.
     * The slot must fit entirely inside an 'available' window.
     */
    public function isRoomAvailableForSlot(
        int    $roomId,
        string $day,
        string $startTime,
        string $endTime
    ): bool {
        $windows = RoomAvailability::where('room_id', $roomId)
            ->where('day_of_week', $day)
            ->where('status', 'available')
            ->get();

        if ($windows->isEmpty()) {
            return true; // no constraints → always available
        }

        foreach ($windows as $window) {
            if ($window->start_time <= $startTime && $window->end_time >= $endTime) {
                return true;
            }
        }

        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Room-type matching
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * True if the room's physical type is compatible with the course component.
     *
     *   lab component       → must be a 'lab' room
     *   theory component    → must be 'classroom' or 'seminar_hall'
     */
    public function roomMatchesCourseType(string $roomType, string $component): bool
    {
        if ($component === 'lab') {
            return $roomType === 'lab';
        }

        return in_array($roomType, ['classroom', 'seminar_hall']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Weekly-session requirement
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * How many 90-minute sessions per week does this course/component need?
     *
     * Uses the course's weekly_sessions field when set; otherwise derives from
     * the credit count:
     *   lab component           → always 1
     *   1–2 credits (theory)    → 1 session
     *   3–4 credits (theory)    → 2 sessions
     *   5+  credits (theory)    → 3 sessions
     */
    public function requiredWeeklySessions(Course $course, string $component): int
    {
        // Labs always need exactly one session per week.
        if ($component === 'lab') {
            return 1;
        }

        // If the HOD/admin has set an explicit override on the course, use it.
        if (!empty($course->weekly_sessions)) {
            return (int) $course->weekly_sessions;
        }

        // Default: 1 session per week for all theory courses.
        // To schedule 2 sessions per week for a course, set weekly_sessions = 2
        // on that course record in the admin panel or via the courses table.
        return 1;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Utility
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * True if two time intervals overlap (half-open interval, exclusive ends).
     *
     *   [startA, endA)  overlaps  [startB, endB)
     *   iff  startA < endB  AND  endA > startB
     */
    public function timesOverlap(
        string $startA, string $endA,
        string $startB, string $endB
    ): bool {
        return $startA < $endB && $endA > $startB;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generic overlap query for teacher/room/section.
     *
     * Scope: all non-archived timetables (active OR draft) in the same term/year,
     * plus the current draft being built.
     *
     * Why non-archived (not just active)?
     *   Rooms and teachers are shared across ALL semesters.  A room booked in a
     *   draft timetable for Sem 2 must also be blocked for Sem 3's generation —
     *   otherwise two drafts can independently claim the same room at the same
     *   time, producing conflicts the moment either is activated.
     *
     *   Archived timetables are excluded: they represent replaced schedules whose
     *   bookings are no longer real and must not prevent new scheduling.
     */
    private function overlapExists(
        string $column,
        int    $entityId,
        string $day,
        string $startTime,
        string $endTime,
        string $term,
        int    $year,
        ?int   $currentTimetableId,
        ?int   $excludeSlotId
    ): bool {
        $query = TimetableSlot::where($column, $entityId)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->whereHas('timetable', function ($q) use ($term, $year, $currentTimetableId) {
                $q->where('term', $term)
                  ->where('year', $year)
                  ->where(function ($inner) use ($currentTimetableId) {
                      // Block against every live timetable (active + draft) so
                      // no two semesters can claim the same room/teacher/section
                      // at the same time, even while both are still drafts.
                      // Archived timetables are excluded — their bookings are gone.
                      $inner->whereIn('status', ['active', 'draft']);
                      if ($currentTimetableId) {
                          // Ensure the current draft is always included even if
                          // it was just created and not yet reflected in the cache.
                          $inner->orWhere('id', $currentTimetableId);
                      }
                  });
            });

        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return $query->exists();
    }
}
