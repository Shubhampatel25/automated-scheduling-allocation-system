<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Conflict;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Room;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TimetableGenerationService
 *
 * Constraint-based timetable generation engine.
 *
 * ── Ordering (hardest items first) ──────────────────────────────────────────
 *   1. Lab components   (constrained to lab rooms only)
 *   2. Large enrolment  (fewer rooms qualify)
 *   3. Teachers with fewer available time windows (scarce resources)
 *
 * ── Hard constraints (never violated) ───────────────────────────────────────
 *   • Teacher may not have two overlapping slots in the same term/year
 *   • Room may not have two overlapping slots in the same term/year
 *   • Section/student-group may not have two overlapping slots
 *   • Students enrolled in retake/backlog sections must not clash across
 *     timetables (cross-semester student overlap)
 *   • Room capacity ≥ enrolled students
 *   • Room type must match component (lab→lab, theory→classroom/seminar_hall)
 *   • Teacher availability windows must be respected
 *   • Room availability windows must be respected
 *   • HOD may only generate for their own department
 *
 * ── Soft constraints (preferred, not enforced) ───────────────────────────────
 *   • Spread sessions across the week (day with fewest slots goes first)
 *   • Multiple sessions of the same course prefer different days
 *   • Theory classes prefer morning/midday slots; labs accept any slot
 *   • Smallest room that still fits the class (balanced room usage)
 *
 * ── Missing-assignment behaviour ─────────────────────────────────────────────
 *   If a course section has no CourseAssignment for a component, the section is
 *   skipped and a 'missing_assignment' conflict is recorded.  Teachers are never
 *   auto-assigned.
 */
class TimetableGenerationService
{
    /** Standard 90-minute teaching periods (with campus break gaps) */
    private array $timeSlots = [
        ['start' => '08:00:00', 'end' => '09:30:00'],
        ['start' => '09:40:00', 'end' => '11:10:00'],
        ['start' => '11:20:00', 'end' => '12:50:00'],
        ['start' => '13:50:00', 'end' => '15:20:00'],
        ['start' => '15:30:00', 'end' => '17:00:00'],
    ];

    /** Soft-constraint: theory prefers earlier periods (indices 0-2 morning-first) */
    private array $theorySlotOrder = [0, 1, 2, 3, 4];

    /** Labs accept any period */
    private array $labSlotOrder = [0, 1, 2, 3, 4];

