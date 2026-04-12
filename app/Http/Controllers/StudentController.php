<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Models\StudentCourseRegistration;
use App\Models\StudentSemesterHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $students = Student::with(['department', 'studentCourseRegistrations' => function ($q) {
                $q->whereIn('status', ['enrolled', 'completed'])
                  ->with('courseSection.course');
            }])
            ->withCount(['studentCourseRegistrations as enrolled_count' => function ($q) {
                $q->where('status', 'enrolled');
            }])
            ->orderBy('name')
            ->get();
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
            'email'         => 'nullable|email|max:255|unique:users,email|unique:students,email',
        ]);

        if ($request->filled('email')) {
            $email = strtolower(trim($request->email));
        } else {
            $firstName = strtolower(str_replace(' ', '', explode(' ', trim($request->name))[0]));
            $rollClean = strtolower(str_replace(['/', ' '], '', $request->roll_no));
            $email     = $firstName . $rollClean . '@student.edu';
        }

        // Atomic: both User and Student must be created together.
        // If Student creation fails, the User record is rolled back automatically.
        DB::transaction(function () use ($request, $email) {
            $user = User::create([
                'username' => $request->roll_no,
                'email'    => $email,
                'password' => Hash::make('password'),
                'role'     => 'student',
                'status'   => 'active',
            ]);

            $student = Student::create([
                'user_id'       => $user->id,
                'roll_no'       => $request->roll_no,
                'name'          => $request->name,
                'email'         => $email,
                'department_id' => $request->department_id,
                'semester'      => $request->semester,
                'status'        => 'active',
                'created_at'    => now(),
            ]);

            StudentSemesterHistory::create([
                'student_id' => $student->id,
                'semester'   => $student->semester,
                'year'       => now()->year,
                'result'     => 'in_progress',
                'started_at' => now(),
            ]);

            ActivityLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'create_student',
                'entity_type' => 'student',
                'entity_id'   => $student->id,
                'details'     => "Admin created student roll_no={$request->roll_no} name={$request->name}",
                'created_at'  => now(),
            ]);
        });

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
            'email'         => 'nullable|email|max:255|unique:users,email,' . $student->user_id . '|unique:students,email,' . $student->id,
        ]);

        $updateData = [
            'name'          => $request->name,
            'roll_no'       => $request->roll_no,
            'department_id' => $request->department_id,
            'semester'      => $request->semester,
        ];

        if ($request->filled('email')) {
            $updateData['email'] = strtolower(trim($request->email));
            if ($student->user_id) {
                User::where('id', $student->user_id)->update(['email' => $updateData['email']]);
            }
        }

        $student->update($updateData);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $userId  = $student->user_id;
        $rollNo  = $student->roll_no;
        $adminId = auth()->id();

        DB::transaction(function () use ($student, $userId, $rollNo, $adminId) {
            // Delete child records first (foreign key order matters)
            $student->studentCourseRegistrations()->delete();
            $student->delete();

            if ($userId) {
                // Preserve ActivityLog records — deleting them would erase the audit trail.
                // Nullify user_id on logs if the FK is nullable, otherwise leave as-is.
                // User record is removed last.
                User::destroy($userId);
            }

            // Log the deletion itself so the action is traceable
            ActivityLog::create([
                'user_id'     => $adminId,
                'action'      => 'delete_student',
                'entity_type' => 'student',
                'entity_id'   => $student->id,
                'details'     => "Admin deleted student roll_no={$rollNo}",
                'created_at'  => now(),
            ]);
        });

        return redirect()->route('admin.students.index')
            ->with('success', 'Student deleted successfully.');
    }
}
