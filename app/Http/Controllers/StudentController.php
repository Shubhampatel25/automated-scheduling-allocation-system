<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Models\StudentCourseRegistration;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $students = Student::with(['department', 'studentCourseRegistrations.courseSection.course'])
            ->withCount(['studentCourseRegistrations as enrolled_count' => function ($q) {
                $q->where('status', 'enrolled');
            }])
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('roll_no', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->paginate(10)
            ->withQueryString();
        $departments = Department::all();
        return view('admin.students.index', compact('students', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'roll_no'       => 'required|string|max:50|unique:students,roll_no',
            'department_id' => 'required|exists:departments,id',
            'semester'      => 'required|integer|between:1,8',
        ]);

        $email = strtolower(str_replace([' ', '/'], '', $request->roll_no)) . '@student.edu';

        $user = User::create([
            'username' => $request->roll_no,
            'email'    => $email,
            'password' => Hash::make('password'),
            'role'     => 'student',
            'status'   => 'active',
        ]);

        Student::create([
            'user_id'       => $user->id,
            'roll_no'       => $request->roll_no,
            'name'          => $request->name,
            'email'         => $email,
            'department_id' => $request->department_id,
            'semester'      => $request->semester,
            'status'        => 'active',
            'created_at'    => now(),
        ]);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student added successfully.');
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'roll_no'       => 'required|string|max:50|unique:students,roll_no,' . $student->id,
            'department_id' => 'required|exists:departments,id',
            'semester'      => 'required|integer|between:1,8',
        ]);

        $student->update([
            'name'          => $request->name,
            'roll_no'       => $request->roll_no,
            'department_id' => $request->department_id,
            'semester'      => $request->semester,
        ]);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $userId = $student->user_id;

        // Delete child records first (foreign key order matters)
        $student->studentCourseRegistrations()->delete();

        $student->delete();
        if ($userId) {
            ActivityLog::where('user_id', $userId)->delete();
            User::destroy($userId);
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
