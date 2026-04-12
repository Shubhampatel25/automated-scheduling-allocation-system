<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CourseSection;
use App\Models\Hod;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\RegistrationEligibilityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * TimetableService — read-only retrieval layer for role-based timetable viewing.
 *
 * This service wraps existing Eloquent queries used across DashboardController
 * and HodPagesController. No generation logic is touched.
 */
class TimetableService
{
    // ── Student timetable ─────────────────────────────────────────────────

    /**
     * Return the active (or draft-fallback) timetable slots for a student,
     * filtered to the sections in which that student is enrolled.
     *
     * Replicates the logic already in DashboardController::studentTimetable()
     * so admin/HOD viewers get exactly the same data a student would see.
     *
     * @return array{slots: Collection, timetable: ?Timetable, isDraft: bool, semester: int}
     */
    public function getStudentTimetable(int $studentId): array
    {
        $student = Student::with([
            'studentCourseRegistrations.courseSection',
        ])->findOrFail($studentId);

        $enrolledSectionIds = $student->studentCourseRegistrations
            ->where('status', 'enrolled')
            ->pluck('course_section_id')
            ->filter()
            ->unique()
            ->values();

        if ($enrolledSectionIds->isEmpty()) {
            return [
                'slots'           => collect(),
                'timetable'       => null,
                'isDraft'         => false,
                'semester'        => $student->semester,
                'student'         => $student,
                'retakeCourseIds' => [],
            ];
        }

        // ── Build term/year-scoped slot query ─────────────────────────────
        //
        // Load enrolled sections (with term + year) so we can scope the timetable
        // query to exactly the terms the student is actually registered for.
        // This prevents showing slots from other active terms (e.g. Winter slots
        // when the student is only enrolled in Fall).
        //
        // For retake students enrolled in sections from multiple terms (e.g. an old
        // failed course's term plus the current term), slots from each term are
        // included correctly — no data is lost.
        $enrolledSections = CourseSection::whereIn('id', $enrolledSectionIds)
            ->get(['id', 'course_id', 'term', 'year']);

        $enrolledCourseIds = $enrolledSections
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($enrolledCourseIds)) {
            return [
                'slots'           => collect(),
                'timetable'       => null,
                'isDraft'         => false,
                'semester'        => $student->semester,
                'student'         => $student,
                'retakeCourseIds' => [],
            ];
        }

        // Distinct (term, year) pairs from the student's own enrollments
        $termYearPairs = $enrolledSections
            ->map(fn($s) => ['term' => $s->term, 'year' => $s->year])
            ->unique(fn($p) => $p['term'] . '|' . $p['year'])
            ->values();

        $slots = TimetableSlot::whereHas('courseSection',
                fn($q) => $q->whereIn('course_id', $enrolledCourseIds)
            )
            ->whereHas('timetable', function ($q) use ($termYearPairs, $student) {
                $q->whereIn('status', ['active', 'draft'])
                  ->where('department_id', $student->department_id)
                  ->where(function ($inner) use ($termYearPairs) {
                      foreach ($termYearPairs as $pair) {
                          $inner->orWhere(fn($sub) =>
                              $sub->where('term', $pair['term'])
                                  ->where('year', $pair['year'])
                          );
                      }
                  });
            })
            ->with(['courseSection.course', 'teacher', 'room', 'timetable'])
            ->get()
            ->unique('id');

        // isDraft = any slot comes from a draft timetable
        $isDraft = $slots->contains(fn($s) => ($s->timetable->status ?? '') === 'draft');

        // For the meta-bar: pick the most recent timetable referenced by the slots
        $timetable = $slots->sortByDesc(fn($s) => $s->timetable->generated_at ?? '')
            ->first()
            ?->timetable;

        // Retake course IDs — used by views to render the RETAKE badge
        $retakeCourseIds = [];
        try {
            $eligSvc         = app(RegistrationEligibilityService::class);
            $retakeCourseIds = $eligSvc->getNetFailedCourseIds($studentId);
        } catch (\Throwable $e) {
            // Non-fatal: badge simply won't appear if service is unavailable
        }

        return [
            'slots'           => $slots,
            'timetable'       => $timetable,
            'isDraft'         => $isDraft,
            'semester'        => $student->semester,
            'student'         => $student,
            'retakeCourseIds' => $retakeCourseIds,
        ];
    }

    // ── Professor / teacher timetable ─────────────────────────────────────

    /**
     * Return all active timetable slots assigned to a teacher.
     *
     * @return array{slots: Collection, teacher: Teacher}
     */
    public function getProfessorTimetable(int $teacherId): array
    {
        $teacher = Teacher::with('department')->findOrFail($teacherId);

        $slots = TimetableSlot::whereHas('timetable', fn($q) =>
                $q->where('status', 'active'))
            ->where('teacher_id', $teacherId)
            ->with(['timetable', 'courseSection.course', 'room'])
            ->get();

        return [
            'slots'   => $slots,
            'teacher' => $teacher,
        ];
    }

    // ── Department timetable ──────────────────────────────────────────────

    /**
     * Return the active timetable and its slots for a department.
     * Used by admin to view a HOD's department timetable.
     *
     * @return array{timetables: Collection, selectedTimetable: ?Timetable, slots: Collection}
     */
    public function getDepartmentTimetable(int $departmentId, ?int $selectedId = null): array
    {
        $timetables = Timetable::where('department_id', $departmentId)
            ->where('status', 'active')
            ->withCount('timetableSlots as slot_count')
            ->orderBy('semester')
            ->get();

        $selectedId ??= $timetables->first()?->id;

        $selectedTimetable = $selectedId ? Timetable::find($selectedId) : null;

        $slots = $selectedId
            ? TimetableSlot::where('timetable_id', $selectedId)
                ->with(['courseSection.course', 'teacher', 'room'])
                ->get()
            : collect();

        return [
            'timetables'        => $timetables,
            'selectedTimetable' => $selectedTimetable,
            'slots'             => $slots,
        ];
    }

    // ── Audit logging ─────────────────────────────────────────────────────

    /**
     * Write an entry to the existing activity_logs table whenever an admin
     * or HOD views another user's timetable.
     *
     * Uses the ActivityLog model already present in the project.
     * No new table is created — the existing audit trail is reused.
     */
    public function logTimetableView(string $targetType, int $targetId, string $context = ''): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'view_timetable',
            'entity_type' => $targetType,
            'entity_id'   => $targetId,
            'details'     => $context ?: null,
            'created_at'  => now(),
        ]);
    }
}
