<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Hod;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HodPagesController extends Controller
{
    private function getDepartmentId(): ?int
    {
        $hod = Hod::where('user_id', Auth::id())->first();
        return $hod ? $hod->department_id : null;
    }

    // ─── 1. Assign Course ────────────────────────────────────────────────────

    public function assignCourse()
    {
        $departmentId = $this->getDepartmentId();

        $teachers = $departmentId
            ? Teacher::where('department_id', $departmentId)->where('status', 'active')->orderBy('name')->get()
            : collect();

        $courseSections = $departmentId
            ? CourseSection::whereHas('course', fn($q) => $q->where('department_id', $departmentId))
                ->with('course')
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $assignments = $departmentId
            ? CourseAssignment::whereHas('courseSection.course', fn($q) => $q->where('department_id', $departmentId))
                ->with(['courseSection.course', 'teacher'])
                ->latest('assigned_date')
                ->get()
            : collect();

        return view('hod.assign_course', compact('teachers', 'courseSections', 'assignments'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'course_section_id' => 'required|exists:course_sections,id',
            'teacher_id'        => 'required|exists:teachers,id',
            'component'         => 'required|in:theory,lab',
        ]);

        // Prevent duplicate assignment for same section + component
        $exists = CourseAssignment::where('course_section_id', $request->course_section_id)
            ->where('component', $request->component)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This section already has a teacher assigned for the selected component.');
        }

        CourseAssignment::create([
            'course_section_id' => $request->course_section_id,
            'teacher_id'        => $request->teacher_id,
            'component'         => $request->component,
            'assigned_date'     => now()->toDateString(),
        ]);

        return back()->with('success', 'Course assigned to teacher successfully!');
    }

    public function destroyAssignment(CourseAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Assignment removed successfully!');
    }

    // ─── 2. Generate Timetable (form page) ──────────────────────────────────

    public function generateTimetable()
    {
        $departmentId = $this->getDepartmentId();

        $existingTimetables = $departmentId
            ? Timetable::where('department_id', $departmentId)
                ->withCount('timetableSlots as slot_count')
                ->with('generatedByUser')
                ->latest('generated_at')
                ->get()
            : collect();

        return view('hod.generate_timetable', compact('existingTimetables'));
    }

    // ─── 3. View Timetable ───────────────────────────────────────────────────

    public function viewTimetable(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $timetables = $departmentId
            ? Timetable::where('department_id', $departmentId)->latest('generated_at')->get()
            : collect();

        $selectedId = $request->timetable_id
            ?? ($timetables->firstWhere('status', 'active')?->id ?? $timetables->first()?->id);

        $selectedTimetable = $selectedId ? Timetable::find($selectedId) : null;

        $timetableSlots = $selectedId
            ? TimetableSlot::where('timetable_id', $selectedId)
                ->with(['courseSection.course', 'teacher', 'room'])
                ->get()
            : collect();

        return view('hod.view_timetable', compact('timetables', 'selectedTimetable', 'timetableSlots'));
    }

    // ─── 4. Faculty Workload ─────────────────────────────────────────────────

    public function facultyWorkload()
    {
        $departmentId = $this->getDepartmentId();

        $activeSlots = $departmentId
            ? TimetableSlot::whereHas('timetable', fn($q) =>
                $q->where('department_id', $departmentId)->where('status', 'active'))
                ->with(['courseSection.course', 'room'])
                ->get()
            : collect();

        $facultyWorkload = $departmentId
            ? Teacher::where('department_id', $departmentId)
                ->withCount('courseAssignments as courses_count')
                ->orderBy('name')
                ->get()
                ->each(function ($teacher) use ($activeSlots) {
                    $slots = $activeSlots->where('teacher_id', $teacher->id);
                    $teacher->classes_per_week = $slots->count();
                    $teacher->hours_per_week   = round($slots->sum(fn($s) =>
                        (strtotime($s->end_time) - strtotime($s->start_time)) / 3600
                    ), 1);
                    $teacher->schedule = $slots->sortBy(['day_of_week', 'start_time'])->values();
                })
            : collect();

        return view('hod.faculty_workload', compact('facultyWorkload'));
    }

    // ─── 5. Approve Schedule ─────────────────────────────────────────────────

    public function approveSchedule()
    {
        $departmentId = $this->getDepartmentId();

        $timetables = $departmentId
            ? Timetable::where('department_id', $departmentId)
                ->withCount(['timetableSlots as slot_count', 'conflicts as conflict_count'])
                ->with('generatedByUser')
                ->latest('generated_at')
                ->get()
            : collect();

        return view('hod.approve_schedule', compact('timetables'));
    }
}
