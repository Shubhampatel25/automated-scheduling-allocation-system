<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Hod;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseAssignment;
use App\Models\StudentCourseRegistration;
use App\Models\TimetableSlot;
use App\Models\Conflict;
use App\Models\ActivityLog;
use App\Models\Timetable;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->get('search');
        $departments = Department::withCount(['teachers', 'courses', 'students'])
            ->when($search, fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
            )
            ->paginate(10)
            ->withQueryString();
        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:departments,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        Department::create([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'description' => $request->description,
            'created_at'  => now(),
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department added successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:departments,code,' . $department->id,
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $department->update([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        // --- 1. Teachers: availability → slots → conflicts → assignments → hods → teachers → users ---
        $teacherIds = $department->teachers()->pluck('id');
        if ($teacherIds->isNotEmpty()) {
            TeacherAvailability::whereIn('teacher_id', $teacherIds)->delete();

            $teacherSlotIds = TimetableSlot::whereIn('teacher_id', $teacherIds)->pluck('id');
            if ($teacherSlotIds->isNotEmpty()) {
                Conflict::whereIn('slot_id_1', $teacherSlotIds)
                        ->orWhereIn('slot_id_2', $teacherSlotIds)
                        ->delete();
                TimetableSlot::whereIn('teacher_id', $teacherIds)->delete();
            }

            CourseAssignment::whereIn('teacher_id', $teacherIds)->delete();
        }

        // HODs share the same user as their teacher record — delete hods before teachers
        $department->hods()->delete();

        $teacherUserIds = $department->teachers()->pluck('user_id')->filter();
        $department->teachers()->delete();
        // timetables.generated_by references users.id — nullify before deleting users
        Timetable::whereIn('generated_by', $teacherUserIds)->update(['generated_by' => null]);
        ActivityLog::whereIn('user_id', $teacherUserIds)->delete();
        User::whereIn('id', $teacherUserIds)->delete();

        // --- 2. Courses: sections → (assignments, registrations, slots → conflicts) → courses ---
        $courseIds = $department->courses()->pluck('id');
        if ($courseIds->isNotEmpty()) {
            $sectionIds = CourseSection::whereIn('course_id', $courseIds)->pluck('id');
            if ($sectionIds->isNotEmpty()) {
                $sectionSlotIds = TimetableSlot::whereIn('course_section_id', $sectionIds)->pluck('id');
                if ($sectionSlotIds->isNotEmpty()) {
                    Conflict::whereIn('slot_id_1', $sectionSlotIds)
                            ->orWhereIn('slot_id_2', $sectionSlotIds)
                            ->delete();
                    TimetableSlot::whereIn('course_section_id', $sectionIds)->delete();
                }
                CourseAssignment::whereIn('course_section_id', $sectionIds)->delete();
                StudentCourseRegistration::whereIn('course_section_id', $sectionIds)->delete();
                CourseSection::whereIn('id', $sectionIds)->delete();
            }
            $department->courses()->delete();
        }

        // --- 3. Students: registrations → students → users ---
        $students = $department->students()->get();
        if ($students->isNotEmpty()) {
            $studentIds     = $students->pluck('id');
            $studentUserIds = $students->pluck('user_id')->filter();
            StudentCourseRegistration::whereIn('student_id', $studentIds)->delete();
            $department->students()->delete();
            ActivityLog::whereIn('user_id', $studentUserIds)->delete();
            User::whereIn('id', $studentUserIds)->delete();
        }

        // --- 4. Timetables: conflicts (by timetable_id) → slots → timetables ---
        $timetableIds = $department->timetables()->pluck('id');
        if ($timetableIds->isNotEmpty()) {
            Conflict::whereIn('timetable_id', $timetableIds)->delete();
            TimetableSlot::whereIn('timetable_id', $timetableIds)->delete();
            $department->timetables()->delete();
        }

        $department->delete();
        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
