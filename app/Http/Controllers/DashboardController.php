<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Conflict;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\FeePayment;
use App\Models\Hod;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentCourseRegistration;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin()
    {
        try {
            $departmentCount = Department::count();
            $teacherCount = Teacher::count();
            $courseCount = Course::count();
            $roomCount = Room::count();
            $studentCount = Student::count();
            $conflictCount = Conflict::where('status', 'unresolved')->count();

            $recentTeachers    = Teacher::with('department')->latest('created_at')->take(5)->get();
            $recentCourses     = Course::latest('created_at')->take(5)->get();
            $recentActivities  = ActivityLog::latest('created_at')->take(10)->get();
            $recentStudents    = Student::with('department')->latest('created_at')->take(5)->get();
            $recentRooms       = Room::latest('created_at')->take(5)->get();
            $recentDepartments = Department::with(['hods.teacher'])->withCount(['teachers', 'courses'])->latest('created_at')->take(10)->get();
            $timetables        = Timetable::with(['department', 'generatedByUser'])->latest('generated_at')->take(10)->get();
            $conflicts         = Conflict::with(['timetable.department', 'slot1'])->where('status', 'unresolved')->latest('detected_at')->take(10)->get();

            return view('admin.dashboard', compact(
                'departmentCount', 'teacherCount', 'courseCount', 'roomCount',
                'studentCount', 'conflictCount', 'recentTeachers', 'recentCourses',
                'recentActivities', 'recentStudents', 'recentRooms', 'recentDepartments',
                'timetables', 'conflicts'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    public function hod()
    {
        try {
            $user = Auth::user();
            $hod = Hod::where('user_id', $user->id)->first();
            $departmentId = $hod ? $hod->department_id : null;

            $facultyMembers = $departmentId
                ? Teacher::where('department_id', $departmentId)
                    ->withCount('courseAssignments as courses_count')
                    ->get()
                : collect();

            $facultyCount = $facultyMembers->count();

            $departmentCourses = $departmentId
                ? Course::where('department_id', $departmentId)
                    ->with(['sections.assignments.teacher'])
                    ->get()
                : collect();

            $courseCount = $departmentCourses->count();

            $assignmentCount = $departmentId
                ? CourseAssignment::whereHas('courseSection.course', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->count()
                : 0;

            $conflicts = $departmentId
                ? Conflict::whereHas('timetable', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->with('slot1')->latest('detected_at')->take(10)->get()
                : collect();

            $conflictCount = $departmentId
                ? Conflict::whereHas('timetable', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->where('status', 'unresolved')->count()
                : 0;

            $timetableSlots = $departmentId
                ? TimetableSlot::whereHas('timetable', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId)->where('status', 'active');
                })->with(['courseSection.course', 'teacher', 'room'])->get()
                : collect();

            $courseAssignments = $departmentId
                ? CourseAssignment::whereHas('courseSection.course', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                })->with(['courseSection.course', 'teacher'])->get()
                : collect();

            $facultyWorkload = $departmentId
                ? Teacher::where('department_id', $departmentId)
                    ->withCount('courseAssignments as courses_count')
                    ->get()
                    ->each(function ($teacher) use ($timetableSlots) {
                        $slots = $timetableSlots->where('teacher_id', $teacher->id);
                        $teacher->classes_per_week = $slots->count();
                        $teacher->hours_per_week   = round($slots->sum(function ($s) {
                            return (strtotime($s->end_time) - strtotime($s->start_time)) / 3600;
                        }), 1);
                    })
                : collect();

            return view('hod.dashboard', compact(
                'facultyCount', 'courseCount', 'assignmentCount', 'conflictCount',
                'facultyMembers', 'departmentCourses', 'conflicts', 'timetableSlots',
                'courseAssignments', 'facultyWorkload'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    public function professor()
    {
        try {
            $user    = Auth::user();
            $teacher = Teacher::where('user_id', $user->id)->with('department')->first();
            $teacherId = $teacher ? $teacher->id : null;

            // Assigned courses with total enrolled students
            $assignedCourses = $teacherId
                ? Course::whereHas('sections.assignments', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->withCount(['sections as students_count' => function ($q) {
                    $q->select(DB::raw('COALESCE(SUM(enrolled_students), 0)'));
                }])->get()
                : collect();

            $courseCount = $assignedCourses->count();

            // All timetable slots for this teacher (active timetable only)
            $allSlots = $teacherId
                ? TimetableSlot::where('teacher_id', $teacherId)
                    ->whereHas('timetable', function ($q) { $q->where('status', 'active'); })
                    ->with(['courseSection.course', 'room'])
                    ->get()
                : collect();

            $classesPerWeek = $allSlots->count();
            $hoursPerWeek   = round($allSlots->sum(function ($slot) {
                return (strtotime($slot->end_time) - strtotime($slot->start_time)) / 3600;
            }), 1);

            // Total enrolled students across all assigned sections
            $studentCount = $teacherId
                ? CourseSection::whereHas('assignments', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->sum('enrolled_students')
                : 0;

            // Section IDs assigned to this teacher
            $sectionIds = $teacherId
                ? CourseAssignment::where('teacher_id', $teacherId)->pluck('course_section_id')
                : collect();

            // Students enrolled in those sections
            $myStudents = $sectionIds->isNotEmpty()
                ? Student::whereHas('studentCourseRegistrations', function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)->where('status', 'enrolled');
                })->with(['department', 'studentCourseRegistrations' => function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)->with('courseSection.course');
                }])->get()
                : collect();

            // Teacher availability slots ordered by day then time
            $availability = $teacherId
                ? TeacherAvailability::where('teacher_id', $teacherId)
                    ->orderByRaw("FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
                    ->orderBy('start_time')
                    ->get()
                : collect();

            $today         = now()->format('l');
            $todaySchedule = $allSlots->where('day_of_week', $today)->sortBy('start_time')->values();
            $weeklySchedule = $allSlots;

            $department = $teacher && $teacher->department ? $teacher->department->name : 'N/A';
            $employeeId = $teacher ? $teacher->employee_id : 'N/A';

            return view('professor.dashboard', compact(
                'courseCount', 'classesPerWeek', 'studentCount', 'hoursPerWeek',
                'assignedCourses', 'todaySchedule', 'weeklySchedule',
                'myStudents', 'availability', 'department', 'employeeId', 'teacher'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    public function schedule(\Illuminate\Http\Request $request)
    {
        $departments = Department::orderBy('name')->get();
        $query = Timetable::with(['department', 'generatedByUser'])
            ->withCount('timetableSlots as slot_count');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $timetables = $query->latest('generated_at')->paginate(15)->withQueryString();

        return view('admin.schedule.index', compact('timetables', 'departments'));
    }

    public function conflicts(\Illuminate\Http\Request $request)
    {
        $query = Conflict::with(['timetable.department', 'slot1.teacher', 'slot1.room', 'slot1.courseSection.course']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('conflict_type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $conflicts = $query->latest('detected_at')->paginate(20)->withQueryString();
        $unresolvedCount = Conflict::where('status', 'unresolved')->count();
        $resolvedCount   = Conflict::where('status', 'resolved')->count();

        return view('admin.conflicts.index', compact('conflicts', 'unresolvedCount', 'resolvedCount'));
    }

    public function activity(\Illuminate\Http\Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', '%' . $request->search . '%')
                  ->orWhere('entity_type', 'like', '%' . $request->search . '%')
                  ->orWhere('details', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $logs = $query->latest('created_at')->paginate(25)->withQueryString();
        $entityTypes = ActivityLog::select('entity_type')->distinct()->whereNotNull('entity_type')->pluck('entity_type');

        return view('admin.activity.index', compact('logs', 'entityTypes'));
    }

    public function student()
    {
        try {
            $user = Auth::user();
            $studentRecord = Student::with('department')->where('user_id', $user->id)->first();
            $studentId = $studentRecord ? $studentRecord->id : null;

            // Enrolled-only IDs used to filter available sections
            $enrolledSectionIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->pluck('course_section_id')
                : collect();

            // All registrations (enrolled + completed) for My Courses display
            $enrolledRegistrations = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->whereIn('status', ['enrolled', 'completed'])
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->get()
                : collect();

            // Build courses list with registration status for badge/drop logic
            $enrolledCourses = $enrolledRegistrations->map(function ($reg) {
                $section = $reg->courseSection;
                $course  = $section ? clone $section->course : null;
                if ($course) {
                    $course->registrationId     = $reg->id;
                    $course->registrationStatus = $reg->status;
                    $course->sectionInfo        = $section;
                    $assignment = $section->assignments->first();
                    $course->teacherName = $assignment && $assignment->teacher ? $assignment->teacher->name : 'TBA';
                }
                return $course;
            })->filter()->values();

            $courseCount  = $enrolledCourses->where('registrationStatus', 'enrolled')->count();
            $totalCredits = $enrolledCourses->where('registrationStatus', 'enrolled')->sum('credits');

            $teacherCount = $enrolledSectionIds->isNotEmpty()
                ? CourseAssignment::whereIn('course_section_id', $enrolledSectionIds)
                    ->distinct()->count('teacher_id')
                : 0;

            $allSlots = $enrolledSectionIds->isNotEmpty()
                ? TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)
                    ->whereHas('timetable', function ($q) { $q->where('status', 'active'); })
                    ->with(['courseSection.course', 'teacher', 'room'])
                    ->get()
                : collect();

            $classesPerWeek = $allSlots->count();
            $today          = now()->format('l');
            $todaySchedule  = $allSlots->where('day_of_week', $today)->sortBy('start_time')->values();
            $weeklySchedule = $allSlots;

            // Check fee payment status for current semester
            $currentYear = now()->year;
            $feeRecord   = null;
            $feePaid     = false;
            if ($studentRecord) {
                $feeRecord = FeePayment::where('student_id', $studentRecord->id)
                    ->where('semester', $studentRecord->semester)
                    ->where('year', $currentYear)
                    ->first();

                // Auto-sync fee amount from enrolled courses if record exists and is not yet paid
                if ($feeRecord && $feeRecord->status !== 'paid') {
                    $calculatedTotal = $enrolledCourses
                        ->where('registrationStatus', 'enrolled')
                        ->sum(fn($c) => (float) ($c->fee ?? 0));
                    if ((float) $feeRecord->amount !== $calculatedTotal) {
                        DB::table('fee_payments')
                            ->where('id', $feeRecord->id)
                            ->update(['amount' => $calculatedTotal]);
                        $feeRecord->amount = $calculatedTotal;
                    }
                }

                $feePaid = $feeRecord && $feeRecord->status === 'paid';
            }

            // Available sections: only from student's department + semester, not enrolled, has capacity
            $availableSections = collect();
            if ($studentRecord && $feePaid) {
                $studentDeptId = $studentRecord->department_id;
                $studentSemester = $studentRecord->semester;

                $availableSections = CourseSection::whereNotIn('id', $enrolledSectionIds->toArray())
                    ->whereColumn('enrolled_students', '<', 'max_students')
                    ->whereHas('course', function ($q) use ($studentDeptId, $studentSemester) {
                        $q->where('department_id', $studentDeptId)
                          ->where(function ($q2) use ($studentSemester) {
                              $q2->where('semester', $studentSemester)
                                 ->orWhereNull('semester');
                          });
                    })
                    ->with(['course.department', 'assignments.teacher'])
                    ->get();
            }

            $department = $studentRecord && $studentRecord->department
                ? $studentRecord->department->name
                : 'N/A';
            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            return view('student.dashboard', compact(
                'courseCount', 'classesPerWeek', 'teacherCount', 'totalCredits',
                'enrolledCourses', 'todaySchedule', 'weeklySchedule',
                'availableSections', 'department', 'semester', 'feePaid', 'feeRecord'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }
}
