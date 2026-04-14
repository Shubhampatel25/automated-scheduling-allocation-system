<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Department;
use App\Models\CourseSection;
use App\Models\CourseAssignment;
use App\Models\StudentCourseRegistration;
use App\Models\TimetableSlot;
use App\Models\Conflict;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $courses = Course::with(['department', 'sections' => fn($q) => $q->orderByDesc('year')])
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->get();
        $departments    = Department::orderBy('name')->get();
        // All courses as prerequisite options (code => "CODE – Name")
        $allCourseCodes = Course::orderBy('code')->get(['id', 'code', 'name']);
        return view('admin.courses.index', compact('courses', 'departments', 'allCourseCodes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'                     => 'required|string|max:20|unique:courses,code',
            'name'                     => 'required|string|max:255',
            'department_id'            => 'required|exists:departments,id',
            'semester'                 => 'nullable|integer|between:1,8',
            'fee'                      => 'nullable|numeric|min:0',
            'credits'                  => 'required|integer|between:1,6',
            'type'                     => 'required|in:theory,lab,hybrid',
            'status'                   => 'required|in:active,inactive',
            'prerequisite_course_code' => 'nullable|string|max:20|exists:courses,code',
            'prerequisite_mandatory'   => 'nullable|boolean',
        ]);

        $course = Course::create([
            'code'                     => strtoupper($request->code),
            'name'                     => $request->name,
            'department_id'            => $request->department_id,
            'semester'                 => $request->semester,
            'fee'                      => $request->fee ?? 0,
            'credits'                  => $request->credits,
            'type'                     => $request->type,
            'status'                   => $request->status,
            'prerequisite_course_code' => $request->prerequisite_course_code ?: null,
            'prerequisite_mandatory'   => $request->boolean('prerequisite_mandatory'),
            'created_at'               => now(),
        ]);

        // Auto-create a default section so the course appears in student registration
        CourseSection::create([
            'course_id'        => $course->id,
            'section_number'   => 1,
            'term'             => now()->month <= 4 ? 'Winter' : (now()->month <= 8 ? 'Summer' : 'Fall'),
            'year'             => now()->year,
            'max_students'     => 30,
            'enrolled_students'=> 0,
            'created_at'       => now(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course added successfully.');
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'code'                     => 'required|string|max:20|unique:courses,code,' . $course->id,
            'name'                     => 'required|string|max:255',
            'department_id'            => 'required|exists:departments,id',
            'semester'                 => 'nullable|integer|between:1,8',
            'fee'                      => 'nullable|numeric|min:0',
            'credits'                  => 'required|integer|between:1,6',
            'type'                     => 'required|in:theory,lab,hybrid',
            'status'                   => 'required|in:active,inactive',
            'prerequisite_course_code' => 'nullable|string|max:20|exists:courses,code',
            'prerequisite_mandatory'   => 'nullable|boolean',
        ]);

        $course->update([
            'code'                     => strtoupper($request->code),
            'name'                     => $request->name,
            'department_id'            => $request->department_id,
            'semester'                 => $request->semester,
            'fee'                      => $request->fee ?? 0,
            'credits'                  => $request->credits,
            'type'                     => $request->type,
            'status'                   => $request->status,
            'prerequisite_course_code' => $request->prerequisite_course_code ?: null,
            'prerequisite_mandatory'   => $request->boolean('prerequisite_mandatory'),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        // course → sections → (assignments, registrations, slots → conflicts)
        $sectionIds = $course->sections()->pluck('id');

        if ($sectionIds->isNotEmpty()) {
            $slotIds = TimetableSlot::whereIn('course_section_id', $sectionIds)->pluck('id');

            if ($slotIds->isNotEmpty()) {
                Conflict::whereIn('slot_id_1', $slotIds)
                        ->orWhereIn('slot_id_2', $slotIds)
                        ->delete();
                TimetableSlot::whereIn('course_section_id', $sectionIds)->delete();
            }

            CourseAssignment::whereIn('course_section_id', $sectionIds)->delete();
            StudentCourseRegistration::whereIn('course_section_id', $sectionIds)->delete();
            CourseSection::whereIn('id', $sectionIds)->delete();
        }

        $course->delete();
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
