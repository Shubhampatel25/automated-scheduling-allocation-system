<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Department;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    
    
     
    public function index()
    {
        try {
            $teachers = Teacher::with('department')
                ->withCount('courseAssignments')
                ->latest('created_at')
                ->paginate(15);

            return view('admin.teachers.index', compact('teachers'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load teachers. Please try again.');
        }
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.teachers.create', compact('departments'));
    }

    /**
     * Store a newly created teacher in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'string', 'max:20', 'unique:teachers,employee_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'username' => $request->employee_id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'professor',
                'status' => $request->status,
            ]);

            // Create teacher record
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'employee_id' => $request->employee_id,
                'name' => $request->name,
                'email' => $request->email,
                'department_id' => $request->department_id,
                'status' => $request->status,
                'created_at' => now(),
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'Teacher',
                'entity_id' => $teacher->id,
                'details' => "Created teacher: {$teacher->employee_id} - {$teacher->name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create teacher. Please try again.');
        }
    }

    /**
     * Display the specified teacher
     */
    public function show(Teacher $teacher)
    {
        try {
            $teacher->load(['department', 'user', 'courseAssignments.courseSection.course']);
            
            $assignedCourses = $teacher->courseAssignments()
                ->with('courseSection.course')
                ->get();

            $totalCourses = $assignedCourses->unique('courseSection.course_id')->count();

            $timetableSlots = $teacher->timetableSlots()
                ->whereHas('timetable', function ($q) {
                    $q->where('status', 'active');
                })
                ->with(['courseSection.course', 'room'])
                ->get();

            return view('admin.teachers.show', compact('teacher', 'assignedCourses', 'totalCourses', 'timetableSlots'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load teacher details. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit(Teacher $teacher)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.teachers.edit', compact('teacher', 'departments'));
    }

    /**
     * Update the specified teacher in database
     */
    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'employee_id' => ['required', 'string', 'max:20', 'unique:teachers,employee_id,' . $teacher->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $teacher->user_id],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            DB::beginTransaction();

            // Update user account
            $teacher->user->update([
                'username' => $request->employee_id,
                'email' => $request->email,
                'status' => $request->status,
            ]);

            // Update teacher record
            $teacher->update([
                'employee_id' => $request->employee_id,
                'name' => $request->name,
                'email' => $request->email,
                'department_id' => $request->department_id,
                'status' => $request->status,
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'Teacher',
                'entity_id' => $teacher->id,
                'details' => "Updated teacher: {$teacher->employee_id} - {$teacher->name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update teacher. Please try again.');
        }
    }

    /**
     * Remove the specified teacher from database
     */
    public function destroy(Teacher $teacher)
    {
        try {
            DB::beginTransaction();

            // Check if teacher has course assignments
            $assignmentCount = $teacher->courseAssignments()->count();
            
            if ($assignmentCount > 0) {
                return back()->with('error', 'Cannot delete teacher with existing course assignments. Please reassign or remove assignments first.');
            }

            // Check if teacher is HOD
            $hodRecord = $teacher->user->hod;
            if ($hodRecord) {
                return back()->with('error', 'Cannot delete teacher who is a HOD. Please remove HOD assignment first.');
            }

            $employeeId = $teacher->employee_id;
            $name = $teacher->name;
            $userId = $teacher->user_id;

            // Delete teacher record
            $teacher->delete();

            // Delete user account
            User::find($userId)->delete();

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'Teacher',
                'entity_id' => $teacher->id,
                'details' => "Deleted teacher: {$employeeId} - {$name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete teacher. Please try again.');
        }
    }

    /**
     * Toggle teacher status (active/inactive)
     */
    public function toggleStatus(Teacher $teacher)
    {
        try {
            DB::beginTransaction();

            $newStatus = $teacher->status === 'active' ? 'inactive' : 'active';
            
            // Update teacher status
            $teacher->update(['status' => $newStatus]);
            
            // Update user status
            $teacher->user->update(['status' => $newStatus]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'entity_type' => 'Teacher',
                'entity_id' => $teacher->id,
                'details' => "Changed teacher status to {$newStatus}: {$teacher->employee_id}",
                'created_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', "Teacher status changed to {$newStatus}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to change teacher status. Please try again.');
        }
    }

    /**
     * Get teachers by department (for AJAX)
     */
    public function byDepartment($departmentId)
    {
        try {
            $teachers = Teacher::where('department_id', $departmentId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'employee_id', 'name', 'email']);

            return response()->json([
                'success' => true,
                'teachers' => $teachers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load teachers.'
            ], 500);
        }
    }

    /**
     * Reset teacher password
     */
    public function resetPassword(Request $request, Teacher $teacher)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $teacher->user->update([
                'password' => Hash::make($request->password),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'password_reset',
                'entity_type' => 'Teacher',
                'entity_id' => $teacher->id,
                'details' => "Reset password for teacher: {$teacher->employee_id}",
                'created_at' => now(),
            ]);

            return back()->with('success', 'Teacher password reset successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reset password. Please try again.');
        }
    }
}
