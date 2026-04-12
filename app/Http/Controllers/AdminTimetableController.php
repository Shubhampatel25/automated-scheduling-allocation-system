<?php

namespace App\Http\Controllers;

use App\Models\Hod;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\TimetableService;
use Illuminate\Http\Request;

/**
 * AdminTimetableController
 *
 * Lets admin view any student's, teacher's, or HOD-department timetable.
 * Thin controller — all data retrieval delegated to TimetableService.
 * All routes are protected by the existing role:admin middleware.
 */
class AdminTimetableController extends Controller
{
    public function __construct(private TimetableService $timetableService) {}

    // ── View any student's timetable ──────────────────────────────────────

    public function studentTimetable(Student $student)
    {
        $data = $this->timetableService->getStudentTimetable($student->id);

        $this->timetableService->logTimetableView(
            'student',
            $student->id,
            "Admin viewed student timetable: {$student->name} (ID {$student->id})"
        );

        return view('admin.timetable.student', array_merge($data, [
            'weeklySchedule'  => $data['slots'],
            'retakeCourseIds' => $data['retakeCourseIds'] ?? [],
        ]));
    }

    // ── JSON endpoint for popup modal ─────────────────────────────────────

    public function studentTimetableSlots(Student $student)
    {
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
                'teacher'   => $s->teacher?->name     ?? '—',
                'room'      => $s->room?->room_number ?? '—',
            ])->values(),
        ]);
    }

    // ── View any teacher's timetable ──────────────────────────────────────

    public function teacherTimetable(Teacher $teacher)
    {
        $data = $this->timetableService->getProfessorTimetable($teacher->id);

        $this->timetableService->logTimetableView(
            'teacher',
            $teacher->id,
            "Admin viewed teacher timetable: {$teacher->name} (ID {$teacher->id})"
        );

        return view('admin.timetable.teacher', $data);
    }

    public function teacherTimetableSlots(Teacher $teacher)
    {
        $data  = $this->timetableService->getProfessorTimetable($teacher->id);
        $slots = $data['slots'];

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

    // ── View a HOD's personal teaching timetable ─────────────────────────
    //
    // Shows only slots where the HOD themselves is the assigned teacher —
    // NOT the whole department. Uses getProfessorTimetable() on the HOD's
    // linked teacher record, consistent with what admin sees for any teacher.

    public function hodTimetable(Hod $hod)
    {
        // Load the HOD's teacher record
        $hod->load(['teacher.department']);
        $teacher = $hod->teacher;

        if (! $teacher) {
            return back()->with('error', 'This HOD has no linked teacher record.');
        }

        $data = $this->timetableService->getProfessorTimetable($teacher->id);

        $this->timetableService->logTimetableView(
            'teacher',
            $teacher->id,
            "Admin viewed HOD teaching timetable: {$teacher->name} (HOD ID {$hod->id})"
        );

        return view('admin.timetable.hod', array_merge($data, [
            'hod' => $hod,
        ]));
    }

    public function hodTimetableSlots(Hod $hod)
    {
        $hod->load(['teacher.department', 'department']);
        $teacher = $hod->teacher;

        if (! $teacher) {
            return response()->json(['timetable' => [], 'slots' => []]);
        }

        $data  = $this->timetableService->getProfessorTimetable($teacher->id);
        $slots = $data['slots'];

        return response()->json([
            'timetable' => [
                'department' => $hod->department?->name ?? $teacher->department?->name ?? 'N/A',
                'term'       => '—',
                'year'       => '—',
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
            ])->values(),
        ]);
    }
}
