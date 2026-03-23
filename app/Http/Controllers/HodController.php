<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hod;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Department;
use App\Models\Conflict;
use App\Models\ActivityLog;
use App\Models\Timetable;
use Illuminate\Support\Facades\Hash;

class HodController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $hods   = Hod::with(['teacher', 'department'])
            ->when($search, fn($q) => $q->whereHas('teacher', fn($t) => $t
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
            ))
            ->paginate(10)
            ->withQueryString();
        $departments        = Department::all();
        $assignedDeptIds    = Hod::pluck('department_id')->toArray();
        return view('admin.hods.index', compact('hods', 'departments', 'assignedDeptIds'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'department_id' => 'required|exists:departments,id|unique:hods,department_id',
            'status'        => 'required|in:active,inactive',
        ], [
            'department_id.unique' => 'This department already has an HOD assigned.',
        ]);

        $user = User::create([
            'username' => strtolower(str_replace(' ', '.', $request->name)) . rand(10, 99),
            'email'    => $request->email,
            'password' => Hash::make('password'),
            'role'     => 'hod',
            'status'   => $request->status,
        ]);

        $empId = 'HOD' . str_pad($user->id, 3, '0', STR_PAD_LEFT);

        $teacher = Teacher::create([
            'user_id'       => $user->id,
            'employee_id'   => $empId,
            'name'          => $request->name,
            'email'         => $request->email,
            'department_id' => $request->department_id,
            'status'        => $request->status,
            'created_at'    => now(),
        ]);

        Hod::create([
            'user_id'        => $user->id,
            'teacher_id'     => $teacher->id,
            'department_id'  => $request->department_id,
            'appointed_date' => now(),
            'status'         => $request->status,
            'created_at'     => now(),
        ]);

        return redirect()->route('admin.hods.index')
            ->with('success', 'HOD added successfully. Default password: password');
    }

    public function update(Request $request, Hod $hod)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $hod->user_id,
            'department_id' => 'required|exists:departments,id|unique:hods,department_id,' . $hod->id,
            'status'        => 'required|in:active,inactive',
        ], [
            'department_id.unique' => 'This department already has an HOD assigned.',
        ]);

        if ($hod->user) {
            $hod->user->update(['email' => $request->email, 'status' => $request->status]);
        }

        if ($hod->teacher) {
            $hod->teacher->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'department_id' => $request->department_id,
                'status'        => $request->status,
            ]);
        }

        $hod->update([
            'department_id' => $request->department_id,
            'status'        => $request->status,
        ]);

        return redirect()->route('admin.hods.index')
            ->with('success', 'HOD updated successfully.');
    }

    public function destroy(Hod $hod)
    {
        $userId  = $hod->user_id;
        $teacher = $hod->teacher;

        // Delete child records of the linked teacher first
        if ($teacher) {
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
        }

        $hod->delete();
        if ($teacher) $teacher->delete();
        if ($userId) {
            // timetables.generated_by references users.id — nullify before deleting user
            Timetable::where('generated_by', $userId)->update(['generated_by' => null]);
            ActivityLog::where('user_id', $userId)->delete();
            User::destroy($userId);
        }

        return redirect()->route('admin.hods.index')
            ->with('success', 'HOD removed successfully.');
    }
}
