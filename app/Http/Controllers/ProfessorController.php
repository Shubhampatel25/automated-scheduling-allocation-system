<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Teacher;
use App\Models\CourseAssignment;
use App\Models\TeacherAvailability;
use App\Models\TimetableSlot;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class ProfessorController extends Controller
{
    // ── Shared helper: resolve all section IDs taught by this teacher ─────────
    private function resolveTeacherSectionIds(int $teacherId): \Illuminate\Support\Collection
    {
        // 1. Direct assignment section IDs → resolve their course IDs
        $assignedSectionIds = CourseAssignment::where('teacher_id', $teacherId)
            ->pluck('course_section_id');

        $courseIds = CourseSection::whereIn('id', $assignedSectionIds)
            ->pluck('course_id');

        $sectionIds = collect();

        // 2. All sections belonging to those courses (catches term/year-specific sections)
        if ($courseIds->isNotEmpty()) {
            $sectionIds = $sectionIds->merge(
                CourseSection::whereIn('course_id', $courseIds)->pluck('id')
            );
        }

        // 3. Sections from active timetable slots (covers generation fallback path)
        $slotSectionIds = TimetableSlot::where('teacher_id', $teacherId)
            ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
            ->pluck('course_section_id');

        return $sectionIds->merge($slotSectionIds)->unique()->values();
    }

    public function students(Request $request)
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;
        $filterSem    = $request->get('semester');
        $filterCourse = $request->get('course_id');
        $filterStatus = $request->get('status');

        $sectionIds = $teacherId ? $this->resolveTeacherSectionIds($teacherId) : collect();

        // Courses list for filter dropdown — only courses this teacher teaches
        $myCourses = collect();
        if ($sectionIds->isNotEmpty()) {
            $myCourses = Course::whereHas('sections', fn($q) => $q->whereIn('id', $sectionIds))
                ->orderBy('name')
                ->get(['id', 'name', 'semester']);
        }

        if ($sectionIds->isEmpty()) {
            $myStudents = Student::whereIn('id', [])->paginate(20);
        } else {
            $query = Student::whereHas('studentCourseRegistrations', function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)->where('status', 'enrolled');
                })
                ->with(['department', 'studentCourseRegistrations' => function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)
                      ->where('status', 'enrolled')
                      ->with('courseSection.course');
                }]);

            // Semester filter
            if ($filterSem) {
                $query->where('semester', $filterSem);
            }

            // Status filter
            if ($filterStatus) {
                $query->where('status', $filterStatus);
            }

            // Course filter — narrow sectionIds to only that course's sections
            if ($filterCourse) {
                $courseSectionIds = $sectionIds->intersect(
                    CourseSection::where('course_id', $filterCourse)->pluck('id')
                )->values();

                $query->whereHas('studentCourseRegistrations', function ($q) use ($courseSectionIds) {
                    $q->whereIn('course_section_id', $courseSectionIds)->where('status', 'enrolled');
                });
            }

            $myStudents = $query->orderBy('name')->paginate(20)->withQueryString();
        }

        return view('professor.students', compact(
            'myStudents', 'teacher',
            'myCourses', 'filterSem', 'filterCourse', 'filterStatus'
        ));
    }

    /**
     * JSON endpoint: return a student's timetable slots.
     * Only accessible if the student is enrolled in at least one of this
     * teacher's sections (prevents viewing unrelated students' schedules).
     */
    public function studentSlots(Student $student)
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId) {
            abort(403);
        }

        // Security: ensure student is in one of this professor's sections
        $sectionIds = $this->resolveTeacherSectionIds($teacherId);
        $isEnrolled = $student->studentCourseRegistrations()
            ->whereIn('course_section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->exists();

        if (!$isEnrolled) {
            abort(403);
        }

        // Fetch all active timetable slots for this student (via their enrolled sections)
        $enrolledSectionIds = $student->studentCourseRegistrations()
            ->where('status', 'enrolled')
            ->pluck('course_section_id');

        $slots = TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)
            ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
            ->with(['courseSection.course', 'teacher', 'room', 'timetable'])
            ->get();

        $student->loadMissing('department');

        // Detect retakes: courses the student previously failed (completed + result=fail)
        // A slot is a retake if the student has a prior failed registration for the same course.
        $failedCourseIds = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'completed')
            ->where('result', 'fail')
            ->with('courseSection')
            ->get()
            ->pluck('courseSection.course_id')
            ->filter()
            ->unique();

        return response()->json([
            'timetable' => [
                'department' => $student->department?->name ?? 'N/A',
                'term'       => 'All Terms',
                'year'       => '',
                'semester'   => $student->semester,
                'status'     => 'active',
            ],
            'slots' => $slots->map(fn($s) => [
                'day'       => $s->day_of_week,
                'start'     => substr($s->start_time, 0, 5),
                'end'       => substr($s->end_time,   0, 5),
                'component' => $s->component,
                'course'    => $s->courseSection?->course?->name ?? 'N/A',
                'code'      => $s->courseSection?->course?->code  ?? '',
                'teacher'   => $s->teacher?->name ?? '—',
                'room'      => $s->room?->room_number ?? '—',
                'term'      => ($s->timetable?->term ?? '') . ' ' . ($s->timetable?->year ?? ''),
                'is_retake' => $failedCourseIds->contains($s->courseSection?->course_id),
            ])->values(),
        ]);
    }

    /**
     * Dedicated timetable page — all active teaching slots for this professor.
     */
    public function timetable()
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->with('department')->first();
        $teacherId = $teacher?->id;

        $timetableSlots = $teacherId
            ? TimetableSlot::where('teacher_id', $teacherId)
                ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
                ->with(['courseSection.course', 'timetable', 'room'])
                ->get()
            : collect();

        $classesPerWeek = $timetableSlots->count();
        $hoursPerWeek   = round($timetableSlots->sum(
            fn($s) => (strtotime($s->end_time) - strtotime($s->start_time)) / 3600
        ), 1);

        return view('professor.timetable', compact(
            'teacher', 'timetableSlots', 'classesPerWeek', 'hoursPerWeek'
        ));
    }

    public function availability()
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;

        $availability = $teacherId
            ? TeacherAvailability::where('teacher_id', $teacherId)
                ->orderByRaw("FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('professor.availability', compact('availability', 'teacher'));
    }
}
