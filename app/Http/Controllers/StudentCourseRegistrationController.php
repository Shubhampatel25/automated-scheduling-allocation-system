<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\CourseSection;
use App\Models\FeePayment;
use App\Models\StudentCourseRegistration;
use App\Models\TimetableSlot;
use App\Services\RegistrationEligibilityService;

class StudentCourseRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'course_section_id' => 'required|exists:course_sections,id',
        ]);

        $user    = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return back()->with('error', 'Student record not found.');
        }

        $section = CourseSection::with('course')->findOrFail($request->course_section_id);
        $course  = $section->course;

        // ── 1. Department match ───────────────────────────────────────────────
        if ($course->department_id !== $student->department_id) {
            return back()->with('error', 'You can only enroll in courses from your own department.');
        }

        // ── 2. Already PASSED this course? Block — no need to re-enroll ─────
        $alreadyPassed = StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'completed')
            ->where('result', 'pass')
            ->whereHas('courseSection', fn($q) => $q->where('course_id', $course->id))
            ->exists();

        if ($alreadyPassed) {
            return back()->with('error',
                'You have already passed "' . $course->name . '". Re-enrollment is not required.');
        }

        // ── 3. Already ENROLLED in another active section of this course? ────
        $alreadyEnrolled = StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->whereHas('courseSection', fn($q) => $q->where('course_id', $course->id))
            ->exists();

        if ($alreadyEnrolled) {
            return back()->with('error',
                'You are already enrolled in a section of "' . $course->name . '".');
        }

        // ── 4. Determine whether this is a retake ────────────────────────────
        //    Retake = student has a completed FAIL record for this course.
        $isRetake = StudentCourseRegistration::where('student_id', $student->id)
            ->where('status', 'completed')
            ->where('result', 'fail')
            ->whereHas('courseSection', fn($q) => $q->where('course_id', $course->id))
            ->exists();

        // ── 5. Fee payment check ──────────────────────────────────────────────
        if ($isRetake) {
            // Retake path: supplemental fee required for this specific course
            $supplementalFeePaid = FeePayment::where('student_id', $student->id)
                ->where('type', 'supplemental')
                ->where('course_id', $course->id)
                ->where('status', 'paid')
                ->exists();

            if (!$supplementalFeePaid) {
                return back()->with('error',
                    'Supplemental fee required: Pay the retake fee for "' . $course->name
                    . '" on the Fee Payment page before re-enrolling.');
            }
        } else {
            // Regular enrollment path: semester fee must be paid
            $regularFeePaid = FeePayment::where('student_id', $student->id)
                ->where('type', 'regular')
                ->where('semester', $student->semester)
                ->where('year', now()->year)
                ->where('status', 'paid')
                ->exists();

            if (!$regularFeePaid) {
                return back()->with('error',
                    'Fee payment required: Complete your Semester ' . $student->semester
                    . ' fee payment before registering for courses.');
            }
        }

        // ── 5b. Sequential semester guard (regular enrollments only) ─────────
        //    Prevents a student whose DB semester jumped (e.g. 3 → 6) from
        //    registering for courses beyond their actual sequential progress.
        //    Uses history-first, then completed registrations; logs + fixes if wrong.
        if (!$isRetake) {
            $semAuth = (new RegistrationEligibilityService())->getAuthoritativeSemester($student->id, $student);
            if ($semAuth['corrected']) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['semester' => $semAuth['semester']]);
                $student->semester = $semAuth['semester'];

                ActivityLog::create([
                    'user_id'     => Auth::id(),
                    'action'      => 'semester_corrected',
                    'entity_type' => 'student',
                    'entity_id'   => $student->id,
                    'details'     => $semAuth['reason'],
                    'created_at'  => now(),
                ]);
            }
        }

        // ── 6. Semester match (regular enrollments only, not retakes) ────────
        //    Retakes bypass this — a Sem1 course retaken by a Sem2 student is valid.
        if (!$isRetake) {
            if ($course->semester !== null && $course->semester !== $student->semester) {
                return back()->with('error',
                    'This course belongs to Semester ' . $course->semester
                    . ' and is not available for your current semester (' . $student->semester . ').');
            }
        }

        // ── 7. Prerequisite check — per course, not per semester ─────────────
        //    Only checked for new (non-retake) enrollments.
        //    Advisory prerequisites warn but do NOT block enrollment.
        //    This allows students to progress to a new semester and retake failed
        //    courses in parallel, even when some prerequisites are unmet.
        if (!$isRetake && $course->prerequisite_course_code) {
            $passedCourseCodes = StudentCourseRegistration::where('student_id', $student->id)
                ->where('status', 'completed')
                ->where('result', 'pass')
                ->with('courseSection.course')
                ->get()
                ->map(fn($r) => optional($r->courseSection?->course)->code)
                ->filter()->unique()->toArray();

            if (!in_array($course->prerequisite_course_code, $passedCourseCodes)) {
                if ($course->prerequisite_mandatory) {
                    // Hard block: mandatory prerequisite not satisfied
                    return back()->with('error',
                        'Prerequisite not passed: You must pass "'
                        . $course->prerequisite_course_code
                        . '" before registering for "' . $course->name . '".');
                }
                // Advisory prerequisite not met — allow enrollment (UI already warned the student)
            }
        }

        // ── 8. Capacity check ────────────────────────────────────────────────
        if ($section->enrolled_students >= $section->max_students) {
            return back()->with('error',
                'Section ' . $section->section_number . ' of "' . $course->name . '" is full. '
                . 'Please choose a different section.');
        }

        // ── 9. Schedule conflict check ───────────────────────────────────────
        $newSlots = TimetableSlot::where('course_section_id', $section->id)->get();
        if ($newSlots->isNotEmpty()) {
            $currentSectionIds = StudentCourseRegistration::where('student_id', $student->id)
                ->where('status', 'enrolled')
                ->pluck('course_section_id');

            if ($currentSectionIds->isNotEmpty()) {
                foreach ($newSlots as $newSlot) {
                    $conflicting = TimetableSlot::whereIn('course_section_id', $currentSectionIds)
                        ->where('day_of_week', $newSlot->day_of_week)
                        ->where('start_time', '<', $newSlot->end_time)
                        ->where('end_time', '>', $newSlot->start_time)
                        ->with('courseSection.course')
                        ->first();

                    if ($conflicting) {
                        $conflictName = optional(optional($conflicting->courseSection)->course)->name
                            ?? 'another enrolled course';
                        return back()->with('error',
                            'Schedule conflict on ' . $newSlot->day_of_week . ': this section overlaps with "'
                            . $conflictName . '".');
                    }
                }
            }
        }

        // ── 10. Enroll (atomic with pessimistic lock to prevent race conditions) ─
        $enrolled = DB::transaction(function () use ($student, $section, $course, $isRetake) {
            // Re-check capacity inside the transaction with a row lock to prevent
            // two students from enrolling simultaneously into a full section.
            $fresh = CourseSection::lockForUpdate()->find($section->id);
            if ($fresh->enrolled_students >= $fresh->max_students) {
                return false; // capacity taken by concurrent request
            }

            DB::table('student_course_registrations')->insert([
                'student_id'        => $student->id,
                'course_section_id' => $section->id,
                'status'            => 'enrolled',
                'registered_at'     => DB::raw('NOW()'),
            ]);

            $fresh->increment('enrolled_students');

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => $isRetake ? 'enroll_retake' : 'enroll_course',
                'entity_type' => 'student_course_registration',
                'entity_id'   => $student->id,
                'details'     => ($isRetake ? '[Retake] ' : '') .
                    'Student ' . $student->roll_no . ' enrolled in "' . $course->name .
                    '" (Section ' . $section->section_number . ')',
                'created_at'  => now(),
            ]);

            return true;
        });

        if (!$enrolled) {
            return back()->with('error',
                'Section ' . $section->section_number . ' of "' . $course->name .
                '" just became full. Please choose a different section.');
        }

        $suffix = $isRetake ? ' (retake)' : '';
        return back()->with('success',
            'Successfully enrolled in "' . $course->name . '"' . $suffix . '.');
    }

    public function drop(StudentCourseRegistration $registration)
    {
        $user    = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student || $registration->student_id !== $student->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        $courseName = optional(optional($registration->courseSection)->course)->name ?? 'Unknown Course';

        $dropError = null;

        DB::transaction(function () use ($registration, $student, $courseName, &$dropError) {
            // Re-read the registration under a lock so concurrent requests cannot
            // both see status='enrolled' and both decrement the counter.
            $locked = \App\Models\StudentCourseRegistration::lockForUpdate()->find($registration->id);

            // ── Guard: only enrolled courses can be dropped ───────────────────
            if (!$locked || $locked->status !== 'enrolled') {
                $status = $locked->status ?? 'unknown';
                $label = match ($status) {
                    'dropped'   => 'already been dropped',
                    'completed' => 'already been completed',
                    default     => 'not eligible for dropping',
                };
                $dropError = "This course has {$label} and cannot be dropped.";
                return;
            }

            $locked->update(['status' => 'dropped']);

            // Use max(0, ...) so enrolled_students can never go negative even if
            // data was manually modified outside the application.
            $section = $locked->courseSection()->lockForUpdate()->first();
            if ($section && $section->enrolled_students > 0) {
                $section->decrement('enrolled_students');
            }

            if (!$dropError) {
                ActivityLog::create([
                    'user_id'     => Auth::id(),
                    'action'      => 'drop_course',
                    'entity_type' => 'student_course_registration',
                    'entity_id'   => $registration->id,
                    'details'     => 'Student ' . $student->roll_no . ' dropped "' . $courseName . '"',
                    'created_at'  => now(),
                ]);
            }
        });

        if ($dropError) {
            return back()->with('error', $dropError);
        }

        return back()->with('success', 'Course dropped successfully.');
    }

    /**
     * Admin: mark a student's course registration as completed with a pass/fail result.
     */
    public function complete(Request $request, StudentCourseRegistration $registration)
    {
        $request->validate([
            'result' => 'required|in:pass,fail',
        ]);

        if ($registration->status !== 'enrolled') {
            return back()->with('error', 'Only enrolled courses can be marked as completed.');
        }

        $courseName = optional(optional($registration->courseSection)->course)->name ?? 'Unknown Course';
        $studentRoll = optional($registration->student)->roll_no ?? 'Unknown';
        $label = $request->result === 'pass' ? 'Pass' : 'Fail';

        DB::transaction(function () use ($registration, $request, $courseName, $studentRoll, $label) {
            $registration->update([
                'status' => 'completed',
                'result' => $request->result,
            ]);
            $section = $registration->courseSection()->lockForUpdate()->first();
            if ($section && $section->enrolled_students > 0) {
                $section->decrement('enrolled_students');
            }

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'complete_course',
                'entity_type' => 'student_course_registration',
                'entity_id'   => $registration->id,
                'details'     => 'Admin marked student ' . $studentRoll . ' as ' . $label . ' in "' . $courseName . '"',
                'created_at'  => now(),
            ]);
        });

        return back()->with('success', "Course marked as completed ({$label}).");
    }
}
