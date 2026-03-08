<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseSection;
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

        $section = CourseSection::findOrFail($request->course_section_id);

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

        StudentCourseRegistration::create([
            'student_id'        => $student->id,
            'course_section_id' => $section->id,
            'status'            => 'enrolled',
            'registered_at'     => now(),
        ]);

        $section->increment('enrolled_students');

        return back()->with('success', 'Successfully registered for ' . $section->course->name . '.');
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
}
