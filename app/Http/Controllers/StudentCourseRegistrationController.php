<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\FeePayment;
use App\Models\StudentCourseRegistration;

class StudentCourseRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'course_section_id' => 'required|exists:course_sections,id',
        ]);

        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return back()->with('error', 'Student record not found.');
        }

        $section = CourseSection::with('course')->findOrFail($request->course_section_id);
        $course = $section->course;

        // Check fee payment status
        $feePaid = FeePayment::where('student_id', $student->id)
            ->where('semester', $student->semester)
            ->where('year', now()->year)
            ->where('status', 'paid')
            ->exists();

        if (!$feePaid) {
            return back()->with('error', 'You must complete fee payment for the current semester before registering for courses.');
        }

        // Check department match
        if ($course->department_id !== $student->department_id) {
            return back()->with('error', 'You can only enroll in courses from your department.');
        }

        // Check semester match (allow courses with null semester as electives)
        if ($course->semester !== null && $course->semester !== $student->semester) {
            return back()->with('error', 'This course is not available for your current semester.');
        }

        // Check already enrolled
        $existing = StudentCourseRegistration::where('student_id', $student->id)
            ->where('course_section_id', $section->id)
            ->where('status', 'enrolled')
            ->first();

        if ($existing) {
            return back()->with('error', 'You are already enrolled in this course.');
        }

        // Check capacity
        if ($section->enrolled_students >= $section->max_students) {
            return back()->with('error', 'This course section is full.');
        }

        DB::table('student_course_registrations')->insert([
            'student_id'        => $student->id,
            'course_section_id' => $section->id,
            'status'            => 'enrolled',
            'registered_at'     => DB::raw('NOW()'),
        ]);

        $section->increment('enrolled_students');

        return back()->with('success', 'Successfully registered for ' . $course->name . '.');
    }

    public function drop(StudentCourseRegistration $registration)
    {
        $user = auth()->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student || $registration->student_id !== $student->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        $registration->update(['status' => 'dropped']);
        $registration->courseSection->decrement('enrolled_students');

        return back()->with('success', 'Course dropped successfully.');
    }

    /**
     * Admin: mark a student's course registration as completed.
     */
    public function complete(StudentCourseRegistration $registration)
    {
        if ($registration->status !== 'enrolled') {
            return back()->with('error', 'Only enrolled courses can be marked as completed.');
        }

        $registration->update(['status' => 'completed']);
        $registration->courseSection->decrement('enrolled_students');

        return back()->with('success', 'Course marked as completed.');
    }
}
