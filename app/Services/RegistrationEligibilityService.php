<?php

namespace App\Services;

use App\Models\CourseSection;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\StudentCourseRegistration;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

/**
 * Encapsulates all course-registration eligibility logic.
 *
 * Design principles:
 *  - Pure-ish functions: no DB writes, only reads.
 *  - Single responsibility: eligibility classification only.
 *  - Controller stays thin: it owns DB queries for WHICH sections exist;
 *    this service decides WHETHER a student can enroll in each one.
 */
class RegistrationEligibilityService
{
    // ─── Academic Record Helpers ──────────────────────────────────────────────

    /**
     * All course CODES the student has passed (needed for prerequisite checks).
     */
    public function getPassedCourseCodes(int $studentId): array
    {
        return StudentCourseRegistration::where('student_id', $studentId)
            ->where('status', 'completed')
            ->where('result', 'pass')
            ->with('courseSection.course')
            ->get()
            ->map(fn($r) => optional($r->courseSection?->course)->code)
            ->filter()->unique()->values()->toArray();
    }

    /**
     * All course IDs the student has passed at least once.
     */
    public function getPassedCourseIds(int $studentId): array
    {
        return StudentCourseRegistration::where('student_id', $studentId)
            ->where('status', 'completed')
            ->where('result', 'pass')
            ->with('courseSection.course')
            ->get()
            ->map(fn($r) => $r->courseSection?->course?->id)
            ->filter()->unique()->values()->toArray();
    }

    /**
     * All course IDs the student has ever failed (includes cleared backlogs).
     */
    public function getFailedCourseIds(int $studentId): array
    {
        return StudentCourseRegistration::where('student_id', $studentId)
            ->where('status', 'completed')
            ->where('result', 'fail')
            ->with('courseSection.course')
            ->get()
            ->map(fn($r) => $r->courseSection?->course?->id)
            ->filter()->unique()->values()->toArray();
    }

    /**
     * Net-failed course IDs: failed AND not yet cleared by a later retake pass.
     * These drive the Retake / Backlog section.
     */
    public function getNetFailedCourseIds(int $studentId): array
    {
        return array_values(
            array_diff($this->getFailedCourseIds($studentId), $this->getPassedCourseIds($studentId))
        );
    }

    /**
     * Course IDs where the supplemental (retake) fee has been fully paid.
     */
    public function getSupplementalPaidCourseIds(int $studentId): array
    {
        return FeePayment::where('student_id', $studentId)
            ->where('type', 'supplemental')
            ->where('status', 'paid')
            ->pluck('course_id')
            ->toArray();
    }

