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
use App\Models\StudentSemesterHistory;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\RegistrationEligibilityService;
use App\Services\TimetableConflictService;
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
            $recentCourses     = Course::with(['sections' => fn($q) => $q->orderByDesc('year')])->latest('created_at')->take(5)->get();
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
                })->with(['sections' => function ($q) use ($teacherId) {
                    $q->whereHas('assignments', fn($aq) => $aq->where('teacher_id', $teacherId))->orderByDesc('year');
                }])->withCount(['sections as students_count' => function ($q) {
                    $q->select(DB::raw('COALESCE(SUM(enrolled_students), 0)'));
                }])->get()
                : collect();

            $courseCount = $assignedCourses->count();

            // All timetable slots for this teacher (active timetable only)
            $allSlots = $teacherId
                ? TimetableSlot::where('teacher_id', $teacherId)
                    ->whereHas('timetable', function ($q) { $q->where('status', 'active'); })
                    ->with(['courseSection.course', 'room', 'timetable'])
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

    public function scheduleSlots(Timetable $timetable)
    {
        $slots = TimetableSlot::where('timetable_id', $timetable->id)
            ->with(['courseSection.course', 'teacher', 'room'])
            ->get()
            ->map(fn($s) => [
                'day'       => $s->day_of_week,
                'start'     => substr($s->start_time, 0, 5),
                'end'       => substr($s->end_time,   0, 5),
                'component' => $s->component,
                'course'    => $s->courseSection?->course?->name ?? 'N/A',
                'code'      => $s->courseSection?->course?->code ?? '',
                'teacher'   => $s->teacher?->name ?? '—',
                'room'      => $s->room?->room_number ?? '—',
            ]);

        return response()->json([
            'timetable' => [
                'department' => $timetable->department?->name ?? 'N/A',
                'term'       => $timetable->term,
                'year'       => $timetable->year,
                'semester'   => $timetable->semester,
                'status'     => $timetable->status,
            ],
            'slots' => $slots,
        ]);
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

    public function scanConflicts(TimetableConflictService $conflictService)
    {
        $activeTimetables = Timetable::where('status', 'active')->get();

        $total = 0;
        foreach ($activeTimetables as $timetable) {
            // Within-timetable scan (safety net)
            $total += $conflictService->detectAndRecordConflicts($timetable);
            // Cross-timetable scan (teacher/room shared across semesters)
            $total += $conflictService->detectCrossTimetableConflicts($timetable);
            $timetable->update(['conflicts_count' => $timetable->conflicts()->count()]);
        }

        $msg = $total > 0
            ? "{$total} new conflict(s) detected across {$activeTimetables->count()} active timetable(s)."
            : "Scan complete — no new conflicts found.";

        return redirect()->route('admin.conflicts')->with('success', $msg);
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
            $course  = ($section && $section->course) ? clone $section->course : null;
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
            $svc  = new RegistrationEligibilityService();
            $base = $this->getStudentBase();
            extract($base);

            $semester   = $studentRecord ? $studentRecord->semester : 'N/A';
            $department = $studentRecord && $studentRecord->department ? $studentRecord->department->name : 'N/A';

            // ── Sequential semester guard ─────────────────────────────────────
            // Detect and correct illegal jumps (e.g. sem 3 → sem 6).
            // Uses student_semester_history first, then completed registrations.
            // No schema changes — only updates students.semester when a jump is found.
            if ($studentRecord && $studentId) {
                $semAuth = $svc->getAuthoritativeSemester($studentId, $studentRecord);
                if ($semAuth['corrected']) {
                    DB::table('students')
                        ->where('id', $studentRecord->id)
                        ->update(['semester' => $semAuth['semester']]);
                    $studentRecord->semester = $semAuth['semester'];
                    $semester                = $semAuth['semester'];

                    ActivityLog::create([
                        'user_id'     => Auth::id(),
                        'action'      => 'semester_corrected',
                        'entity_type' => 'student',
                        'entity_id'   => $studentRecord->id,
                        'details'     => $semAuth['reason'],
                        'created_at'  => now(),
                    ]);
                }
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Auto-advance semester ──────────────────────────────────────────
            // PRIMARY  : semester_1_result = 'Pass' while still on semester 1
            // SECONDARY: all current-semester courses completed AND ≥ 50 % passed
            if ($studentRecord && is_numeric($semester) && $studentId) {
                $currentSem    = (int) $semester;
                $shouldAdvance = false;

                if ($currentSem === 1 && ($studentRecord->semester_1_result ?? '') === 'Pass') {
                    $shouldAdvance = true;
                }

                if (!$shouldAdvance) {
                    $semRegs       = StudentCourseRegistration::where('student_id', $studentId)
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
                    $primaryFired  = ($currentSem === 1 && ($studentRecord->semester_1_result ?? '') === 'Pass');
                    $nextSemExists = $primaryFired || Course::where('department_id', $studentRecord->department_id)
                        ->where('semester', $currentSem + 1)->where('status', 'active')->exists();

                    if ($nextSemExists) {
                        // Wrap advancement in a transaction so semester update and
                        // fee auto-correction are atomic — no partial state on failure.
                        DB::transaction(function () use ($studentRecord, $currentSem, $currentYear, &$semester, &$feeRecord, &$feePaid) {
                            DB::table('students')->where('id', $studentRecord->id)
                                ->where('semester', $currentSem)
                                ->update(['semester' => $currentSem + 1]);
                            $studentRecord->semester = $currentSem + 1;
                            $semester                = $currentSem + 1;

                            $feeRecord = FeePayment::where('student_id', $studentRecord->id)
                                ->where('type', 'regular')->where('semester', $semester)
                                ->where('year', $currentYear)->first();

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

                            // ── Record completed semester in history ──────────
                            $completedRegs = StudentCourseRegistration::where('student_id', $studentRecord->id)
                                ->whereHas('courseSection.course', fn($q) => $q->where('semester', $currentSem))
                                ->where('status', 'completed')->get();
                            $total  = $completedRegs->count();
                            $passed = $completedRegs->where('result', 'pass')->count();
                            $semResult = ($total > 0 && ($passed / $total) >= 0.5) ? 'pass' : 'fail';

                            StudentSemesterHistory::updateOrCreate(
                                ['student_id' => $studentRecord->id, 'semester' => $currentSem, 'year' => $currentYear],
                                ['result' => $semResult, 'completed_at' => now()]
                            );

                            // Create an in_progress record for the new semester
                            StudentSemesterHistory::firstOrCreate(
                                ['student_id' => $studentRecord->id, 'semester' => $currentSem + 1, 'year' => $currentYear],
                                ['result' => 'in_progress', 'started_at' => now()]
                            );
                            // ─────────────────────────────────────────────────
                        });

                        session()->flash('semester_advanced', $semester);
                    }
                }
            }
            // ─────────────────────────────────────────────────────────────────

            // ── Upcoming term resolution ──────────────────────────────────────
            $termPriority = ['Winter' => 1, 'Summer' => 2, 'Fall' => 3];
            $month        = now()->month;
            if ($month <= 4) {
                $calTerm = 'Summer'; $calYear = now()->year;
            } elseif ($month <= 8) {
                $calTerm = 'Fall';   $calYear = now()->year;
            } else {
                $calTerm = 'Winter'; $calYear = now()->year + 1;
            }
            $upcomingTerm = $calTerm;
            $upcomingYear = $calYear;

            // ── HYBRID: detect whether real-world registration history exists ──
            // PATH A (NEW logic)  : student has at least one record in
            //                       student_course_registrations → use full
            //                       passed/failed/prerequisite/fee checks.
            // PATH B (OLD fallback): no history yet (fresh/new student) →
            //                       degrade gracefully to semester-only display;
            //                       service calls still work (return [] safely)
            //                       but we skip the DB round-trips entirely.
            $hasRegistrationHistory = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)->exists()
                : false;

            // ── Academic record (via service — PATH A only) ───────────────────
            $passedCourseCodes         = ($studentId && $hasRegistrationHistory) ? $svc->getPassedCourseCodes($studentId)         : [];
            $netFailedCourseIds        = ($studentId && $hasRegistrationHistory) ? $svc->getNetFailedCourseIds($studentId)        : [];
            $supplementalPaidCourseIds = ($studentId && $hasRegistrationHistory) ? $svc->getSupplementalPaidCourseIds($studentId) : [];

            // ── Enrolled-slot context (shared by both classifiers) ────────────
            $enrolledSlots       = $svc->getEnrolledSlots($enrolledSectionIds);
            $enrolledSectionsMap = $svc->getEnrolledSectionsMap($enrolledSectionIds);

            // ── Candidate regular sections ────────────────────────────────────
            // Exclude all completed course IDs (pass or fail) — handled per-course not per-section
            // so a failed student cannot see the same course as a "new" regular course.
            $allCandidateSections = collect();
            if ($studentRecord) {
                $allCompletedCourseIds = StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => $r->courseSection?->course?->id)
                    ->filter()->unique()->values()->toArray();

                // Resolve nearest actual term that has data
                $calTermPriority = $termPriority[$calTerm] ?? 1;
                $distinctTerms   = CourseSection::whereHas('course', function ($q) use ($studentRecord) {
                        $q->where('department_id', $studentRecord->department_id)
                          ->where('status', 'active')
                          ->where(fn($q2) => $q2->where('semester', $studentRecord->semester)->orWhereNull('semester'));
                    })
                    ->selectRaw('term, year')->distinct()->get()
                    ->sortBy(fn($r) => $r->year * 10 + ($termPriority[$r->term] ?? 0));

                $nearest = $distinctTerms->first(function ($r) use ($calYear, $calTermPriority, $termPriority) {
                    $rp = $termPriority[$r->term] ?? 0;
                    return $r->year > $calYear || ($r->year == $calYear && $rp >= $calTermPriority);
                }) ?? $distinctTerms->last();

                if ($nearest) {
                    $upcomingTerm = $nearest->term;
                    $upcomingYear = $nearest->year;
                }

                if ($feePaid) {
                    // Also exclude course IDs where student is already enrolled in any section
                    $alreadyEnrolledCourseIds = $enrolledSectionIds->isNotEmpty()
                        ? CourseSection::whereIn('id', $enrolledSectionIds)->pluck('course_id')->unique()->toArray()
                        : [];

                    $allCandidateSections = CourseSection::whereNotIn('id', $enrolledSectionIds->toArray())
                        ->where('term', $upcomingTerm)->where('year', $upcomingYear)
                        ->whereHas('course', function ($q) use ($studentRecord, $allCompletedCourseIds, $alreadyEnrolledCourseIds) {
                            $q->where('department_id', $studentRecord->department_id)
                              ->where('status', 'active')
                              ->where(fn($q2) => $q2->where('semester', $studentRecord->semester)->orWhereNull('semester'))
                              ->whereNotIn('id', $allCompletedCourseIds)
                              ->whereNotIn('id', $alreadyEnrolledCourseIds);
                        })
                        ->withCount(['studentCourseRegistrations as actual_enrolled' => fn($q) => $q->where('status', 'enrolled')])
                        ->with(['course.department', 'assignments.teacher'])
                        ->get();
                }
            }

            // ── Classify regular sections → regular + blocked (via service) ───
            $classified = $svc->classifyRegularSections($allCandidateSections, [
                'passed_codes'         => $passedCourseCodes,
                'enrolled_slots'       => $enrolledSlots,
                'enrolled_sections_map' => $enrolledSectionsMap,
            ]);
            $regularSections = $classified['regularSections'];
            $blockedSections = $classified['blockedSections'];
            $sectionStatuses = $classified['sectionStatuses'];

            // ── Retake sections (PATH A only — skipped for new students) ─────
            // Only students with registration history can have net-failed courses.
            $retakeSections = collect();
            $retakeStatuses = [];
            if ($studentId && $hasRegistrationHistory && !empty($netFailedCourseIds)) {
                $retakeSections = CourseSection::whereIn('course_id', $netFailedCourseIds)
                    ->whereNotIn('id', $enrolledSectionIds->toArray())
                    ->where('term', $upcomingTerm)->where('year', $upcomingYear)
                    ->whereColumn('enrolled_students', '<', 'max_students')
                    ->whereHas('course', fn($q) => $q->where('status', 'active'))
                    ->with(['course.department', 'assignments.teacher'])
                    ->get();

                $retakeClassified = $svc->classifyRetakeSections($retakeSections, [
                    'supplemental_paid_ids' => $supplementalPaidCourseIds,
                    'enrolled_slots'        => $enrolledSlots,
                    'enrolled_sections_map' => $enrolledSectionsMap,
                ]);
                $retakeStatuses = $retakeClassified['retakeStatuses'];
            }

            // Build a course_id → teacher name map so the view can show the real
            // teacher even when the assignment lives on a different section.
            $allDisplaySections = $regularSections->merge($blockedSections)->merge($retakeSections);
            $allCourseIds = $allDisplaySections->map(fn($s) => $s->course?->id)->filter()->unique()->values()->toArray();
            $courseTeacherMap = [];
            if (!empty($allCourseIds)) {
                \App\Models\CourseAssignment::whereHas('courseSection',
                        fn($q) => $q->whereIn('course_id', $allCourseIds)
                    )
                    ->with(['teacher', 'courseSection'])
                    ->get()
                    ->each(function ($a) use (&$courseTeacherMap) {
                        $cid = $a->courseSection->course_id ?? null;
                        if ($cid && !isset($courseTeacherMap[$cid]) && $a->teacher) {
                            $courseTeacherMap[$cid] = $a->teacher->name;
                        }
                    });
            }

            return view('student.register-courses', compact(
                'regularSections', 'blockedSections', 'sectionStatuses',
                'feePaid', 'feeRecord', 'semester', 'department',
                'upcomingTerm', 'upcomingYear',
                'retakeSections', 'retakeStatuses', 'supplementalPaidCourseIds',
                'passedCourseCodes', 'courseTeacherMap'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentMyCourses()
    {
        try {
            $svc  = new RegistrationEligibilityService();
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            $enrolledCourses = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->get()->map($mapReg)->filter()->values()
                : collect();

            // Fix TBA: teacher may be assigned to a different section of the same course.
            // Pre-load one assignment per course_id across all sections, then fill gaps.
            $coursesNeedingTeacher = $enrolledCourses
                ->filter(fn($c) => ($c->teacherName ?? 'TBA') === 'TBA')
                ->pluck('id');

            if ($coursesNeedingTeacher->isNotEmpty()) {
                $fallbackAssignments = \App\Models\CourseAssignment::whereHas('courseSection',
                        fn($q) => $q->whereIn('course_id', $coursesNeedingTeacher)
                    )
                    ->with(['teacher', 'courseSection'])
                    ->get()
                    ->groupBy(fn($a) => $a->courseSection->course_id ?? 0);

                $enrolledCourses = $enrolledCourses->map(function ($course) use ($fallbackAssignments) {
                    if (($course->teacherName ?? 'TBA') === 'TBA') {
                        $assignment = $fallbackAssignments->get($course->id)?->first();
                        $course->teacherName = $assignment?->teacher?->name ?? 'TBA';
                    }
                    return $course;
                });
            }

            // Mark enrolled courses as retakes (student has a prior fail for the same course)
            $failedCourseIds = $studentId ? $svc->getFailedCourseIds($studentId) : [];
            $enrolledCourses = $enrolledCourses->map(function ($course) use ($failedCourseIds) {
                $course->isRetake = in_array($course->id, $failedCourseIds);
                return $course;
            });

            // Completed history — raw registration records annotated by service
            $completedRegs = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'completed')
                    ->with(['courseSection.course.department', 'courseSection.assignments.teacher'])
                    ->orderBy('registered_at')
                    ->get()
                : collect();

            // Annotate with isRetakePass / isBacklogCleared / isRetakeFail
            $annotatedRegs = $svc->annotateCompletedResults($completedRegs);

            // Group by course semester for display (semester 0 = unknown)
            $completedHistory = $annotatedRegs
                ->groupBy(fn($r) => $r->courseSection?->course?->semester ?? 0)
                ->sortKeys();

            // Summary counts for the header
            $historyStats = [
                'total'         => $annotatedRegs->count(),
                'passed'        => $annotatedRegs->where('result', 'pass')->where('isRetakePass', false)->count(),
                'retake_pass'   => $annotatedRegs->where('isRetakePass', true)->count(),
                'failed'        => $annotatedRegs->where('result', 'fail')->where('isBacklogCleared', false)->count(),
                'backlog_clear' => $annotatedRegs->where('isBacklogCleared', true)->count(),
            ];

            return view('student.my-courses', compact(
                'enrolledCourses', 'completedHistory', 'historyStats', 'semester'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load page.');
        }
    }

    public function studentTimetable()
    {
        try {
            $svc  = new RegistrationEligibilityService();
            $base = $this->getStudentBase();
            extract($base);

            $semester = $studentRecord ? $studentRecord->semester : 'N/A';

            // Fetch timetable slots for the student's enrolled courses.
            //
            // We match by COURSE ID (not section ID) because the section the student
            // enrolled in and the section the HOD generated the timetable for can
            // differ when courses were auto-created in a different term than the one
            // the timetable was generated for. Matching via course ensures the student
            // always sees their schedule regardless of term/section ID mismatch.
            $weeklySchedule = collect();
            $timetableIsDraft = false;

            if ($enrolledSectionIds->isNotEmpty() && $studentRecord) {
                $enrolledSections  = CourseSection::whereIn('id', $enrolledSectionIds)
                    ->get(['id', 'course_id', 'term', 'year']);
                $enrolledCourseIds = $enrolledSections->pluck('course_id')->toArray();
                $termYearPairs     = $enrolledSections
                    ->map(fn($s) => ['term' => $s->term, 'year' => $s->year])
                    ->unique(fn($p) => $p['term'] . '|' . $p['year'])
                    ->values();

                if (!empty($enrolledCourseIds)) {
                    $weeklySchedule = TimetableSlot::whereHas('courseSection',
                            fn($q) => $q->whereIn('course_id', $enrolledCourseIds)
                        )
                        ->whereHas('timetable', function ($q) use ($termYearPairs, $studentRecord) {
                            $q->whereIn('status', ['active', 'draft'])
                              ->where('department_id', $studentRecord->department_id)
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

                    $timetableIsDraft = $weeklySchedule->contains(
                        fn($s) => ($s->timetable->status ?? '') === 'draft'
                    );
                }
            }

            // Pass net-failed course IDs so the view can badge retake slots distinctly.
            $retakeCourseIds = $studentId ? $svc->getNetFailedCourseIds($studentId) : [];

            return view('student.timetable', compact('weeklySchedule', 'semester', 'retakeCourseIds', 'timetableIsDraft'));
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

            $allSlots = collect();
            if ($enrolledSectionIds->isNotEmpty() && $studentRecord) {
                $enrolledSections  = CourseSection::whereIn('id', $enrolledSectionIds)
                    ->get(['id', 'course_id', 'term', 'year']);
                $enrolledCourseIds = $enrolledSections->pluck('course_id')->toArray();
                $termYearPairs     = $enrolledSections
                    ->map(fn($s) => ['term' => $s->term, 'year' => $s->year])
                    ->unique(fn($p) => $p['term'] . '|' . $p['year'])
                    ->values();

                if (!empty($enrolledCourseIds)) {
                    $allSlots = TimetableSlot::whereHas('courseSection',
                            fn($q) => $q->whereIn('course_id', $enrolledCourseIds)
                        )
                        ->whereHas('timetable', function ($q) use ($termYearPairs, $studentRecord) {
                            $q->whereIn('status', ['active', 'draft'])
                              ->where('department_id', $studentRecord->department_id)
                              ->where(function ($inner) use ($termYearPairs) {
                                  foreach ($termYearPairs as $pair) {
                                      $inner->orWhere(fn($sub) =>
                                          $sub->where('term', $pair['term'])
                                              ->where('year', $pair['year'])
                                      );
                                  }
                              });
                        })
                        ->with(['courseSection.course', 'teacher', 'room'])
                        ->get()
                        ->unique('id');
                }
            }

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

            // Build a set of course codes the student is currently enrolled in
            // (these prerequisites are in progress — don't flag them as unmet)
            $enrolledCourseCodes = $studentId
                ? StudentCourseRegistration::where('student_id', $studentId)
                    ->where('status', 'enrolled')
                    ->with('courseSection.course')
                    ->get()
                    ->map(fn($r) => optional($r->courseSection->course)->code)
                    ->filter()
                    ->values()
                    ->toArray()
                : [];

            // Find next-semester courses with unmet prerequisites.
            // Only alert if the prerequisite has NOT been passed AND is NOT currently being taken.
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
                    $prereq = $course->prerequisite_course_code;
                    // Already passed — no alert needed
                    if (in_array($prereq, $passedCourseCodes)) {
                        continue;
                    }
                    // Currently enrolled in the prerequisite — in progress, suppress alert
                    if (in_array($prereq, $enrolledCourseCodes)) {
                        continue;
                    }
                    // Prerequisite not started or failed without re-enrollment
                    $prerequisiteAlerts->push([
                        'course'      => $course->name,
                        'course_code' => $course->code,
                        'requires'    => $prereq,
                    ]);
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
