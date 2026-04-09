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
}