    /**
     * Build a collection of all timetable slots for currently-enrolled sections.
     * Used for schedule-conflict detection.
     */
    public function getEnrolledSlots(Collection $enrolledSectionIds): Collection
    {
        if ($enrolledSectionIds->isEmpty()) {
            return collect();
        }
        return TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)->get();
    }

    /**
     * Build a keyed map: section_id → CourseSection (with course loaded).
     * Used to look up the name of the conflicting course for human-readable messages.
     */
    public function getEnrolledSectionsMap(Collection $enrolledSectionIds): Collection
    {
        if ($enrolledSectionIds->isEmpty()) {
            return collect();
        }
        return CourseSection::whereIn('id', $enrolledSectionIds)
            ->with('course')
            ->get()
            ->keyBy('id');
    }

    // ─── Section Classification ───────────────────────────────────────────────

    /**
     * Classify a flat collection of candidate regular sections into:
     *   - regularSections  : unblocked — student can enroll
     *   - blockedSections  : hard-blocked with a human-readable reason
     *   - sectionStatuses  : per-section ['blocked', 'advisory', 'reason', 'reason_type'] map
     *
     * $context keys:
     *   passed_codes          : string[]   — passed course codes (for prereq checks)
     *   enrolled_slots        : Collection — active timetable slots for enrolled sections
     *   enrolled_sections_map : Collection — section_id → CourseSection with course
     *
     * @return array{regularSections: Collection, blockedSections: Collection, sectionStatuses: array}
     */
    public function classifyRegularSections(Collection $sections, array $context): array
    {
        $passedCodes        = $context['passed_codes'] ?? [];
        $enrolledSlots      = $context['enrolled_slots'] ?? collect();
        $enrolledSectionsMap = $context['enrolled_sections_map'] ?? collect();

        $regular  = collect();
        $blocked  = collect();
        $statuses = [];

        foreach ($sections as $section) {
            $status = $this->checkSectionStatus(
                $section,
                $section->course,
                $passedCodes,
                $enrolledSlots,
                $enrolledSectionsMap
            );
            $statuses[$section->id] = $status;

            if ($status['blocked']) {
                $blocked->push($section);
            } else {
                $regular->push($section);
            }
        }

        return [
            'regularSections' => $regular,
            'blockedSections' => $blocked,
            'sectionStatuses' => $statuses,
        ];
    }

    /**
     * Classify retake sections, adding fee and conflict info per section.
     *
     * $context keys:
     *   supplemental_paid_ids : int[]      — course IDs with paid supplemental fee
     *   enrolled_slots        : Collection
     *   enrolled_sections_map : Collection
     *
     * @return array{retakeStatuses: array<int, array>}
     */
    public function classifyRetakeSections(Collection $sections, array $context): array
    {
        $suppPaidIds         = $context['supplemental_paid_ids'] ?? [];
        $enrolledSlots       = $context['enrolled_slots'] ?? collect();
        $enrolledSectionsMap = $context['enrolled_sections_map'] ?? collect();

        $statuses = [];

        foreach ($sections as $section) {
            $course       = $section->course;
            $suppPaid     = in_array($course->id, $suppPaidIds);
            $conflict     = $this->detectScheduleConflict($section, $enrolledSlots, $enrolledSectionsMap);

            // A retake is hard-blocked if the supplemental fee is not paid OR there is a conflict.
            if (!$suppPaid) {
                $blockReason     = 'retake_fee_not_paid';
                $blockReasonText = 'Retake fee not paid — visit Fee Payment page first';
            } elseif ($conflict) {
                $blockReason     = 'timetable_conflict';
                $blockReasonText = $conflict;
            } else {
                $blockReason     = null;
                $blockReasonText = null;
            }

            $statuses[$section->id] = [
                'supp_paid'        => $suppPaid,
                'conflict'         => $conflict,
                'blocked'          => $blockReason !== null,
                'block_reason_key' => $blockReason,
                'block_reason'     => $blockReasonText,
            ];
        }

        return ['retakeStatuses' => $statuses];
    }

    // ─── Single-section Eligibility Check ────────────────────────────────────

    /**
     * Full eligibility check for one section (mirrors the 10-point validation
     * in StudentCourseRegistrationController, read-only version).
     *
     * Returns:
     *   eligible   : bool
     *   type       : 'regular' | 'retake' | 'blocked'
     *   reason     : string|null  — human-readable block reason
     *   reason_key : string|null  — machine key for i18n / tests
     *
     * $context keys (same as classifyRegularSections, plus):
     *   student           : Student
     *   is_retake         : bool
     *   regular_fee_paid  : bool
     *   supp_paid_ids     : int[]
     *   enrolled_ids      : int[]  — currently enrolled section IDs
     */
    public function checkFullEligibility(CourseSection $section, array $context): array
    {
        $student         = $context['student'];
        $isRetake        = $context['is_retake'] ?? false;
        $regularFeePaid  = $context['regular_fee_paid'] ?? false;
        $suppPaidIds     = $context['supp_paid_ids'] ?? [];
        $passedCodes     = $context['passed_codes'] ?? [];
        $enrolledSlots   = $context['enrolled_slots'] ?? collect();
        $enrolledSectionsMap = $context['enrolled_sections_map'] ?? collect();

        $course = $section->course;

        // 1. Department match
        if ($course->department_id !== $student->department_id) {
            return $this->blocked('department_mismatch', 'Course is not in your department');
        }

        // 2. Already passed
        $passedCourseIds = $this->getPassedCourseIds($student->id);
        if (in_array($course->id, $passedCourseIds)) {
            return $this->blocked('already_passed', 'You have already passed this course');
        }

        // 3. Already enrolled in another section
        $enrolledIds = $context['enrolled_ids'] ?? [];
        $alreadyEnrolled = StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->whereHas('courseSection', fn($q) => $q->where('course_id', $course->id))
            ->exists();
        if ($alreadyEnrolled) {
            return $this->blocked('already_registered', 'Already enrolled in a section of this course');
        }

        // 4–5. Fee check
        if ($isRetake) {
            if (!in_array($course->id, $suppPaidIds)) {
                return $this->blocked('retake_fee_not_paid', 'Supplemental retake fee required for "' . $course->name . '"');
            }
        } else {
            if (!$regularFeePaid) {
                return $this->blocked('fee_not_paid', 'Semester ' . $student->semester . ' fee must be paid before registration');
            }
            // 6. Semester match (regular only)
            if ($course->semester !== null && $course->semester !== $student->semester) {
                return $this->blocked('semester_mismatch', 'Course belongs to Semester ' . $course->semester . ', not your current Semester ' . $student->semester);
            }
        }

        // 7. Prerequisite (hard block for mandatory; advisory for non-mandatory)
        if (!$isRetake && $course->prerequisite_course_code) {
            if (!in_array($course->prerequisite_course_code, $passedCodes)) {
                if ($course->prerequisite_mandatory) {
                    return $this->blocked('prerequisite_fail', 'Must pass "' . $course->prerequisite_course_code . '" before enrolling');
                }
                // Advisory — eligible but with a warning
                return [
                    'eligible'   => true,
                    'type'       => 'regular',
                    'reason'     => null,
                    'reason_key' => null,
                    'advisory'   => 'Advisory prerequisite "' . $course->prerequisite_course_code . '" not yet passed',
                ];
            }
        }

        // 8. Section capacity
        if ($section->enrolled_students >= $section->max_students) {
            return $this->blocked('section_full', 'Section ' . $section->section_number . ' is full');
        }

        // 9. Timetable conflict
        $conflict = $this->detectScheduleConflict($section, $enrolledSlots, $enrolledSectionsMap);
        if ($conflict) {
            return $this->blocked('timetable_conflict', $conflict);
        }

        return [
            'eligible'   => true,
            'type'       => $isRetake ? 'retake' : 'regular',
            'reason'     => null,
            'reason_key' => null,
            'advisory'   => null,
        ];
    }

    // ─── Result History Annotation ────────────────────────────────────────────

    /**
     * Annotate a flat collection of completed StudentCourseRegistration records with:
     *   isRetakePass     : true if this is a pass record and the course was also previously failed
     *   isBacklogCleared : true if this is a fail record and the course was later passed
     *
     * This gives the view everything it needs to render:
     *   Pass | Retake Pass | Fail | Retake Fail (active backlog) | Backlog Cleared
     */
    public function annotateCompletedResults(Collection $completedRegs): Collection
    {
        // Group by course ID to identify retake patterns
        $byCourse                = $completedRegs->groupBy(fn($r) => $r->courseSection?->course?->id ?? 0);
        $backlogClearedCourseIds = [];
        $latestRetakePassIds     = [];

        foreach ($byCourse as $courseId => $regs) {
            if (!$courseId) {
                continue;
            }
            $fails  = $regs->where('result', 'fail');
            $passes = $regs->where('result', 'pass')->sortByDesc(fn($r) => $r->registered_at ?? '');

            if ($fails->isNotEmpty() && $passes->isNotEmpty()) {
                $backlogClearedCourseIds[] = $courseId;
                $latestRetakePassIds[]     = $passes->first()->id;
            }
        }

        return $completedRegs->map(function ($reg) use ($backlogClearedCourseIds, $latestRetakePassIds) {
            $courseId              = $reg->courseSection?->course?->id;
            $reg->isBacklogCleared = $reg->result === 'fail' && in_array($courseId, $backlogClearedCourseIds);
            $reg->isRetakePass     = in_array($reg->id, $latestRetakePassIds);
            // isRetakeFail: failed a course that has also a fail record — active backlog (not yet cleared)
            $reg->isRetakeFail     = $reg->result === 'fail' && !$reg->isBacklogCleared
                && in_array($courseId, $backlogClearedCourseIds);
            return $reg;
        })->values();
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function checkSectionStatus(
        CourseSection $section,
        $course,
        array $passedCodes,
        Collection $enrolledSlots,
        Collection $enrolledSectionsMap
    ): array {
        // 1. Mandatory prerequisite → hard block
        if ($course->prerequisite_course_code && !in_array($course->prerequisite_course_code, $passedCodes)) {
            if ($course->prerequisite_mandatory) {
                return [
                    'blocked'     => true,
                    'advisory'    => null,
                    'reason'      => 'Prerequisite not passed: must pass "' . $course->prerequisite_course_code . '" first',
                    'reason_type' => 'prerequisite_fail',
                ];
            }
            // Non-mandatory → advisory warning, still enrollable
            $advisory = 'Recommended prerequisite "' . $course->prerequisite_course_code . '" not yet passed';
        } else {
            $advisory = null;
        }

        // 2. Schedule conflict → hard block (checked even when advisory prereq exists)
        $conflict = $this->detectScheduleConflict($section, $enrolledSlots, $enrolledSectionsMap);
        if ($conflict) {
            return [
                'blocked'     => true,
                'advisory'    => $advisory,
                'reason'      => $conflict,
                'reason_type' => 'timetable_conflict',
            ];
        }

        return [
            'blocked'     => false,
            'advisory'    => $advisory,
            'reason'      => null,
            'reason_type' => null,
        ];
    }

    private function detectScheduleConflict(
        CourseSection $section,
        Collection $enrolledSlots,
        Collection $enrolledSectionsMap
    ): ?string {
        if ($enrolledSlots->isEmpty()) {
            return null;
        }

        $sectionSlots = TimetableSlot::where('course_section_id', $section->id)->get();

        foreach ($sectionSlots as $slot) {
            $conflictSlot = $enrolledSlots
                ->where('day_of_week', $slot->day_of_week)
                ->first(fn($es) => $es->start_time < $slot->end_time && $es->end_time > $slot->start_time);

            if ($conflictSlot) {
                $conflictCourse = $enrolledSectionsMap->get($conflictSlot->course_section_id)?->course;
                return 'Schedule conflict with "' . ($conflictCourse?->name ?? 'another course') . '" on ' . $slot->day_of_week;
            }
        }

        return null;
    }

    private function blocked(string $key, string $message): array
    {
        return [
            'eligible'   => false,
            'type'       => 'blocked',
            'reason'     => $message,
            'reason_key' => $key,
            'advisory'   => null,
        ];
    }
}
