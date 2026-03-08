<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Department;
use App\Models\Hod;
use App\Models\Conflict;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $teachers = Teacher::with('department')
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
            )
            ->paginate(10)
            ->withQueryString();
        $departments = Department::all();
        return view('admin.teachers.index', compact('teachers', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'department_id' => 'required|exists:departments,id',
            'status'        => 'required|in:active,inactive',
        ]);

        $username = strtolower(str_replace(' ', '.', $request->name)) . rand(100, 999);

        $user = User::create([
            'username' => $username,
            'email'    => $request->email,
            'password' => Hash::make('password'),
            'role'     => 'professor',
            'status'   => $request->status,
        ]);

        $empId = 'EMP' . str_pad($user->id, 4, '0', STR_PAD_LEFT);

        Teacher::create([
            'user_id'       => $user->id,
            'employee_id'   => $empId,
            'name'          => $request->name,
            'email'         => $request->email,
            'department_id' => $request->department_id,
            'status'        => $request->status,
            'created_at'    => now(),
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher added. Default password: password');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $teacher->user_id,
            'department_id' => 'required|exists:departments,id',
            'status'        => 'required|in:active,inactive',
        ]);

        if ($teacher->user) {
            $teacher->user->update(['email' => $request->email, 'status' => $request->status]);
        }

        $teacher->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'department_id' => $request->department_id,
            'status'        => $request->status,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $userId = $teacher->user_id;

        // Delete child records first (foreign key order matters)
        $teacher->teacherAvailabilities()->delete();

        // conflicts references timetable_slots via slot_id_1 and slot_id_2
        $slotIds = $teacher->timetableSlots()->pluck('id');
        if ($slotIds->isNotEmpty()) {
            Conflict::whereIn('slot_id_1', $slotIds)
                    ->orWhereIn('slot_id_2', $slotIds)
                    ->delete();
        }

        $teacher->timetableSlots()->delete();
        $teacher->courseAssignments()->delete();
        Hod::where('teacher_id', $teacher->id)->delete();

        $teacher->delete();
        if ($userId) {
            ActivityLog::where('user_id', $userId)->delete();
            User::destroy($userId);
        }

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }
}
