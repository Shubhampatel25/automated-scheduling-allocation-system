<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index()
    {
        try {
            $departments = Department::withCount(['teachers', 'courses', 'students'])
                ->latest('created_at')
                ->paginate(15);

            return view('admin.departments.index', compact('departments'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load departments. Please try again.');
        }
    }

    /**
     * Show the form for creating a new department
     */
    public function create()
    {
        return view('admin.departments.create');
    }

    /**
     * Store a newly created department in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $department = Department::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'description' => $request->description,
                'created_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'details' => "Created department: {$department->code} - {$department->name}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create department. Please try again.');
        }
    }

    /**
     * Display the specified department
     */
    public function show(Department $department)
    {
        try {
            $department->load(['teachers', 'courses', 'students', 'hods']);
            
            $teacherCount = $department->teachers()->count();
            $courseCount = $department->courses()->count();
            $studentCount = $department->students()->count();
            $activeHod = $department->hods()->where('status', 'active')->first();

            return view('admin.departments.show', compact(
                'department',
                'teacherCount',
                'courseCount',
                'studentCount',
                'activeHod'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load department details. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified department
     */
    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    /**
     * Update the specified department in database
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:departments,code,' . $department->id],
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $department->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'description' => $request->description,
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'details' => "Updated department: {$department->code} - {$department->name}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update department. Please try again.');
        }
    }

    /**
     * Remove the specified department from database
     */
    public function destroy(Department $department)
    {
        try {
            // Check if department has related records
            $teacherCount = $department->teachers()->count();
            $courseCount = $department->courses()->count();
            $studentCount = $department->students()->count();

            if ($teacherCount > 0 || $courseCount > 0 || $studentCount > 0) {
                return back()->with('error', 'Cannot delete department with existing teachers, courses, or students. Please reassign or delete them first.');
            }

            $deptCode = $department->code;
            $deptName = $department->name;

            $department->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'Department',
                'entity_id' => $department->id,
                'details' => "Deleted department: {$deptCode} - {$deptName}",
                'created_at' => now(),
            ]);

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete department. Please try again.');
        }
    }

    /**
     * Get all active departments (for AJAX)
     */
    public function getActiveDepartments()
    {
        try {
            $departments = Department::orderBy('name')->get(['id', 'code', 'name']);

            return response()->json([
                'success' => true,
                'departments' => $departments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load departments.'
            ], 500);
        }
    }
}
