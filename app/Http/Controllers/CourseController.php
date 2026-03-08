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
        $courses = Course::with('department')
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            )
            ->paginate(10)
            ->withQueryString();
        $departments = Department::all();
        return view('admin.courses.index', compact('courses', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:20|unique:courses,code',
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'credits'       => 'required|integer|between:1,6',
            'type'          => 'required|in:theory,lab,hybrid',
            'status'        => 'required|in:active,inactive',
        ]);

        Course::create([
            'code'          => strtoupper($request->code),
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'credits'       => $request->credits,
            'type'          => $request->type,
            'status'        => $request->status,
            'created_at'    => now(),
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course added successfully.');
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'code'          => 'required|string|max:20|unique:courses,code,' . $course->id,
            'name'          => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'credits'       => 'required|integer|between:1,6',
            'type'          => 'required|in:theory,lab,hybrid',
            'status'        => 'required|in:active,inactive',
        ]);

        $course->update([
            'code'          => strtoupper($request->code),
            'name'          => $request->name,
            'department_id' => $request->department_id,
            'credits'       => $request->credits,
            'type'          => $request->type,
            'status'        => $request->status,
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
