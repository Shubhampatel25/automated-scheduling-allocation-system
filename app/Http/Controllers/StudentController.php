<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Department;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index()
    {
        try {
            $students = Student::with('department')
                ->latest('created_at')
                ->paginate(15);

            return view('admin.students.index', compact('students'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load students. Please try again.');
        }
    }

    /**
     * Show the form for creating a new student
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.students.create', compact('departments'));
    }

    /**
     * Store a newly created student in database
     */
    public function store(Request $request)
    {
        $request->validate([
            'roll_no' => ['required', 'string', 'max:20', 'unique:students,roll_no'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:8'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            DB::beginTransaction();

            // Create user account
            $user = User::create([
                'username' => $request->roll_no,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'status' => $request->status,
            ]);

            // Create student record
            $student = Student::create([
                'user_id' => $user->id,
                'roll_no' => $request->roll_no,
                'name' => $request->name,
                'email' => $request->email,
                'department_id' => $request->department_id,
                'semester' => $request->semester,
                'status' => $request->status,
                'created_at' => now(),
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'Student',
                'entity_id' => $student->id,
                'details' => "Created student: {$student->roll_no} - {$student->name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create student. Please try again.');
        }
    }

    /**
     * Display the specified student
     */
    public function show(Student $student)
    {
        try {
            $student->load(['department', 'user', 'studentCourseRegistrations.courseSection.course']);
            
            $enrolledCourses = $student->studentCourseRegistrations()
                ->where('status', 'enrolled')
                ->with('courseSection.course')
                ->get();

            $totalCredits = $enrolledCourses->sum(function ($registration) {
                return $registration->courseSection->course->credits ?? 0;
            });

            return view('admin.students.show', compact('student', 'enrolledCourses', 'totalCredits'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load student details. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit(Student $student)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.students.edit', compact('student', 'departments'));
    }

    
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'roll_no' => ['required', 'string', 'max:20', 'unique:students,roll_no,' . $student->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $student->user_id],
            'department_id' => ['required', 'exists:departments,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:8'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            DB::beginTransaction();

            // Update user account
            $student->user->update([
                'username' => $request->roll_no,
                'email' => $request->email,
                'status' => $request->status,
            ]);

            // Update student record
            $student->update([
                'roll_no' => $request->roll_no,
                'name' => $request->name,
                'email' => $request->email,
                'department_id' => $request->department_id,
                'semester' => $request->semester,
                'status' => $request->status,
            ]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'Student',
                'entity_id' => $student->id,
                'details' => "Updated student: {$student->roll_no} - {$student->name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update student. Please try again.');
        }
    }

    
    public function destroy(Student $student)
    {
        try {
            DB::beginTransaction();

            
            $registrationCount = $student->studentCourseRegistrations()->count();
            
            if ($registrationCount > 0) {
                return back()->with('error', 'Cannot delete student with existing course registrations. Please remove registrations first.');
            }

            $rollNo = $student->roll_no;
            $name = $student->name;
            $userId = $student->user_id;

            
            $student->delete();

            
            User::find($userId)->delete();

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'entity_type' => 'Student',
                'entity_id' => $student->id,
                'details' => "Deleted student: {$rollNo} - {$name}",
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete student. Please try again.');
        }
    }

    
    public function toggleStatus(Student $student)
    {
        try {
            DB::beginTransaction();

            $newStatus = $student->status === 'active' ? 'inactive' : 'active';
            
            // Update student status
            $student->update(['status' => $newStatus]);
            
            // Update user status
            $student->user->update(['status' => $newStatus]);

            // Log activity
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'entity_type' => 'Student',
                'entity_id' => $student->id,
                'details' => "Changed student status to {$newStatus}: {$student->roll_no}",
                'created_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', "Student status changed to {$newStatus}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to change student status. Please try again.');
        }
    }

    
    public function byDepartment($departmentId)
    {
        try {
            $students = Student::where('department_id', $departmentId)
                ->where('status', 'active')
                ->orderBy('roll_no')
                ->get(['id', 'roll_no', 'name', 'semester']);

            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students.'
            ], 500);
        }
    }

    
    public function resetPassword(Request $request, Student $student)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $student->user->update([
                'password' => Hash::make($request->password),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'password_reset',
                'entity_type' => 'Student',
                'entity_id' => $student->id,
                'details' => "Reset password for student: {$student->roll_no}",
                'created_at' => now(),
            ]);

            return back()->with('success', 'Student password reset successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reset password. Please try again.');
        }
    }
}
