<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    
    public function index()
    {
        try {
            $courses = Course::with('department')
                ->latest('created_at')
                ->paginate(15);

            return view('admin.courses.index', compact('courses'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load courses. Please try again.');
        }
    }

    
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.courses.create', compact('departments'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'credits' => ['required', 'integer', 'min:1', 'max:6'],
            'type' => ['required', 'in:theory,lab,both'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            $course = Course::create([
                'code' => $request->code,
                'name' => $request->name,
                'department_id' => $request->department_id,
                'credits' => $request->credits,
                'type' => $request->type,
                'description' => $request->description,
                'status' => $request->status,
                'created_at' => now(),
            ]);

            
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'Course',
                'entity_id' => $course->id,
                'details' => "Created course: {$course->code} - {$course->name}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create course. Please try again.');
        }
    }

    
    public function show(Course $course)
    {
        try {
            $course->load(['department', 'sections.assignments.teacher']);
            return view('admin.courses.show', compact('course'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load course details. Please try again.');
        }
    }

    
    public function edit(Course $course)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.courses.edit', compact('course', 'departments'));
    }

    
    public function update(Request $request, Course $course)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:courses,code,' . $course->id],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'credits' => ['required', 'integer', 'min:1', 'max:6'],
            'type' => ['required', 'in:theory,lab,both'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            $course->update([
                'code' => $request->code,
                'name' => $request->name,
                'department_id' => $request->department_id,
                'credits' => $request->credits,
                'type' => $request->type,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'Course',
                'entity_id' => $course->id,
                'details' => "Updated course: {$course->code} - {$course->name}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update course. Please try again.');
        }
    }

    /**
     * Remove the specified course from database
     */
    public function destroy(Course $course)
    {
        try {
            // Check if course has sections
            if ($course->sections()->count() > 0) {
                return back()->with('error', 'Cannot delete course with existing sections. Please delete sections first.');
            }

            $courseCode = $course->code;
            $courseName = $course->name;

            $course->delete();

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'Course',
                'entity_id' => $course->id,
                'details' => "Deleted course: {$courseCode} - {$courseName}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete course. Please try again.');
        }
    }

    /**
     * Toggle course status (active/inactive)
     */
    public function toggleStatus(Course $course)
    {
        try {
            $newStatus = $course->status === 'active' ? 'inactive' : 'active';
            $course->update(['status' => $newStatus]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'entity_type' => 'Course',
                'entity_id' => $course->id,
                'details' => "Changed course status to {$newStatus}: {$course->code}",
                'created_at' => now(),
            ]);

            return back()->with('success', "Course status changed to {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to change course status. Please try again.');
        }
    }

    /**
     * Get courses by department (for AJAX)
     */
    public function byDepartment($departmentId)
    {
        try {
            $courses = Course::where('department_id', $departmentId)
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'credits', 'type']);

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load courses.'
            ], 500);
        }
    }
}
