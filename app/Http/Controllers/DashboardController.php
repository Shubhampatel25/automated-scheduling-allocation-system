<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Conflict;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Hod;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentCourseRegistration;
use App\Models\Teacher;
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

            $recentTeachers = Teacher::with('department')->latest('created_at')->take(5)->get();
            $recentCourses = Course::latest('created_at')->take(5)->get();
            $recentActivities = ActivityLog::latest('created_at')->take(10)->get();

            return view('admin.dashboard', compact(
                'departmentCount', 'teacherCount', 'courseCount', 'roomCount',
                'studentCount', 'conflictCount', 'recentTeachers', 'recentCourses',
                'recentActivities'
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

            return view('hod.dashboard', compact(
                'facultyCount', 'courseCount', 'assignmentCount', 'conflictCount',
                'facultyMembers', 'departmentCourses', 'conflicts', 'timetableSlots'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    public function professor()
    {
        try {
            $user = Auth::user();
            $teacher = Teacher::where('user_id', $user->id)->first();
            $teacherId = $teacher ? $teacher->id : null;

            $assignedCourses = $teacherId
                ? Course::whereHas('sections.assignments', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->withCount(['sections as students_count' => function ($q) {
                    $q->select(DB::raw('COALESCE(SUM(enrolled_students), 0)'));
                }])->get()
                : collect();

            $courseCount = $assignedCourses->count();

            $allSlots = $teacherId
                ? TimetableSlot::where('teacher_id', $teacherId)
                    ->whereHas('timetable', function ($q) { $q->where('status', 'active'); })
                    ->with(['courseSection.course', 'room'])
                    ->get()
                : collect();

            $classesPerWeek = $allSlots->count();
            $hoursPerWeek = $allSlots->sum(function ($slot) {
                $start = strtotime($slot->start_time);
                $end = strtotime($slot->end_time);
                return ($end - $start) / 3600;
            });

            $studentCount = $teacherId
                ? CourseSection::whereHas('assignments', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->sum('enrolled_students')
                : 0;

            $today = now()->format('l');
            $todaySchedule = $allSlots->where('day_of_week', $today)->sortBy('start_time')->values();
            $weeklySchedule = $allSlots;

            return view('professor.dashboard', compact(
                'courseCount', 'classesPerWeek', 'studentCount', 'hoursPerWeek',
                'assignedCourses', 'todaySchedule', 'weeklySchedule'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    public function student()
    {
        try {
            $user = Auth::user();
            $studentRecord = Student::with('department')->where('user_id', $user->id)->first();
            $studentId = $studentRecord ? $studentRecord->id : null;

            $enrolledSectionIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->pluck('course_section_id')
                : collect();

            $enrolledCourses = $enrolledSectionIds->isNotEmpty()
                ? Course::whereHas('sections', function ($q) use ($enrolledSectionIds) {
                    $q->whereIn('id', $enrolledSectionIds);
                })->with(['sections.assignments.teacher'])->get()
                : collect();

            $courseCount = $enrolledCourses->count();
            $totalCredits = $enrolledCourses->sum('credits');

            // Fixed: use DB::raw for proper distinct count
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

            $today = now()->format('l');
            $todaySchedule = $allSlots->where('day_of_week', $today)->sortBy('start_time')->values();
            $weeklySchedule = $allSlots;

            $department = $studentRecord && $studentRecord->department
                ? $studentRecord->department->name
                : 'N/A';
            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            return view('student.dashboard', compact(
                'courseCount', 'classesPerWeek', 'teacherCount', 'totalCredits',
                'enrolledCourses', 'todaySchedule', 'weeklySchedule',
                'department', 'semester'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }
}
