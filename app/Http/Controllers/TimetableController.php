<?php

namespace App\Http\Controllers;

use App\Models\Conflict;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseSection;
use App\Models\Hod;
use App\Models\Room;
use App\Models\RoomAvailability;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    private $timeSlots = [
        ['start' => '08:00:00', 'end' => '09:30:00'],  // break 09:30–09:40
        ['start' => '09:40:00', 'end' => '11:10:00'],  // break 11:10–11:20
        ['start' => '11:20:00', 'end' => '12:50:00'],  // lunch 12:50–13:50
        ['start' => '13:50:00', 'end' => '15:20:00'],  // break 15:20–15:30
        ['start' => '15:30:00', 'end' => '17:00:00'],
    ];

    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    public function generate(Request $request)
    {
        $request->validate([
            'term'          => 'required|string|in:Fall,Winter,Summer',
            'year'          => 'required|integer|min:2024|max:2030',
            'semester'      => 'required|integer|in:1,2,3,4,5,6,7,8',
            'department_id' => 'required|integer|exists:departments,id',
            'course_id'     => 'nullable|integer|exists:courses,id',
        ]);

        $hod = Hod::where('user_id', Auth::id())->first();
        if (!$hod) {
            return back()->with('error', 'HOD record not found for your account.');
        }

        $departmentId = (int) $request->department_id;
        $term         = $request->term;
        $year         = (int) $request->year;
        $semester     = (int) $request->semester;
        $courseId     = $request->course_id ? (int) $request->course_id : null;

        try {
            return DB::transaction(function () use ($departmentId, $term, $year, $semester, $courseId) {

                // ── Step 1: Fetch courses ────────────────────────────────────────
                $courseQuery = Course::where('department_id', $departmentId)
                    ->where('semester', $semester)
                    ->where('status', 'active');

                if ($courseId) {
                    $courseQuery->where('id', $courseId);
                }

                $courses = $courseQuery->get();

                if ($courses->isEmpty()) {
                    return back()->with('error',
                        "No active courses found for Semester {$semester} in the selected department. " .
                        "Please add courses first.");
                }

                // ── Step 2: Fetch active teachers in the department ──────────────
                $teachers = Teacher::where('department_id', $departmentId)
                    ->where('status', 'active')
                    ->get();

                if ($teachers->isEmpty()) {
                    return back()->with('error',
                        "No active teachers found in this department. " .
                        "Please add teachers before generating a timetable.");
                }

                // ── Step 3: Ensure course sections + assignments exist ────────────
                $assignments  = collect();
                $teacherIndex = 0;

                foreach ($courses as $course) {
                    // Find or create one section for this term/year
                    $section = CourseSection::firstOrCreate(
                        [
                            'course_id'      => $course->id,
                            'term'           => $term,
                            'year'           => $year,
                            'section_number' => 1,
                        ],
                        [
                            'max_students'      => 60,
                            'enrolled_students' => 30,
                        ]
                    );

                    // Determine components based on actual course type in DB
                    $components = match ($course->type) {
                        'lab'         => ['lab'],
                        'lecture_lab' => ['theory', 'lab'],
                        default       => ['theory'], // lecture, theory, hybrid, etc.
                    };

                    foreach ($components as $component) {
                        // Reuse existing assignment or auto-create one
                        $assignment = CourseAssignment::where('course_section_id', $section->id)
                            ->where('component', $component)
                            ->first();

                        if (!$assignment) {
                            $teacher    = $teachers[$teacherIndex % $teachers->count()];
                            $teacherIndex++;

                            $assignment = CourseAssignment::create([
                                'course_section_id' => $section->id,
                                'teacher_id'        => $teacher->id,
                                'component'         => $component,
                            ]);
                        }

                        $assignment->load(['courseSection.course', 'teacher']);
                        $assignments->push($assignment);
                    }
                }

                // ── Step 4: Remove existing draft, archive active ────────────────
                Timetable::where('department_id', $departmentId)
                    ->where('term', $term)
                    ->where('year', $year)
                    ->where('semester', $semester)
                    ->where('status', 'draft')
                    ->each(function ($tt) {
                        $tt->timetableSlots()->delete();
                        $tt->conflicts()->delete();
                        $tt->delete();
                    });

                Timetable::where('department_id', $departmentId)
                    ->where('term', $term)
                    ->where('year', $year)
                    ->where('semester', $semester)
                    ->where('status', 'active')
                    ->update(['status' => 'archived']);

                $timetable = Timetable::create([
                    'department_id' => $departmentId,
                    'term'          => $term,
                    'year'          => $year,
                    'semester'      => $semester,
                    'status'        => 'draft',
                    'generated_by'  => Auth::id(),
                    'generated_at'  => now(),
                    'conflicts_count' => 0,
                ]);

                // ── Step 5: Get usable rooms ────────────────────────────────────
                $rooms = Room::whereIn('status', ['active', 'available'])
                    ->orderBy('capacity', 'desc')
                    ->get();

                if ($rooms->isEmpty()) {
                    return redirect()->route('hod.generate-timetable')
                        ->with('warning',
                            "Timetable record created but no available rooms found. " .
                            "Add rooms (status = available) to schedule slots.");
                }

                // ── Step 6: Build availability lookups ──────────────────────────
                $teacherAvail = $this->buildTeacherAvailability($assignments, $term, $year);
                $roomAvail    = $this->buildRoomAvailability($rooms);

                // ── Step 7: Schedule each assignment ────────────────────────────
                // Larger classes get first pick of rooms
                $assignments   = $assignments->sortByDesc(
                    fn($a) => $a->courseSection->enrolled_students ?? 0
                );

                $teacherSchedule = [];
                $roomSchedule    = [];
                $unscheduled     = [];

                foreach ($assignments as $assignment) {
                    $teacherId       = $assignment->teacher_id;
                    $courseSectionId = $assignment->course_section_id;
                    $component       = $assignment->component;
                    $enrolled        = $assignment->courseSection->enrolled_students ?? 30;
                    $courseType      = $assignment->courseSection->course->type ?? 'theory';
                    $placed          = false;

                    foreach ($this->days as $day) {
                        if ($placed) break;

                        foreach ($this->timeSlots as $slot) {
                            if ($placed) break;

                            // Teacher already placed at this day+time?
                            if (isset($teacherSchedule[$teacherId][$day][$slot['start']])) {
                                continue;
                            }

                            // Teacher availability window check
                            if (!$this->isTeacherAvailable($teacherAvail, $teacherId, $day, $slot)) {
                                continue;
                            }

                            // Find a suitable room
                            foreach ($rooms as $room) {
                                // Capacity
                                if ($room->capacity < $enrolled) {
                                    continue;
                                }

                                // Room type must match course/component type
                                if (!$this->roomMatchesCourseType($room->type, $component, $courseType)) {
                                    continue;
                                }

                                // Room not double-booked
                                if (isset($roomSchedule[$room->id][$day][$slot['start']])) {
                                    continue;
                                }

                                // Room availability window check
                                if (!$this->isRoomAvailable($roomAvail, $room->id, $day, $slot)) {
                                    continue;
                                }

                                // ✔ All checks passed — create slot
                                TimetableSlot::create([
                                    'timetable_id'     => $timetable->id,
                                    'course_section_id' => $courseSectionId,
                                    'teacher_id'       => $teacherId,
                                    'room_id'          => $room->id,
                                    'day_of_week'      => $day,
                                    'start_time'       => $slot['start'],
                                    'end_time'         => $slot['end'],
                                    'component'        => $component,
                                    'created_at'       => now(),
                                ]);

                                $teacherSchedule[$teacherId][$day][$slot['start']] = true;
                                $roomSchedule[$room->id][$day][$slot['start']]     = true;
                                $placed = true;
                                break;
                            }
                        }
                    }

                    if (!$placed) {
                        $unscheduled[] = $assignment;
                    }
                }

                // ── Step 8: Detect conflicts ────────────────────────────────────
                $conflictCount = $this->detectConflicts($timetable);
                $timetable->update(['conflicts_count' => $conflictCount]);

                $total     = $assignments->count();
                $scheduled = $total - count($unscheduled);

                $msg = "Timetable generated! {$scheduled} of {$total} slots scheduled.";
                if (count($unscheduled) > 0) {
                    $msg .= " " . count($unscheduled) . " could not be placed — check room availability or add more rooms.";
                }
                if ($conflictCount > 0) {
                    $msg .= " {$conflictCount} conflict(s) detected.";
                }

                return redirect()->route('hod.generate-timetable')->with('success', $msg);
            });

        } catch (\Exception $ex) {
            return back()->with('error', 'Generation failed: ' . $ex->getMessage());
        }
    }

    public function activate(Timetable $timetable)
    {
        abort_if($timetable->generated_by !== Auth::id(), 403);

        Timetable::where('department_id', $timetable->department_id)
            ->where('term', $timetable->term)
            ->where('year', $timetable->year)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $timetable->update(['status' => 'active']);

        return redirect()->route('hod.generate-timetable')
            ->with('success', 'Timetable activated successfully!');
    }

    public function deactivate(Timetable $timetable)
    {
        abort_if($timetable->generated_by !== Auth::id(), 403);
        abort_if($timetable->status !== 'active', 422, 'Only active timetables can be deactivated.');

        $timetable->update(['status' => 'draft']);

        return redirect()->route('hod.generate-timetable')
            ->with('success', 'Timetable deactivated and moved back to draft.');
    }

    public function destroy(Timetable $timetable)
    {
        abort_if($timetable->generated_by !== Auth::id(), 403);
        abort_if($timetable->status === 'active', 403, 'Cannot delete an active timetable.');

        $timetable->timetableSlots()->delete();
        $timetable->conflicts()->delete();
        $timetable->delete();

        return redirect()->route('hod.generate-timetable')
            ->with('success', 'Timetable deleted.');
    }

    // ── Room type matching ──────────────────────────────────────────────────────

    private function roomMatchesCourseType(string $roomType, string $component, string $courseType): bool
    {
        // Lab component → lab or hybrid room
        if ($component === 'lab') {
            return in_array($roomType, ['lab', 'hybrid']);
        }

        // Theory component → lecture, classroom, seminar, seminar_hall, or hybrid
        return in_array($roomType, ['lecture', 'classroom', 'seminar', 'seminar_hall', 'hybrid']);
    }

    // ── Availability builders ───────────────────────────────────────────────────

    private function buildTeacherAvailability($assignments, $term, $year)
    {
        $teacherIds    = $assignments->pluck('teacher_id')->unique();
        $availabilities = TeacherAvailability::whereIn('teacher_id', $teacherIds)
            ->where('term', $term)
            ->where('year', $year)
            ->get();

        $lookup = [];
        foreach ($availabilities as $avail) {
            $lookup[$avail->teacher_id][$avail->day_of_week][] = [
                'start' => $avail->start_time,
                'end'   => $avail->end_time,
            ];
        }

        return $lookup;
    }

    private function buildRoomAvailability($rooms)
    {
        $roomIds        = $rooms->pluck('id');
        $availabilities = RoomAvailability::whereIn('room_id', $roomIds)
            ->whereIn('status', ['active', 'available'])
            ->get();

        $lookup = [];
        foreach ($availabilities as $avail) {
            $lookup[$avail->room_id][$avail->day_of_week][] = [
                'start' => $avail->start_time,
                'end'   => $avail->end_time,
            ];
        }

        return $lookup;
    }

    // ── Availability checkers ───────────────────────────────────────────────────

    private function isTeacherAvailable($teacherAvail, $teacherId, $day, $slot)
    {
        // No records at all → treat as always available
        if (!isset($teacherAvail[$teacherId])) {
            return true;
        }

        if (!isset($teacherAvail[$teacherId][$day])) {
            return false;
        }

        foreach ($teacherAvail[$teacherId][$day] as $window) {
            if ($window['start'] <= $slot['start'] && $window['end'] >= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    private function isRoomAvailable($roomAvail, $roomId, $day, $slot)
    {
        // No records at all → treat as always available
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

    // ── Conflict detection ──────────────────────────────────────────────────────

    private function detectConflicts(Timetable $timetable)
    {
        $slots = TimetableSlot::where('timetable_id', $timetable->id)
            ->with(['courseSection.course', 'teacher', 'room'])
            ->get();

        $conflictCount = 0;

        // Group by day + start_time
        $grouped = $slots->groupBy(fn($s) => $s->day_of_week . '_' . $s->start_time);

        foreach ($grouped as $_key => $groupSlots) {
            if ($groupSlots->count() < 2) {
                continue;
            }

            // Teacher double-booking
            $byTeacher = $groupSlots->groupBy('teacher_id');
            foreach ($byTeacher as $teacherSlots) {
                if ($teacherSlots->count() < 2) continue;

                $first  = $teacherSlots->first();
                $second = $teacherSlots->skip(1)->first();

                Conflict::create([
                    'timetable_id'  => $timetable->id,
                    'conflict_type' => 'teacher_conflict',
                    'description'   => "Teacher {$first->teacher->name} is double-booked on {$first->day_of_week} at {$first->start_time}",
                    'slot_id_1'     => $first->id,
                    'slot_id_2'     => $second->id,
                    'status'        => 'unresolved',
                    'detected_at'   => now(),
                ]);
                $conflictCount++;
            }

            // Room double-booking
            $byRoom = $groupSlots->groupBy('room_id');
            foreach ($byRoom as $roomSlots) {
                if ($roomSlots->count() < 2) continue;

                $first  = $roomSlots->first();
                $second = $roomSlots->skip(1)->first();

                Conflict::create([
                    'timetable_id'  => $timetable->id,
                    'conflict_type' => 'room_conflict',
                    'description'   => "Room {$first->room->room_number} is double-booked on {$first->day_of_week} at {$first->start_time}",
                    'slot_id_1'     => $first->id,
                    'slot_id_2'     => $second->id,
                    'status'        => 'unresolved',
                    'detected_at'   => now(),
                ]);
                $conflictCount++;
            }
        }

        return $conflictCount;
    }
}
