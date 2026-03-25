<?php

namespace App\Http\Controllers;

use App\Models\Conflict;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Department;
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

    // ─── 0. Department Courses page ──────────────────────────────────────────

    public function courses(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $courses = $departmentId
            ? Course::where('department_id', $departmentId)
                ->with(['sections.assignments.teacher'])
                ->orderBy('code')
                ->get()
            : collect();

        $totalCourses    = $courses->count();
        $assignedCourses = $courses->filter(fn($c) =>
            $c->sections->flatMap(fn($s) => $s->assignments)->isNotEmpty()
        )->count();
        $unassignedCourses  = $totalCourses - $assignedCourses;
        $totalStudents      = $courses->sum(fn($c) => $c->sections->sum('enrolled_students'));

        return view('hod.courses', compact(
            'courses', 'totalCourses', 'assignedCourses', 'unassignedCourses', 'totalStudents'
        ));
    }

    // ─── 0b. Course Assignments page ─────────────────────────────────────────

    public function assignments(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $assignments = $departmentId
            ? CourseAssignment::whereHas('courseSection.course', fn($q) => $q->where('department_id', $departmentId))
                ->with(['courseSection.course', 'teacher'])
                ->latest('assigned_date')
                ->get()
            : collect();

        $totalAssignments  = $assignments->count();
        $teachersAssigned  = $assignments->pluck('teacher_id')->unique()->count();
        $coursesCovered    = $assignments->pluck('courseSection.course_id')->unique()->count();

        return view('hod.assignments', compact(
            'assignments', 'totalAssignments', 'teachersAssigned', 'coursesCovered'
        ));
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

        $existingTimetables = Timetable::where('generated_by', Auth::id())
            ->withCount('timetableSlots as slot_count')
            ->with(['generatedByUser', 'department'])
            ->latest('generated_at')
            ->get();

        $departments = Department::orderBy('name')->get();
        $years       = range(now()->year, now()->year + 3);

        $courses = $departmentId
            ? Course::where('department_id', $departmentId)->orderBy('code')->get()
            : collect();

        return view('hod.generate_timetable', compact('existingTimetables', 'departments', 'courses', 'years'));
    }

    // ─── 2b. Departments by Semester (AJAX) ─────────────────────────────────

    public function departmentsBySemester(Request $request)
    {
        $departmentIds = Course::where('semester', $request->semester)
            ->pluck('department_id')
            ->unique();

        $departments = Department::whereIn('id', $departmentIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json($departments);
    }

    // ─── 2c. Courses by Department + Semester (AJAX) ─────────────────────────

    public function coursesByDepartment(Request $request)
    {
        $query = Course::where('department_id', $request->department_id);

        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        $courses = $query->orderBy('code')->get(['id', 'code', 'name']);

        return response()->json($courses);
    }

    // ─── 3. View Timetable ───────────────────────────────────────────────────

    public function viewTimetable(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $timetables = Timetable::where('generated_by', Auth::id())
            ->latest('generated_at')
            ->get();

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

        $timetables = Timetable::where('generated_by', Auth::id())
            ->withCount(['timetableSlots as slot_count', 'conflicts as conflict_count'])
            ->with(['generatedByUser', 'department'])
            ->latest('generated_at')
            ->get();

        return view('hod.approve_schedule', compact('timetables'));
    }

    // ─── 6. Faculty Members page ─────────────────────────────────────────────

    public function facultyMembers(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $department = $departmentId ? Department::find($departmentId) : null;

        $activeSlots = $departmentId
            ? TimetableSlot::whereHas('timetable', fn($q) =>
                $q->where('department_id', $departmentId)->where('status', 'active'))
                ->get()
            : collect();

        $teachers = $departmentId
            ? Teacher::where('department_id', $departmentId)
                ->with(['courseAssignments.courseSection.course'])
                ->orderBy('name')
                ->get()
                ->each(function ($teacher) use ($activeSlots) {
                    $slots = $activeSlots->where('teacher_id', $teacher->id);
                    $teacher->classes_per_week = $slots->count();
                    $teacher->hours_per_week   = round($slots->sum(fn($s) =>
                        (strtotime($s->end_time) - strtotime($s->start_time)) / 3600
                    ), 1);
                })
            : collect();

        $totalFaculty       = $teachers->count();
        $activeMembers      = $teachers->where('status', 'active')->count();
        $handlingCourses    = $teachers->filter(fn($t) => $t->courseAssignments->isNotEmpty())->count();
        $totalWeeklyHours   = $teachers->sum('hours_per_week');
        $assignedFaculty    = $handlingCourses;
        $activeWeeklyHours  = $teachers->sum('hours_per_week');

        // Faculty members count & course owners (teachers with assignments)
        $facultyMembersCount = $totalFaculty;
        $courseOwners        = $handlingCourses;
        $activeRightNow      = $activeMembers;

        return view('hod.faculty_members', compact(
            'department', 'teachers',
            'totalFaculty', 'activeMembers', 'handlingCourses', 'totalWeeklyHours',
            'assignedFaculty', 'activeWeeklyHours',
            'facultyMembersCount', 'courseOwners', 'activeRightNow'
        ));
    }

    // ─── 7. Department Timetable ─────────────────────────────────────────────

    public function departmentTimetable(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        // Show all active timetables for the department grouped by semester
        $activeTimetables = $departmentId
            ? Timetable::where('department_id', $departmentId)
                ->where('status', 'active')
                ->withCount('timetableSlots as slot_count')
                ->orderBy('semester')
                ->get()
            : collect();

        $selectedId = $request->timetable_id ?? $activeTimetables->first()?->id;
        $selectedTimetable = $selectedId ? Timetable::find($selectedId) : null;

        $timetableSlots = $selectedId
            ? TimetableSlot::where('timetable_id', $selectedId)
                ->with(['courseSection.course', 'teacher', 'room'])
                ->get()
            : collect();

        $department = $departmentId ? Department::find($departmentId) : null;

        return view('hod.department_timetable', compact(
            'activeTimetables', 'selectedTimetable', 'timetableSlots', 'department'
        ));
    }

    // ─── 8. Department Report ────────────────────────────────────────────────

    public function departmentReport()
    {
        $departmentId = $this->getDepartmentId();
        $department   = $departmentId ? Department::find($departmentId) : null;

        // Courses stats
        $courses = $departmentId
            ? Course::where('department_id', $departmentId)->with('sections.assignments')->get()
            : collect();

        $totalCourses      = $courses->count();
        $activeCourses     = $courses->where('status', 'active')->count();
        $assignedCourses   = $courses->filter(fn($c) => $c->sections->flatMap(fn($s) => $s->assignments)->isNotEmpty())->count();
        $coursesBySemester = $courses->groupBy('semester')->map->count()->sortKeys();
        $coursesByType     = $courses->groupBy('type')->map->count();

        // Faculty stats
        $teachers = $departmentId
            ? Teacher::where('department_id', $departmentId)
                ->withCount('courseAssignments as assignments_count')
                ->get()
            : collect();

        $totalTeachers    = $teachers->count();
        $activeTeachers   = $teachers->where('status', 'active')->count();
        $assignedTeachers = $teachers->filter(fn($t) => $t->assignments_count > 0)->count();

        // Timetable stats
        $timetables = $departmentId
            ? Timetable::where('department_id', $departmentId)
                ->withCount(['timetableSlots as slot_count', 'conflicts as conflict_count'])
                ->latest('generated_at')
                ->get()
            : collect();

        $activeTimetable   = $timetables->firstWhere('status', 'active');
        $totalTimetables   = $timetables->count();
        $totalConflicts    = $timetables->sum('conflict_count');

        // Weekly schedule load from active timetable
        $activeSlots = $activeTimetable
            ? TimetableSlot::where('timetable_id', $activeTimetable->id)
                ->with(['courseSection.course', 'teacher', 'room'])
                ->get()
            : collect();

        $slotsByDay  = $activeSlots->groupBy('day_of_week')->map->count();
        $totalHours  = round($activeSlots->sum(fn($s) =>
            (strtotime($s->end_time) - strtotime($s->start_time)) / 3600
        ), 1);

        return view('hod.department_report', compact(
            'department',
            'totalCourses', 'activeCourses', 'assignedCourses', 'coursesBySemester', 'coursesByType',
            'totalTeachers', 'activeTeachers', 'assignedTeachers', 'teachers',
            'timetables', 'activeTimetable', 'totalTimetables', 'totalConflicts',
            'activeSlots', 'slotsByDay', 'totalHours'
        ));
    }

    // ─── 9. Conflicts page ───────────────────────────────────────────────────

    public function conflicts(Request $request)
    {
        $departmentId = $this->getDepartmentId();

        $department = $departmentId ? Department::find($departmentId) : null;

        $conflicts = $departmentId
            ? Conflict::whereHas('timetable', fn($q) => $q->where('department_id', $departmentId))
                ->with([
                    'slot1.courseSection.course', 'slot1.teacher', 'slot1.room',
                    'slot2.courseSection.course', 'slot2.teacher', 'slot2.room',
                ])
                ->latest('detected_at')
                ->get()
            : collect();

        $unresolved      = $conflicts->where('status', 'unresolved')->count();
        $resolved        = $conflicts->where('status', 'resolved')->count();
        $teacherConflicts = $conflicts->filter(fn($c) => str_contains($c->conflict_type, 'teacher'))->count();
        $roomConflicts    = $conflicts->filter(fn($c) => str_contains($c->conflict_type, 'room'))->count();

        return view('hod.conflicts', compact(
            'department', 'conflicts', 'unresolved', 'resolved', 'teacherConflicts', 'roomConflicts'
        ));
    }
}
