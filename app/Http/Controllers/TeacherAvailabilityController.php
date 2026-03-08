<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherAvailability;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;

class TeacherAvailabilityController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'days'               => 'required|array|min:1',
            'days.*'             => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time'         => 'required|date_format:H:i',
            'end_time'           => 'required|date_format:H:i|after:start_time',
            'max_hours_per_week' => 'nullable|integer|min:1|max:40',
        ], [
            'days.required' => 'Please select at least one day.',
            'days.min'      => 'Please select at least one day.',
        ]);

        $teacher = Teacher::where('user_id', Auth::id())->first();
        if (!$teacher) {
            return back()->with('error', 'Teacher record not found.');
        }

        $term = now()->month <= 6 ? 'spring' : 'fall';
        $year = now()->year;

        foreach ($request->days as $day) {
            TeacherAvailability::create([
                'teacher_id'         => $teacher->id,
                'term'               => $term,
                'year'               => $year,
                'day_of_week'        => $day,
                'start_time'         => $request->start_time . ':00',
                'end_time'           => $request->end_time . ':00',
                'max_hours_per_week' => $request->max_hours_per_week ?? 20,
                'created_at'         => now(),
            ]);
        }

        $count = count($request->days);
        return back()->with('success', "Availability added for {$count} day(s) successfully.");
    }

    public function destroy(TeacherAvailability $teacherAvailability)
    {
        $teacher = Teacher::where('user_id', Auth::id())->first();
        if (!$teacher || $teacherAvailability->teacher_id !== $teacher->id) {
            abort(403, 'Unauthorized action.');
        }

        $teacherAvailability->delete();
        return back()->with('success', 'Availability slot removed.');
    }
}
