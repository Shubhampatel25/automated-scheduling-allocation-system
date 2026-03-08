<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\CourseAssignment;
use App\Models\TeacherAvailability;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class ProfessorController extends Controller
{
    public function students(Request $request)
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;
        $search    = $request->get('search');

        $sectionIds = $teacherId
            ? CourseAssignment::where('teacher_id', $teacherId)->pluck('course_section_id')
            : collect();

        if ($sectionIds->isEmpty()) {
            $myStudents = Student::whereIn('id', [])->paginate(15);
        } else {
            $myStudents = Student::whereHas('studentCourseRegistrations', function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)->where('status', 'enrolled');
                })
                ->with(['department', 'studentCourseRegistrations' => function ($q) use ($sectionIds) {
                    $q->whereIn('course_section_id', $sectionIds)->with('courseSection.course');
                }])
                ->when($search, fn($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%")
                )
                ->paginate(15)
                ->withQueryString();
        }

        return view('professor.students', compact('myStudents', 'teacher', 'search'));
    }

    public function availability()
    {
        $user      = Auth::user();
        $teacher   = Teacher::where('user_id', $user->id)->first();
        $teacherId = $teacher ? $teacher->id : null;

        $availability = $teacherId
            ? TeacherAvailability::where('teacher_id', $teacherId)
                ->orderByRaw("FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('professor.availability', compact('availability', 'teacher'));
    }
}
