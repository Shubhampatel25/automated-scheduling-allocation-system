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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $timetables */
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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $conflicts */
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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $logs */
        $logs = $query->latest('created_at')->paginate(25)->withQueryString();
        $entityTypes = ActivityLog::select('entity_type')->distinct()->whereNotNull('entity_type')->pluck('entity_type');

        return view('admin.activity.index', compact('logs', 'entityTypes'));
    }

    public function student()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $enrolledSectionIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->pluck('course_section_id')
                : collect();

            $courseCount  = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)->where('status', 'enrolled')->count()
                : 0;
            $totalCredits = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with('courseSection.course')
                    ->get()->sum(fn($r) => $r->courseSection->course->credits ?? 0)
                : 0;
            $teacherCount = $enrolledSectionIds->isNotEmpty()
                ? CourseAssignment::whereIn('course_section_id', $enrolledSectionIds)->distinct()->count('teacher_id')
                : 0;
            $classesPerWeek = $enrolledSectionIds->isNotEmpty()
                ? TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)
                    ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
                    ->count()
                : 0;

            $department = $studentRecord && $studentRecord->department
                ? $studentRecord->department->name
                : 'N/A';
            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            $failedCoursesCount = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)->where('result', 'fail')->count()
                : 0;

            $unpaidRetakeFeesCount = $studentId
                ? FeePayment::where('student_id', $studentId)
                    ->where('type', 'supplemental')
                    ->where('status', '!=', 'paid')
                    ->count()
                : 0;

            return view('student.dashboard', compact(
                'courseCount', 'classesPerWeek', 'teacherCount', 'totalCredits',
                'department', 'semester', 'feeRecord', 'studentRecord',
                'failedCoursesCount', 'unpaidRetakeFeesCount'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load dashboard data. Please try again.');
        }
    }

    // ----------------------------------------------------------------
    // Shared helper — loads the student base data needed across pages
    // ----------------------------------------------------------------
    private function getStudentBase()
    {
        $user          = Auth::user();
        $studentRecord = Student::with('department')->where('user_id', $user->id)->first();
        $studentId     = $studentRecord ? $studentRecord->id : null;

        $enrolledSectionIds = $studentId
            ? StudentCourseRegistration::where('student_id', $studentId)
                ->where('status', 'enrolled')
                ->pluck('course_section_id')
            : collect();

        $mapReg = function ($reg) {
            $section = $reg->courseSection;
            $course  = $section ? clone $section->course : null;
            if ($course) {
                $course->registrationId     = $reg->id;
                $course->registrationStatus = $reg->status;
                $course->registrationResult = $reg->result;
                $course->sectionInfo        = $section;
                $assignment = $section->assignments->first();
                $course->teacherName = $assignment && $assignment->teacher ? $assignment->teacher->name : 'TBA';
            }
            return $course;
        };

        $currentYear = now()->year;
        $feeRecord   = null;
        $feePaid     = false;
        if ($studentRecord) {
            $feeRecord = FeePayment::where('student_id', $studentRecord->id)
                ->where('type', 'regular')
                ->where('semester', $studentRecord->semester)
                ->where('year', $currentYear)
                ->first();

            // Auto-correct: if paid_amount covers the total but status was never updated
            if ($feeRecord && $feeRecord->status !== 'paid') {
                $pa = (float) ($feeRecord->paid_amount ?? 0);
                $ta = (float) $feeRecord->amount;
                if ($ta > 0 && $pa >= $ta) {
                    DB::table('fee_payments')->where('id', $feeRecord->id)->update([
                        'status'  => 'paid',
                        'paid_at' => DB::raw('NOW()'),
                    ]);
                    $feeRecord->status = 'paid';
                }
            }

            $feePaid = $feeRecord && $feeRecord->status === 'paid';
        }

        return compact('studentRecord', 'studentId', 'enrolledSectionIds', 'mapReg', 'feeRecord', 'feePaid', 'currentYear');
    }

    public function studentRegisterCourses()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester   = $studentRecord ? $studentRecord->semester : 'N/A';
            $department = $studentRecord && $studentRecord->department ? $studentRecord->department->name : 'N/A';

            // ── Auto-advance semester ──────────────────────────────────────────
            // Two checks (either is sufficient):
            //   PRIMARY   – semester_1_result = 'Pass' while still on semester 1
            //               (uses the explicit result field, works immediately)
            //   SECONDARY – all current-semester course registrations are 'completed'
            //               AND ≥ 50% are 'pass' (general multi-semester support)
            if ($studentRecord && is_numeric($semester) && $studentId) {
                $currentSem    = (int) $semester;
                $shouldAdvance = false;

                // PRIMARY: semester_1_result field (reliable, already seeded)
                if ($currentSem === 1 && ($studentRecord->semester_1_result ?? '') === 'Pass') {
                    $shouldAdvance = true;
                }

                // SECONDARY: completion-based check (future semesters)
                if (!$shouldAdvance) {
                    $semRegs = StudentCourseRegistration::where('student_id', $studentId)
                        ->whereHas('courseSection.course', fn($q) => $q->where('semester', $currentSem))
                        ->get();

                    $stillEnrolled = $semRegs->where('status', 'enrolled')->count();
                    $completed     = $semRegs->where('status', 'completed');
                    $totalDone     = $completed->count();
                    $passCount     = $completed->where('result', 'pass')->count();

                    if ($stillEnrolled === 0 && $totalDone > 0 && ($passCount / $totalDone) >= 0.5) {
                        $shouldAdvance = true;
                    }
                }

                if ($shouldAdvance && (int) $studentRecord->semester === $currentSem) {
                    // For the secondary (completion-based) check only, verify next-semester
                    // courses actually exist before advancing.  The primary check (semester_1_result)
                    // always advances — the registration page will show a "no courses yet" state
                    // gracefully if needed.
                    $primaryFired = ($currentSem === 1 && ($studentRecord->semester_1_result ?? '') === 'Pass');
                    $nextSemExists = $primaryFired || Course::where('department_id', $studentRecord->department_id)
                        ->where('semester', $currentSem + 1)
                        ->where('status', 'active')
                        ->exists();

                    if ($nextSemExists) {
                        DB::table('students')
                            ->where('id', $studentRecord->id)
                            ->update(['semester' => $currentSem + 1]);
                        $studentRecord->semester = $currentSem + 1;
                        $semester = $currentSem + 1;

                        // Reload fee record for the new semester
                        $feeRecord = FeePayment::where('student_id', $studentRecord->id)
                            ->where('type', 'regular')
                            ->where('semester', $semester)
                            ->where('year', $currentYear)
                            ->first();
                        // Auto-correct paid status if paid_amount already covers the total
                        if ($feeRecord && $feeRecord->status !== 'paid') {
                            $pa = (float) ($feeRecord->paid_amount ?? 0);
                            $ta = (float) $feeRecord->amount;
                            if ($ta > 0 && $pa >= $ta) {
                                DB::table('fee_payments')->where('id', $feeRecord->id)
                                    ->update(['status' => 'paid', 'paid_at' => DB::raw('NOW()')]);
                                $feeRecord->status = 'paid';
                            }
                        }
                        $feePaid = $feeRecord && $feeRecord->status === 'paid';

                        session()->flash('semester_advanced', $semester);
                    }
                }
            }
            // ─────────────────────────────────────────────────────────────────

            // Term ordering: Winter=1, Summer=2, Fall=3
            $termPriority = ['Winter' => 1, 'Summer' => 2, 'Fall' => 3];

            // Compute the calendar-based upcoming term
            $month = now()->month;
            if ($month <= 4) {
                $calTerm = 'Summer'; $calYear = now()->year;
            } elseif ($month <= 8) {
                $calTerm = 'Fall';   $calYear = now()->year;
            } else {
                $calTerm = 'Winter'; $calYear = now()->year + 1;
            }

            $upcomingTerm = $calTerm;
            $upcomingYear = $calYear;

            $availableSections = collect();
            if ($studentRecord && $feePaid) {
                // Find the nearest (term, year) that actually has sections for this student
                $calTermPriority = $termPriority[$calTerm] ?? 1;

                $distinctTerms = CourseSection::whereHas('course', function ($q) use ($studentRecord) {
                        $q->where('department_id', $studentRecord->department_id)
                          ->where('status', 'active')
                          ->where(function ($q2) use ($studentRecord) {
                              $q2->where('semester', $studentRecord->semester)
                                 ->orWhereNull('semester');
                          });
                    })
                    ->selectRaw('term, year')
                    ->distinct()
                    ->get()
                    ->sortBy(fn($r) => $r->year * 10 + ($termPriority[$r->term] ?? 0));

                // Prefer nearest future term; fall back to most recent available
                $nearest = $distinctTerms->first(function ($r) use ($calYear, $calTermPriority, $termPriority) {
                    $rp = $termPriority[$r->term] ?? 0;
                    return $r->year > $calYear || ($r->year == $calYear && $rp >= $calTermPriority);
                }) ?? $distinctTerms->last();

                if ($nearest) {
                    $upcomingTerm = $nearest->term;
                    $upcomingYear = $nearest->year;
                }

                // ── Separate completed-course exclusion by course ID (not section ID) ──
                // A student who failed CS111 Sec1 must NOT see CS111 Sec2 as a "regular" course.
                // We exclude by COURSE ID for all completed registrations (pass or fail).
                // Currently enrolled are excluded by SECTION ID only (so they can retake in a new section).
                $allCompletedCourseIds = StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course?->id)
                    ->filter()->unique()->values()->toArray();

                $availableSections = CourseSection::whereNotIn('id', $enrolledSectionIds->toArray())
                    ->where('term', $upcomingTerm)
                    ->where('year', $upcomingYear)
                    ->whereColumn('enrolled_students', '<', 'max_students')
                    ->whereHas('course', function ($q) use ($studentRecord, $allCompletedCourseIds) {
                        $q->where('department_id', $studentRecord->department_id)
                          ->where('status', 'active')
                          ->where(function ($q2) use ($studentRecord) {
                              $q2->where('semester', $studentRecord->semester)
                                 ->orWhereNull('semester');
                          })
                          ->whereNotIn('id', $allCompletedCourseIds); // exclude all completed (pass+fail) by course
                    })
                    ->with(['course.department', 'assignments.teacher'])
                    ->get();
            }

            // ── Passed course codes (for prerequisite checking) ──
            $passedCourseCodes = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->where('result', 'pass')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => optional($r->courseSection?->course)->code)
                    ->filter()->unique()->values()->toArray()
                : [];

            // ── Net-failed course IDs: failed AND not yet cleared by a retake pass ──
            // These drive the Retake section — independent of supplemental fee status.
            $passedCourseIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')->where('result', 'pass')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course?->id)
                    ->filter()->unique()->values()->toArray()
                : [];

            $failedCourseIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')->where('result', 'fail')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course?->id)
                    ->filter()->unique()->values()->toArray()
                : [];

            // Net failed = failed but not yet passed via any retake
            $netFailedCourseIds = array_values(array_diff($failedCourseIds, $passedCourseIds));

            // Retake sections: all net-failed courses in the upcoming term (regardless of supplemental fee)
            $retakeSections = collect();
            if ($studentId && !empty($netFailedCourseIds)) {
                $retakeSections = CourseSection::whereIn('course_id', $netFailedCourseIds)
                    ->whereNotIn('id', $enrolledSectionIds->toArray())
                    ->where('term', $upcomingTerm)
                    ->where('year', $upcomingYear)
                    ->whereColumn('enrolled_students', '<', 'max_students')
                    ->whereHas('course', fn($q) => $q->where('status', 'active'))
                    ->with(['course.department', 'assignments.teacher'])
                    ->get();
            }

            // Which retake courses have a paid supplemental fee (controls "Re-Enroll" vs "Fee Required" button)
            $supplementalPaidCourseIds = $studentId
                ? FeePayment::where('student_id', $studentId)
                    ->where('type', 'supplemental')
                    ->where('status', 'paid')
                    ->pluck('course_id')->toArray()
                : [];

            // ── Per-section status: prerequisite + schedule conflict (per course, not per semester) ──
            $enrolledSlots = $enrolledSectionIds->isNotEmpty()
                ? TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)->get()
                : collect();

            $enrolledSectionsMap = $enrolledSectionIds->isNotEmpty()
                ? CourseSection::whereIn('id', $enrolledSectionIds)->with('course')->get()->keyBy('id')
                : collect();

            $sectionStatuses = [];
            foreach ($availableSections as $section) {
                $course = $section->course;

                // 1. Prerequisite check — per course, not per semester
                //    Each course is evaluated independently: a student can register for any
                //    course whose own prerequisite is met, even if they failed unrelated courses.
                $prereqBlocked  = false;
                $prereqAdvisory = null;

                if ($course->prerequisite_course_code &&
                    !in_array($course->prerequisite_course_code, $passedCourseCodes)) {
                    if ($course->prerequisite_mandatory) {
                        $prereqBlocked = true;
                    } else {
                        $prereqAdvisory = 'Recommended prerequisite "' . $course->prerequisite_course_code . '" not yet passed';
                    }
                }

                if ($prereqBlocked) {
                    $sectionStatuses[$section->id] = [
                        'blocked'  => true,
                        'advisory' => null,
                        'reason'   => 'Prerequisite not passed: must pass "' . $course->prerequisite_course_code . '" first',
                    ];
                    continue; // hard-blocked, skip schedule check
                }

                // 2. Schedule conflict check (runs even when advisory prereq warning exists)
                $conflictReason = null;
                if ($enrolledSlots->isNotEmpty()) {
                    $sectionSlots = TimetableSlot::where('course_section_id', $section->id)->get();
                    foreach ($sectionSlots as $slot) {
                        $conflictSlot = $enrolledSlots
                            ->where('day_of_week', $slot->day_of_week)
                            ->first(fn($es) => $es->start_time < $slot->end_time && $es->end_time > $slot->start_time);
                        if ($conflictSlot) {
                            $conflictCourse = $enrolledSectionsMap->get($conflictSlot->course_section_id)?->course;
                            $conflictReason = 'Schedule conflict with "' . ($conflictCourse?->name ?? 'another course') . '" on ' . $slot->day_of_week;
                            break;
                        }
                    }
                }

                if ($conflictReason) {
                    $sectionStatuses[$section->id] = [
                        'blocked'  => true,
                        'advisory' => $prereqAdvisory,
                        'reason'   => $conflictReason,
                    ];
                } else {
                    $sectionStatuses[$section->id] = [
                        'blocked'  => false,
                        'advisory' => $prereqAdvisory,
                        'reason'   => null,
                    ];
                }
            }

            return view('student.register-courses', compact(
                'availableSections', 'feePaid', 'feeRecord', 'semester', 'department',
                'upcomingTerm', 'upcomingYear', 'retakeSections', 'sectionStatuses',
                'passedCourseCodes', 'supplementalPaidCourseIds'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentMyCourses()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            $enrolledCourses = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->get()->map($mapReg)->filter()->values()
                : collect();

            // Detect which enrolled courses are retakes (student has a prior fail for the same course)
            $failedCourseIds = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->where('result', 'fail')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course?->id)
                    ->filter()->unique()->values()->toArray()
                : [];

            $enrolledCourses = $enrolledCourses->map(function ($course) use ($failedCourseIds) {
                $course->isRetake = in_array($course->id, $failedCourseIds);
                return $course;
            });

            // Exclude from history any section where the student has re-enrolled (retake in progress)
            $reEnrolledSectionIds = StudentCourseRegistration::where('student_id', $studentId)
                ->where('status', 'enrolled')
                ->pluck('course_section_id');

            $completedHistory = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->whereNotIn('course_section_id', $reEnrolledSectionIds)
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->get()->map($mapReg)->filter()
                    ->groupBy(fn($c) => $c->semester ?? 0)->sortKeys()
                : collect();

            return view('student.my-courses', compact('enrolledCourses', 'completedHistory', 'semester'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentTimetable()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            $weeklySchedule = $enrolledSectionIds->isNotEmpty()
                ? TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)
                    ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
                    ->with(['courseSection.course', 'teacher', 'room'])
                    ->get()
                : collect();

            return view('student.timetable', compact('weeklySchedule', 'semester'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentToday()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';
            $today    = now()->format('l');

            $allSlots = $enrolledSectionIds->isNotEmpty()
                ? TimetableSlot::whereIn('course_section_id', $enrolledSectionIds)
                    ->whereHas('timetable', fn($q) => $q->where('status', 'active'))
                    ->with(['courseSection.course', 'teacher', 'room'])
                    ->get()
                : collect();

            $todaySchedule = $allSlots->where('day_of_week', $today)->sortBy('start_time')->values();

            return view('student.today', compact('todaySchedule', 'today', 'semester'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentFeePayment()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            $enrolledCourses = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->get()->map($mapReg)->filter()->values()
                : collect();

            // Auto-sync fee amount — prefer department registration fee if set
            $deptFee = $studentRecord && $studentRecord->department
                ? $studentRecord->department->registration_fee
                : null;

            if ($studentRecord) {
                $calculatedTotal = $deptFee !== null
                    ? (float) $deptFee
                    : $enrolledCourses->sum(fn($c) => (float) ($c->fee ?? 0));

                if (!$feeRecord && $calculatedTotal > 0) {
                    // Auto-create pending regular fee record if none exists and amount is known
                    $newId = DB::table('fee_payments')->insertGetId([
                        'student_id'  => $studentId,
                        'type'        => 'regular',
                        'course_id'   => null,
                        'semester'    => $studentRecord->semester,
                        'year'        => $currentYear,
                        'amount'      => $calculatedTotal,
                        'paid_amount' => 0,
                        'status'      => 'pending',
                        'paid_at'     => null,
                        'created_at'  => DB::raw('NOW()'),
                    ]);
                    $feeRecord = FeePayment::find($newId);
                    $feePaid   = false;
                } elseif ($feeRecord && $feeRecord->status !== 'paid' && (float) $feeRecord->amount !== $calculatedTotal) {
                    DB::table('fee_payments')->where('id', $feeRecord->id)->update(['amount' => $calculatedTotal]);
                    $feeRecord->amount = $calculatedTotal;
                }

                // Auto-correct status: if paid_amount already covers the total, mark as paid.
                // This fixes cases where Stripe processed the payment but the status was never updated.
                if ($feeRecord && $feeRecord->status !== 'paid') {
                    $paidAmt  = (float) ($feeRecord->paid_amount ?? 0);
                    $totalAmt = (float) $feeRecord->amount;
                    if ($totalAmt > 0 && $paidAmt >= $totalAmt) {
                        DB::table('fee_payments')->where('id', $feeRecord->id)->update([
                            'status'  => 'paid',
                            'paid_at' => DB::raw('NOW()'),
                        ]);
                        $feeRecord->status = 'paid';
                        $feePaid = true;
                    }
                }

                // Auto-generate supplemental fee records for each failed course (if not yet created)
                $failedCourseIds = StudentCourseRegistration::where('student_id', $studentId)
                    ->where('result', 'fail')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course)
                    ->filter()
                    ->unique('id');

                foreach ($failedCourseIds as $failedCourse) {
                    $alreadyExists = FeePayment::where('student_id', $studentId)
                        ->where('type', 'supplemental')
                        ->where('course_id', $failedCourse->id)
                        ->exists();

                    if (!$alreadyExists) {
                        FeePayment::create([
                            'student_id' => $studentId,
                            'type'       => 'supplemental',
                            'course_id'  => $failedCourse->id,
                            'semester'   => $failedCourse->semester,
                            'year'       => $currentYear,
                            'amount'     => $failedCourse->fee > 0 ? (float) $failedCourse->fee : 300.00,
                            'paid_amount'=> 0,
                            'status'     => 'pending',
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            // Load all supplemental fee records (pending + paid) for display
            $supplementalFees = $studentId
                ? FeePayment::where('student_id', $studentId)
                    ->where('type', 'supplemental')
                    ->with('course')
                    ->orderBy('status')
                    ->get()
                : collect();

            return view('student.fee-payment', compact(
                'feeRecord', 'feePaid', 'enrolledCourses', 'semester', 'deptFee', 'supplementalFees', 'studentRecord'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentProfile()
    {
        try {
            $base = $this->getStudentBase();
            extract($base);

            $semester     = $studentRecord ? $studentRecord->semester : 'N/A';
            $department   = $studentRecord && $studentRecord->department ? $studentRecord->department->name : 'N/A';
            $totalCredits = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with('courseSection.course')
                    ->get()->sum(fn($r) => $r->courseSection->course->credits ?? 0)
                : 0;

            // All completed registrations with results (for the results history section)
            $completedResults = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->with(['courseSection.course.department'])
                    ->orderBy('registered_at')
                    ->get()
                : collect();

            // Backlog detection: a fail is a "cleared backlog" if the same course was later passed
            $resultsByCourse         = $completedResults->groupBy(fn($r) => $r->courseSection?->course?->id ?? 0);
            $backlogClearedCourseIds = [];
            $latestRetakePassIds     = [];

            foreach ($resultsByCourse as $courseId => $regs) {
                if (!$courseId) continue;
                $fails  = $regs->where('result', 'fail');
                $passes = $regs->where('result', 'pass')->sortByDesc(fn($r) => $r->registered_at ?? '');
                if ($fails->isNotEmpty() && $passes->isNotEmpty()) {
                    $backlogClearedCourseIds[] = $courseId;
                    $latestRetakePassIds[]     = $passes->first()->id;
                }
            }

            $completedResults = $completedResults->map(function ($reg) use ($backlogClearedCourseIds, $latestRetakePassIds) {
                $courseId              = $reg->courseSection?->course?->id;
                $reg->isBacklogCleared = $reg->result === 'fail' && in_array($courseId, $backlogClearedCourseIds);
                $reg->isRetakePass     = in_array($reg->id, $latestRetakePassIds);
                return $reg;
            })->values();

            // Build a set of course codes the student has passed
            $passedCourseCodes = $completedResults
                ->where('result', 'pass')
                ->map(fn($r) => optional($r->courseSection->course)->code)
                ->filter()
                ->values()
                ->toArray();

            // Find next-semester courses with unmet prerequisites
            $prerequisiteAlerts = collect();
            if ($studentRecord && is_numeric($semester)) {
                $nextSemester = (int) $semester + 1;
                $nextSemesterCourses = Course::where('department_id', $studentRecord->department_id)
                    ->where('semester', $nextSemester)
                    ->whereNotNull('prerequisite_course_code')
                    ->where('prerequisite_course_code', '!=', '')
                    ->where('status', 'active')
                    ->get();

                foreach ($nextSemesterCourses as $course) {
                    if (!in_array($course->prerequisite_course_code, $passedCourseCodes)) {
                        $prerequisiteAlerts->push([
                            'course'      => $course->name,
                            'course_code' => $course->code,
                            'requires'    => $course->prerequisite_course_code,
                        ]);
                    }
                }
            }

            return view('student.profile', compact(
                'semester', 'department', 'totalCredits', 'feeRecord', 'feePaid',
                'completedResults', 'prerequisiteAlerts'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }
}