    private array $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function __construct(
        private TimetableConstraintService $constraints,
        private TimetableConflictService   $conflictService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Public entry point
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate a draft timetable for the given department/term/year/semester.
     *
     * Must be called inside a DB::transaction() by the caller so the whole
     * operation succeeds or rolls back atomically.
     *
     * @param int      $departmentId
     * @param string   $term         'Fall' | 'Winter' | 'Summer'
     * @param int      $year
     * @param int      $semester
     * @param int      $generatedBy  User ID of the HOD triggering generation
     * @param int|null $courseId     Optional: restrict to a single course
     *
     * @return array{
     *   timetable: Timetable,
     *   scheduled: int,
     *   unscheduled: int,
     *   conflicts: int,
     *   messages: string[]
     * }
     */
    public function generate(
        int    $departmentId,
        string $term,
        int    $year,
        int    $semester,
        int    $generatedBy,
        ?int   $courseId = null
    ): array {
        $messages = [];

        // ── 1. Fetch active courses ───────────────────────────────────────────
        $courseQuery = Course::where('department_id', $departmentId)
            ->where('semester', $semester)
            ->where('status', 'active');

        if ($courseId) {
            $courseQuery->where('id', $courseId);
        }

        $courses = $courseQuery->get();

        if ($courses->isEmpty()) {
            return $this->earlyExit(
                "No active courses found for Semester {$semester} in this department. Add courses first."
            );
        }

        // ── 2. Fetch active teachers in the department ────────────────────────
        $teachers = Teacher::where('department_id', $departmentId)
            ->where('status', 'active')
            ->get();

        if ($teachers->isEmpty()) {
            return $this->earlyExit(
                'No active teachers found in this department. Add teachers before generating a timetable.'
            );
        }

        // ── 3. Fetch available rooms ──────────────────────────────────────────
        $rooms = Room::where('status', 'available')
            ->orderBy('capacity', 'asc')   // smallest-fit search: prefer right-sized rooms
            ->get();

        if ($rooms->isEmpty()) {
            return $this->earlyExit(
                'No available rooms found. Set at least one room to status = available.'
            );
        }

        // ── 4. Resolve sections + assignments ────────────────────────────────
        //
        // We use firstOrCreate for sections so existing data is preserved.
        // Teacher assignment is NOT auto-created; if absent we log a conflict.
        //
        $assignmentsToSchedule = $this->resolveAssignments(
            $courses, $term, $year, $messages
        );

        if ($assignmentsToSchedule->isEmpty()) {
            return $this->earlyExit(
                'No schedulable assignments found. Ensure each course section has a teacher assigned.'
            );
        }

        // ── 5. Archive/remove old timetables for this scope ──────────────────
        Timetable::where('department_id', $departmentId)
            ->where('term', $term)
            ->where('year', $year)
            ->where('semester', $semester)
            ->where('status', 'draft')
            ->each(function (Timetable $tt) {
                $tt->timetableSlots()->delete();
                $tt->conflicts()->delete();
                $tt->delete();
            });

        Timetable::where('department_id', $departmentId)
            ->where('term', $term)
            ->where('year', $year)
            ->where('semester', $semester)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // ── 6. Create the new draft timetable record ──────────────────────────
        $timetable = Timetable::create([
            'department_id'   => $departmentId,
            'term'            => $term,
            'year'            => $year,
            'semester'        => $semester,
            'status'          => 'draft',
            'generated_by'    => $generatedBy,
            'generated_at'    => now(),
            'conflicts_count' => 0,
        ]);

        // ── 6b. Purge phantom slots/conflicts with the same timetable_id ───────
        // When AUTO_INCREMENT resets after a full data wipe, a newly created
        // timetable can reuse an old id.  Any orphaned slots/conflicts from the
        // previous timetable with that id would be "adopted" by the new record
        // and corrupt every constraint check.  This hard-delete ensures a clean
        // slate regardless of id reuse history.
        TimetableSlot::where('timetable_id', $timetable->id)
            ->where(function ($q) use ($timetable) {
                // Only delete rows created BEFORE this generation run
                $q->where('created_at', '<', $timetable->generated_at)
                  ->orWhereNull('created_at');
            })
            ->delete();

        Conflict::where('timetable_id', $timetable->id)
            ->where(function ($q) use ($timetable) {
                $q->where('detected_at', '<', $timetable->generated_at)
                  ->orWhereNull('detected_at');
            })
            ->delete();

        // ── 7. Sort assignments: hardest to place first ───────────────────────
        $sorted = $this->prioritise($assignmentsToSchedule, $term, $year);

        // ── 8. Schedule every assignment ──────────────────────────────────────
        $scheduledCount   = 0;
        $unscheduledCount = 0;

        // dayLoad tracks how many slots are on each day so far (global soft constraint)
        $dayLoad = array_fill_keys($this->days, 0);

        // Rule 2: per-semester day load — [semester => [day => count]]
        // Keeps each semester's sessions distributed evenly across the week.
        $semesterDayLoad = [];

        foreach ($sorted as $item) {
            /** @var CourseAssignment $assignment */
            $assignment    = $item['assignment'];
            $requiredCount = $item['sessions'];
            $section       = $item['section'];
            $section->loadMissing('course');
            $course        = $section->course;
            $component     = $assignment->component;
            $teacherId     = $assignment->teacher_id;
            $enrolled      = (int) ($section->enrolled_students ?? 0);
            $semester      = (int) ($course->semester ?? 0); // Rule 2 grouping key

            // Track which days this assignment already occupies so sessions are spread
            $placedOnDays = [];
            $placedCount  = 0;

            for ($sessionIndex = 0; $sessionIndex < $requiredCount; $sessionIndex++) {
                $placed = false;

                // Get candidate (day, slotIndex) pairs sorted by soft constraints.
                // Rule 2 is baked in: days where this semester has fewer slots come first.
                $allCandidates = $this->candidateSlots(
                    $component, $dayLoad, $placedOnDays, $semester, $semesterDayLoad
                );

                // ── Rule 1: professor break — two-pass approach ───────────────
                //
                // Pass 1 (preferred): only slots that do NOT create a back-to-back
                //   situation for this teacher on this day.
                // Pass 2 (fallback):  back-to-back slots, tried only when pass 1
                //   finds no feasible placement (all hard constraints failed).
                //
                // This keeps the rule as a strong soft constraint: always respected
                // when possible, gracefully relaxed when no other slot is available.
                $preferredCandidates  = [];
                $backToBackCandidates = [];

                foreach ($allCandidates as $candidate) {
                    [$cDay, $cSlotIdx] = $candidate;
                    $cSlot = $this->timeSlots[$cSlotIdx];

                    if ($this->constraints->teacherWouldCreateBackToBack(
                        $teacherId, $cDay, $cSlot['start'], $cSlot['end'], $term, $year, $timetable->id
                    )) {
                        $backToBackCandidates[] = $candidate;
                    } else {
                        $preferredCandidates[] = $candidate;
                    }
                }

                // Preferred first; only try back-to-back slots if preferred set
                // is entirely blocked by hard constraints.
                $candidateSets = array_filter([$preferredCandidates, $backToBackCandidates]);

                foreach ($candidateSets as $candidates) {
                    foreach ($candidates as [$day, $slotIdx]) {
                        $slot      = $this->timeSlots[$slotIdx];
                        $startTime = $slot['start'];
                        $endTime   = $slot['end'];

                        // ── Hard constraint: teacher availability ──────────────
                        if (!$this->constraints->isTeacherAvailableForSlot(
                            $teacherId, $day, $startTime, $endTime, $term, $year
                        )) {
                            continue;
                        }

                        // ── Hard constraint: teacher overlap (DB + draft) ──────
                        if ($this->constraints->teacherHasOverlap(
                            $teacherId, $day, $startTime, $endTime, $term, $year, $timetable->id
                        )) {
                            continue;
                        }

                        // ── Hard constraint: teacher 6-hour daily limit ────────
                        if ($this->constraints->teacherExceedsDailyHours(
                            $teacherId, $day, $startTime, $endTime, $term, $year, $timetable->id
                        )) {
                            continue;
                        }

                        // ── Hard constraint: section overlap ───────────────────
                        if ($this->constraints->sectionHasOverlap(
                            $section->id, $day, $startTime, $endTime, $term, $year, $timetable->id
                        )) {
                            continue;
                        }

                        // ── Hard constraint: student cross-section overlap ──────
                        if ($this->constraints->studentsHaveOverlap(
                            $section->id, $day, $startTime, $endTime, $term, $year, $timetable->id
                        )) {
                            continue;
                        }

                        // ── Find the best room for this slot ───────────────────
                        $room = $this->findBestRoom(
                            $enrolled, $component, $day, $startTime, $endTime,
                            $term, $year, $timetable->id, $rooms
                        );

                        if ($room === null) {
                            continue; // no valid room at this time — try next slot
                        }

                        // ── All constraints satisfied — save slot ──────────────
                        TimetableSlot::create([
                            'timetable_id'      => $timetable->id,
                            'course_section_id' => $section->id,
                            'teacher_id'        => $teacherId,
                            'room_id'           => $room->id,
                            'day_of_week'       => $day,
                            'start_time'        => $startTime,
                            'end_time'          => $endTime,
                            'component'         => $component,
                            'created_at'        => now(),
                        ]);

                        // Update global day load (all courses)
                        $dayLoad[$day]++;
                        // Update per-semester day load (Rule 2)
                        $semesterDayLoad[$semester][$day] = ($semesterDayLoad[$semester][$day] ?? 0) + 1;

                        $placedOnDays[] = $day;
                        $placedCount++;
                        $placed = true;
                        break 2; // exit both the candidates loop and the candidateSets loop
                    }
                }

                if (!$placed) {
                    // Record why this session could not be scheduled
                    $label = "{$course->name} (Section {$section->section_number}, {$component}, session " . ($sessionIndex + 1) . ")";
                    $reason = $this->diagnoseFailure(
                        $assignment, $term, $year, $timetable->id, $rooms
                    );
                    $this->conflictService->recordUnschedulable(
                        $timetable->id,
                        'no_feasible_slot',
                        "Could not schedule {$label}: {$reason}",
                        $section->id
                    );
                    $unscheduledCount++;
                }
            }

            if ($placedCount > 0) {
                $scheduledCount += $placedCount;
            }
        }

        // ── 9. Post-generation conflict scans ────────────────────────────────
        //      a) Within-timetable: teacher/room/section double-bookings
        $this->conflictService->detectAndRecordConflicts($timetable);

        //      b) Cross-timetable: rooms/teachers shared with other active
        //         semester timetables in the same term/year
        $this->conflictService->detectCrossTimetableConflicts($timetable);

        // Total conflicts = unscheduled records + post-scan findings
        $existingConflicts = $timetable->conflicts()->count();
        $timetable->update(['conflicts_count' => $existingConflicts]);

        // ── 10. Audit log ─────────────────────────────────────────────────────
        ActivityLog::create([
            'user_id'     => $generatedBy,
            'action'      => 'generate_timetable',
            'entity_type' => 'timetable',
            'entity_id'   => $timetable->id,
            'details'     => "Generated timetable for dept={$departmentId} sem={$semester} {$term}/{$year}. " .
                             "Scheduled={$scheduledCount} Unscheduled={$unscheduledCount} Conflicts={$existingConflicts}",
            'created_at'  => now(),
        ]);

        return [
            'timetable'   => $timetable,
            'scheduled'   => $scheduledCount,
            'unscheduled' => $unscheduledCount,
            'conflicts'   => $existingConflicts,
            'messages'    => $messages,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Assignment resolution
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the flat list of assignments to schedule.
     *
     * For each course:
     *   - firstOrCreate a section (section_number = 1, backward compatible)
     *   - Determine required components from course.type
     *   - Look for an existing CourseAssignment per component
     *   - If found: add to schedule list
     *   - If missing: queue a 'missing_assignment' conflict (logged later)
     *
     * Returns a Collection of:
     *   ['assignment' => CourseAssignment, 'sessions' => int, 'conflict_reason' => null]
     * plus a separate list of missing-assignment items.
     */
    private function resolveAssignments(
        Collection $courses,
        string     $term,
        int        $year,
        array      &$messages
    ): Collection {
        $schedulable = collect();
        $missingLog  = [];

        foreach ($courses as $course) {
            $section = CourseSection::firstOrCreate(
                [
                    'course_id'      => $course->id,
                    'term'           => $term,
                    'year'           => $year,
                    'section_number' => 1,
                ],
                [
                    'max_students'      => 60,
                    'enrolled_students' => 0,
                ]
            );

            $components = match ($course->type) {
                'lab'     => ['lab'],
                'hybrid'  => ['theory', 'lab'],
                default   => ['theory'],  // theory, lecture, etc.
            };

            foreach ($components as $component) {
                // Look for an assignment on the exact term/year section first.
                $assignment = CourseAssignment::where('course_section_id', $section->id)
                    ->where('component', $component)
                    ->first();

                // Fallback: find an assignment on ANY section of this course
                // (handles the common case where the HOD assigned a teacher before
                // generating the timetable, so the assignment lives on a section
                // that was created without a matching term/year).
                if (!$assignment) {
                    $assignment = CourseAssignment::whereHas('courseSection', fn($q) =>
                        $q->where('course_id', $course->id)
                    )
                    ->where('component', $component)
                    ->first();
                }

                if (!$assignment) {
                    $missingLog[] = [
                        'section_id'  => $section->id,
                        'course_name' => $course->name,
                        'component'   => $component,
                    ];
                    continue;
                }

                $assignment->loadMissing(['courseSection.course', 'teacher']);

                $sessions = $this->constraints->requiredWeeklySessions($course, $component);

                // Always push the term/year-specific $section so timetable slots
                // are linked to the correct scope, regardless of which section
                // the assignment record originally belongs to.
                $schedulable->push([
                    'assignment' => $assignment,
                    'section'    => $section,
                    'sessions'   => $sessions,
                ]);
            }
        }

        if (!empty($missingLog)) {
            $names = array_map(
                fn($m) => "{$m['course_name']} ({$m['component']})",
                $missingLog
            );
            $messages[] = 'Missing teacher assignments for: ' . implode(', ', $names) .
                          '. Assign teachers first — those sections were skipped.';
        }

        return $schedulable;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Priority ordering
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Sort assignments so the hardest-to-place items are attempted first.
     *
     * Priority (descending importance):
     *   1. Lab components first (only lab rooms qualify)
     *   2. Larger enrolment (fewer rooms have enough capacity)
     *   3. Teacher with fewer total availability windows (scarce availability)
     */
    private function prioritise(Collection $items, string $term, int $year): Collection
    {
        // Pre-compute availability window counts per teacher
        $avCounts = \App\Models\TeacherAvailability::where('term', $term)
            ->where('year', $year)
            ->selectRaw('teacher_id, COUNT(*) as cnt')
            ->groupBy('teacher_id')
            ->pluck('cnt', 'teacher_id');

        return $items->sortBy([
            // Labs first
            fn($a, $b) => ($a['assignment']->component === 'lab' ? 0 : 1)
                          <=> ($b['assignment']->component === 'lab' ? 0 : 1),
            // Largest enrolment first
            fn($a, $b) => ($b['section']->enrolled_students ?? 0)
                          <=> ($a['section']->enrolled_students ?? 0),
            // Teacher with fewest windows first (most constrained)
            fn($a, $b) => ($avCounts[$a['assignment']->teacher_id] ?? PHP_INT_MAX)
                          <=> ($avCounts[$b['assignment']->teacher_id] ?? PHP_INT_MAX),
        ])->values();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Candidate slot generation (soft constraints)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return an ordered list of [day, slotIndex] pairs to try for one session.
     *
     * Soft constraints applied here (priority order):
     *   1. Days not yet used by this assignment's earlier sessions go first
     *      (multi-session courses spread across the week)
     *   2. Rule 2 — Semester/department balance: days where this semester
     *      already has fewer sessions go before days already loaded for that
     *      semester (even distribution per-semester, not just overall)
     *   3. Days with fewer overall slots go before heavier days (global spread)
     *   4. Theory prefers morning-to-afternoon slot order; labs accept any order
     *
     * @param string $component       'theory' | 'lab'
     * @param array  $dayLoad         [day => total-slot-count] across all courses
     * @param array  $placedOnDays    Days already used for previous sessions of THIS assignment
     * @param int    $semester        Semester number (Rule 2 key)
     * @param array  $semesterDayLoad [semester => [day => count]] tracked per-semester
     */
    private function candidateSlots(
        string $component,
        array  $dayLoad,
        array  $placedOnDays,
        int    $semester        = 0,
        array  $semesterDayLoad = []
    ): array {
        $sortedDays = $this->days;

        usort($sortedDays, function (string $a, string $b) use (
            $dayLoad, $placedOnDays, $semester, $semesterDayLoad
        ) {
            // Priority 1: unused-by-this-assignment days first
            $aUsed = in_array($a, $placedOnDays) ? 1 : 0;
            $bUsed = in_array($b, $placedOnDays) ? 1 : 0;
            if ($aUsed !== $bUsed) {
                return $aUsed <=> $bUsed;
            }

            // Priority 2 (Rule 2): day lighter for this semester goes first
            $semA = $semesterDayLoad[$semester][$a] ?? 0;
            $semB = $semesterDayLoad[$semester][$b] ?? 0;
            if ($semA !== $semB) {
                return $semA <=> $semB;
            }

            // Priority 3: day lighter overall goes first
            return ($dayLoad[$a] ?? 0) <=> ($dayLoad[$b] ?? 0);
        });

        $slotOrder = $component === 'lab' ? $this->labSlotOrder : $this->theorySlotOrder;

        $candidates = [];
        foreach ($sortedDays as $day) {
            foreach ($slotOrder as $slotIdx) {
                $candidates[] = [$day, $slotIdx];
            }
        }

        return $candidates;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Room selection
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Find the best room for a given component/day/time.
     *
     * "Best" = smallest room whose capacity ≥ enrolled students (balanced usage).
     * Rooms collection is pre-sorted by capacity ASC so we stop at the first match.
     *
     * Hard constraints checked per room:
     *   - capacity ≥ enrolled
     *   - room type matches component
     *   - room availability window covers the slot
     *   - room has no overlapping booking
     */
    private function findBestRoom(
        int        $enrolled,
        string     $component,
        string     $day,
        string     $startTime,
        string     $endTime,
        string     $term,
        int        $year,
        int        $timetableId,
        Collection $rooms
    ): ?Room {
        foreach ($rooms as $room) {
            if ($room->capacity < $enrolled) {
                continue; // too small
            }

            if (!$this->constraints->roomMatchesCourseType($room->type, $component)) {
                continue; // wrong type
            }

            if (!$this->constraints->isRoomAvailableForSlot($room->id, $day, $startTime, $endTime)) {
                continue; // outside availability window
            }

            if ($this->constraints->roomHasOverlap(
                $room->id, $day, $startTime, $endTime, $term, $year, $timetableId
            )) {
                continue; // already booked
            }

            return $room; // first valid smallest-fit room
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Failure diagnosis
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Produce a human-readable reason why no slot could be found for an assignment.
     * Used as the description in 'no_feasible_slot' conflict records.
     */
    private function diagnoseFailure(
        CourseAssignment $assignment,
        string           $term,
        int              $year,
        int              $timetableId,
        Collection       $rooms
    ): string {
        $section    = $assignment->courseSection;
        $component  = $assignment->component;
        $teacherId  = $assignment->teacher_id;
        $enrolled   = (int) ($section->enrolled_students ?? 0);

        // Check teacher availability
        $teacherHasAnyWindow = \App\Models\TeacherAvailability::where('teacher_id', $teacherId)
            ->where('term', $term)->where('year', $year)->exists();
        if (!$teacherHasAnyWindow) {
            // might still be available (no records = always available), but check
            // if teacher is blocked on ALL slots by overlap
        }

        // Check room adequacy
        $suitableRooms = $rooms->filter(fn(Room $r) =>
            $r->capacity >= $enrolled &&
            $this->constraints->roomMatchesCourseType($r->type, $component)
        );

        if ($suitableRooms->isEmpty()) {
            return "No {$component} room with capacity ≥ {$enrolled} exists.";
        }

        // Check if teacher is fully blocked (overlap, availability, or daily hour cap)
        $teacherFullyBlocked  = true;
        $teacherHitDailyLimit = false;
        foreach ($this->days as $d) {
            foreach ($this->timeSlots as $s) {
                if (!$this->constraints->isTeacherAvailableForSlot($teacherId, $d, $s['start'], $s['end'], $term, $year)) {
                    continue;
                }
                if ($this->constraints->teacherHasOverlap($teacherId, $d, $s['start'], $s['end'], $term, $year, $timetableId)) {
                    continue;
                }
                if ($this->constraints->teacherExceedsDailyHours($teacherId, $d, $s['start'], $s['end'], $term, $year, $timetableId)) {
                    $teacherHitDailyLimit = true;
                    continue;
                }
                $teacherFullyBlocked = false;
                break 2;
            }
        }

        if ($teacherFullyBlocked) {
            $reason = 'Teacher is fully booked or unavailable for all remaining time slots.';
            if ($teacherHitDailyLimit) {
                $reason .= ' (6-hour daily teaching limit reached on one or more days)';
            }
            return $reason;
        }

        return 'All valid (day, time, room) combinations were blocked by teacher, room, or section constraints.';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Uniform early-exit structure used before the timetable record is created */
    private function earlyExit(string $message): array
    {
        return [
            'timetable'   => null,
            'scheduled'   => 0,
            'unscheduled' => 0,
            'conflicts'   => 0,
            'messages'    => [$message],
        ];
    }
}
