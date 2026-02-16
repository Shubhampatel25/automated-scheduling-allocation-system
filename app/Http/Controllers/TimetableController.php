<?php

namespace App\Http\Controllers;

use App\Models\Conflict;
use App\Models\CourseAssignment;
use App\Models\Hod;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Models\TeacherAvailability;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    private $timeSlots = [
        ['start' => '08:00:00', 'end' => '09:30:00'],
        ['start' => '09:30:00', 'end' => '11:00:00'],
        ['start' => '11:00:00', 'end' => '12:30:00'],
        ['start' => '13:00:00', 'end' => '14:30:00'],
        ['start' => '14:30:00', 'end' => '16:00:00'],
    ];

    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function generate(Request $request)
    {
        $request->validate([
            'term' => 'required|string|in:Fall,Winter,Summer',
            'year' => 'required|integer|min:2024|max:2030',
            'semester' => 'nullable|integer|in:1,2,3,4,5,6,7,8',
        ]);

        $hod = Hod::where('user_id', Auth::id())->first();
        if (!$hod) {
            return back()->with('error', 'HOD record not found for your account.');
        }

        $departmentId = $hod->department_id;
        $term = $request->term;
        $year = $request->year;
        $semester = $request->semester;

        try {
            return DB::transaction(function () use ($departmentId, $term, $year, $semester) {
                // Archive any existing active timetable for same dept/term/year
                Timetable::where('department_id', $departmentId)
                    ->where('term', $term)
                    ->where('year', $year)
                    ->where('status', 'active')
                    ->update(['status' => 'archived']);

                // Create new timetable record
                $timetable = Timetable::create([
                    'department_id' => $departmentId,
                    'term' => $term,
                    'year' => (int) $year,
                    'semester' => $semester,
                    'status' => 'draft',
                    'generated_by' => Auth::id(),
                    'generated_at' => now(),
                    'conflicts_count' => 0,
                ]);

                // Load course assignments for this department's sections matching term/year
                $assignments = CourseAssignment::whereHas('courseSection', function ($q) use ($departmentId, $term, $year) {
                    $q->whereHas('course', fn($q2) => $q2->where('department_id', $departmentId))
                      ->where('term', $term)
                      ->where('year', $year);
                })->with(['courseSection.course', 'teacher'])->get();

                if ($assignments->isEmpty()) {
                    return back()->with('error', "No course assignments found for {$term} {$year}. Please ensure course sections and assignments exist for this term.");
                }

                // Sort by enrolled students descending (larger classes get first pick of rooms)
                $assignments = $assignments->sortByDesc(fn($a) => $a->courseSection->enrolled_students ?? 0);

                // Load rooms
                $rooms = Room::where('status', 'available')
                    ->orderBy('capacity', 'desc')
                    ->get();

                // Build teacher availability lookup
                $teacherAvail = $this->buildTeacherAvailability($assignments, $term, $year);

                // Build room availability lookup
                $roomAvail = $this->buildRoomAvailability($rooms);

                // Scheduling tracking arrays
                $teacherSchedule = [];
                $roomSchedule = [];
                $unscheduled = [];

                foreach ($assignments as $assignment) {
                    $teacherId = $assignment->teacher_id;
                    $courseSectionId = $assignment->course_section_id;
                    $component = $assignment->component;
                    $enrolledStudents = $assignment->courseSection->enrolled_students ?? 0;
                    $placed = false;

                    foreach ($this->days as $day) {
                        if ($placed) break;

                        foreach ($this->timeSlots as $slot) {
                            if ($placed) break;

                            // Check: Teacher not already scheduled at this day+time
                            if (isset($teacherSchedule[$teacherId][$day][$slot['start']])) {
                                continue;
                            }

                            // Check: Teacher availability allows this day+time
                            if (!$this->isTeacherAvailable($teacherAvail, $teacherId, $day, $slot)) {
                                continue;
                            }

                            // Find a suitable room
                            foreach ($rooms as $room) {
                                // Room capacity check
                                if ($room->capacity < $enrolledStudents) {
                                    continue;
                                }

                                // Room not already booked
                                if (isset($roomSchedule[$room->id][$day][$slot['start']])) {
                                    continue;
                                }

                                // Room availability check
                                if (!$this->isRoomAvailable($roomAvail, $room->id, $day, $slot)) {
                                    continue;
                                }

                                // All checks passed - create the slot
                                TimetableSlot::create([
                                    'timetable_id' => $timetable->id,
                                    'course_section_id' => $courseSectionId,
                                    'teacher_id' => $teacherId,
                                    'room_id' => $room->id,
                                    'day_of_week' => $day,
                                    'start_time' => $slot['start'],
                                    'end_time' => $slot['end'],
                                    'component' => $component,
                                    'created_at' => now(),
                                ]);

                                $teacherSchedule[$teacherId][$day][$slot['start']] = true;
                                $roomSchedule[$room->id][$day][$slot['start']] = true;
                                $placed = true;
                                break;
                            }
                        }
                    }

                    if (!$placed) {
                        $unscheduled[] = $assignment;
                    }
                }

                // Run conflict detection
                $conflictCount = $this->detectConflicts($timetable);
                $timetable->update(['conflicts_count' => $conflictCount]);

                $scheduledCount = $assignments->count() - count($unscheduled);

                return redirect()->route('hod.dashboard')->with([
                    'success' => "Timetable generated successfully! {$scheduledCount} of {$assignments->count()} assignments scheduled.",
                    'scheduled' => $scheduledCount,
                    'unscheduled' => count($unscheduled),
                    'conflicts' => $conflictCount,
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate timetable. Please try again.');
        }
    }

    public function activate(Timetable $timetable)
    {
        $hod = Hod::where('user_id', Auth::id())->firstOrFail();
        abort_if($timetable->department_id !== $hod->department_id, 403);

        // Archive any currently active timetable for same dept/term/year
        Timetable::where('department_id', $timetable->department_id)
            ->where('term', $timetable->term)
            ->where('year', $timetable->year)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $timetable->update(['status' => 'active']);

        return redirect()->route('hod.dashboard')
            ->with('success', 'Timetable activated successfully!');
    }

    public function destroy(Timetable $timetable)
    {
        $hod = Hod::where('user_id', Auth::id())->firstOrFail();
        abort_if($timetable->department_id !== $hod->department_id, 403);
        abort_if($timetable->status === 'active', 403, 'Cannot delete an active timetable.');

        $timetable->timetableSlots()->delete();
        $timetable->conflicts()->delete();
        $timetable->delete();

        return redirect()->route('hod.dashboard')
            ->with('success', 'Draft timetable deleted.');
    }

    private function buildTeacherAvailability($assignments, $term, $year)
    {
        $teacherIds = $assignments->pluck('teacher_id')->unique();
        $availabilities = TeacherAvailability::whereIn('teacher_id', $teacherIds)
            ->where('term', $term)
            ->where('year', $year)
            ->get();

        $lookup = [];
        foreach ($availabilities as $avail) {
            $lookup[$avail->teacher_id][$avail->day_of_week][] = [
                'start' => $avail->start_time,
                'end' => $avail->end_time,
            ];
        }

        return $lookup;
    }

    private function buildRoomAvailability($rooms)
    {
        $roomIds = $rooms->pluck('id');
        $availabilities = RoomAvailability::whereIn('room_id', $roomIds)
            ->where('status', 'available')
            ->get();

        $lookup = [];
        foreach ($availabilities as $avail) {
            $lookup[$avail->room_id][$avail->day_of_week][] = [
                'start' => $avail->start_time,
                'end' => $avail->end_time,
            ];
        }

        return $lookup;
    }

    private function isTeacherAvailable($teacherAvail, $teacherId, $day, $slot)
    {
        // If no availability records exist, treat as available all day
        if (!isset($teacherAvail[$teacherId])) {
            return true;
        }

        // If no records for this day, teacher is unavailable
        if (!isset($teacherAvail[$teacherId][$day])) {
            return false;
        }

        // Check if any availability window covers this time slot
        foreach ($teacherAvail[$teacherId][$day] as $window) {
            if ($window['start'] <= $slot['start'] && $window['end'] >= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    private function isRoomAvailable($roomAvail, $roomId, $day, $slot)
    {
        // If no availability records exist, treat as available all day
        if (!isset($roomAvail[$roomId])) {
            return true;
        }

        if (!isset($roomAvail[$roomId][$day])) {
            return false;
        }

        foreach ($roomAvail[$roomId][$day] as $window) {
            if ($window['start'] <= $slot['start'] && $window['end'] >= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    private function detectConflicts(Timetable $timetable)
    {
        $slots = TimetableSlot::where('timetable_id', $timetable->id)
            ->with(['courseSection.course', 'teacher', 'room'])
            ->get();

        $conflictCount = 0;

        // Group slots by day + start_time
        $grouped = $slots->groupBy(fn($s) => $s->day_of_week . '_' . $s->start_time);

        foreach ($grouped as $key => $groupSlots) {
            if ($groupSlots->count() < 2) continue;

            // Check teacher conflicts
            $byTeacher = $groupSlots->groupBy('teacher_id');
            foreach ($byTeacher as $teacherId => $teacherSlots) {
                if ($teacherSlots->count() < 2) continue;

                $first = $teacherSlots->first();
                $second = $teacherSlots->skip(1)->first();

                Conflict::create([
                    'timetable_id' => $timetable->id,
                    'conflict_type' => 'teacher_conflict',
                    'description' => "Teacher {$first->teacher->name} is double-booked on {$first->day_of_week} at {$first->start_time}",
                    'slot_id_1' => $first->id,
                    'slot_id_2' => $second->id,
                    'status' => 'unresolved',
                    'detected_at' => now(),
                ]);
                $conflictCount++;
            }

            // Check room conflicts
            $byRoom = $groupSlots->groupBy('room_id');
            foreach ($byRoom as $roomId => $roomSlots) {
                if ($roomSlots->count() < 2) continue;

                $first = $roomSlots->first();
                $second = $roomSlots->skip(1)->first();

                Conflict::create([
                    'timetable_id' => $timetable->id,
                    'conflict_type' => 'room_conflict',
                    'description' => "Room {$first->room->room_number} is double-booked on {$first->day_of_week} at {$first->start_time}",
                    'slot_id_1' => $first->id,
                    'slot_id_2' => $second->id,
                    'status' => 'unresolved',
                    'detected_at' => now(),
                ]);
                $conflictCount++;
            }
        }

        return $conflictCount;
    }
}
