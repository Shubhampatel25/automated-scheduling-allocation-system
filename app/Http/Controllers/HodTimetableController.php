<?php

namespace App\Http\Controllers;

use App\Models\Hod;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * HodTimetableController
 *
 * Lets an HOD view timetables for students and teachers within their
 * own department only. Cross-department access returns 403.
 */
class HodTimetableController extends Controller
{
    public function __construct(private TimetableService $timetableService) {}

    // ── HOD department ID helper ──────────────────────────────────────────

    private function getHodDepartmentId(): int
    {
        $hod = Hod::where('user_id', Auth::id())->firstOrFail();
        return $hod->department_id;
    }

    // ── Student list for HOD ──────────────────────────────────────────────

    public function students(Request $request)
    {
        $departmentId = $this->getHodDepartmentId();

        $students = Student::where('department_id', $departmentId)
            ->with([
                'department',
                'studentCourseRegistrations.courseSection.course',
            ])
            ->orderBy('name')
            ->get();

        return view('hod.students.index', compact('students'));
    }

    // ── View a specific student's timetable (department-scoped) ───────────

    public function studentTimetable(Student $student)
    {
        $departmentId = $this->getHodDepartmentId();

        // Enforce department scope
        if ($student->department_id !== $departmentId) {
            abort(403, 'You are not authorized to view this student\'s timetable.');
        }

        $data = $this->timetableService->getStudentTimetable($student->id);

        $this->timetableService->logTimetableView(
            'student',
            $student->id,
            "HOD viewed student timetable: {$student->name} (ID {$student->id})"
        );

        return view('hod.timetable.student', array_merge($data, [
            'weeklySchedule' => $data['slots'],
        ]));
    }

    // ── View a specific teacher's timetable (department-scoped) ──────────

    public function teacherTimetable(Teacher $teacher)
    {
        $departmentId = $this->getHodDepartmentId();

        if ($teacher->department_id !== $departmentId) {
            abort(403, 'You are not authorized to view this teacher\'s timetable.');
        }

        $data = $this->timetableService->getProfessorTimetable($teacher->id);

        $this->timetableService->logTimetableView(
            'teacher',
            $teacher->id,
            "HOD viewed teacher timetable: {$teacher->name} (ID {$teacher->id})"
        );

        return view('hod.timetable.teacher', $data);
    }

    // ── JSON slots for popup modal (teacher) ─────────────────────────────

    public function teacherTimetableSlots(Teacher $teacher)
    {
        $departmentId = $this->getHodDepartmentId();

        if ($teacher->department_id !== $departmentId) {
            abort(403);
        }

        $data  = $this->timetableService->getProfessorTimetable($teacher->id);
        $slots = $data['slots'];
        $teacher->loadMissing('department');

        return response()->json([
            'timetable' => [
                'department' => $teacher->department?->name ?? 'N/A',
                'term'       => 'All Terms',
                'year'       => '',
                'semester'   => 0,
                'status'     => 'active',
            ],
            'slots' => $slots->map(fn($s) => [
                'day'       => $s->day_of_week,
                'start'     => substr($s->start_time, 0, 5),
                'end'       => substr($s->end_time,   0, 5),
                'component' => $s->component,
                'course'    => $s->courseSection?->course?->name ?? 'N/A',
                'code'      => $s->courseSection?->course?->code  ?? '',
                'teacher'   => $teacher->name,
                'room'      => $s->room?->room_number ?? '—',
                'term'      => ($s->timetable?->term ?? '') . ' ' . ($s->timetable?->year ?? ''),
            ])->values(),
        ]);
    }

    // ── JSON slots for popup modal (student) ─────────────────────────────

    public function studentTimetableSlots(Student $student)
    {
        $departmentId = $this->getHodDepartmentId();

        if ($student->department_id !== $departmentId) {
            abort(403);
        }

        $student->loadMissing('department');
        $data  = $this->timetableService->getStudentTimetable($student->id);
        $slots = $data['slots'];
        $tt    = $data['timetable'];

        return response()->json([
            'timetable' => [
                'department' => $tt?->department?->name ?? $student->department?->name ?? 'N/A',
                'term'       => $tt?->term  ?? '—',
                'year'       => $tt?->year  ?? '—',
                'semester'   => $student->semester,
                'status'     => $tt?->status ?? 'N/A',
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
            ])->values(),
        ]);
    }
}
