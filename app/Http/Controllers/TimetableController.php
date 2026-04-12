<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Conflict;
use App\Models\Hod;
use App\Models\Room;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Services\TimetableConstraintService;
use App\Services\TimetableConflictService;
use App\Services\TimetableGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * TimetableController
 *
 * Thin HTTP layer — all scheduling logic lives in the service layer.
 *
 * Responsibilities:
 *   - Validate request input
 *   - Verify HOD owns the requested department
 *   - Delegate to TimetableGenerationService for draft creation
 *   - Provide activate / deactivate / destroy for timetable lifecycle
 *   - Provide updateSlot / destroySlot for manual slot editing with full
 *     constraint validation and audit logging
 */
class TimetableController extends Controller
{
    public function __construct(
        private TimetableGenerationService $generationService,
        private TimetableConstraintService $constraintService,
        private TimetableConflictService   $conflictService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Timetable generation
    // ──────────────────────────────────────────────────────────────────────────

    public function generate(Request $request)
    {
        $request->validate([
            'term'          => 'required|string|in:Fall,Winter,Summer',
            'year'          => 'required|integer|min:2024|max:2030',
            'semester'      => 'required|integer|in:1,2,3,4,5,6,7,8',
            'department_id' => 'required|integer|exists:departments,id',
            'course_id'     => 'nullable|integer|exists:courses,id',
        ]);

        // ── HOD owns the requested department ─────────────────────────────────
        $hod = Hod::where('user_id', Auth::id())->first();
        if (!$hod) {
            return back()->with('error', 'HOD record not found for your account.');
        }

        $departmentId = (int) $request->department_id;

        if ($hod->department_id !== $departmentId) {
            return back()->with('error', 'You can only generate timetables for your own department.');
        }

        try {
            $result = DB::transaction(function () use ($request, $departmentId, $hod) {
                return $this->generationService->generate(
                    departmentId: $departmentId,
                    term:         $request->term,
                    year:         (int) $request->year,
                    semester:     (int) $request->semester,
                    generatedBy:  Auth::id(),
                    courseId:     $request->course_id ? (int) $request->course_id : null,
                );
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Generation failed: ' . $e->getMessage());
        }

        // ── Build feedback message ─────────────────────────────────────────────
        if ($result['timetable'] === null) {
            // earlyExit — no timetable created
            return back()->with('error', implode(' ', $result['messages']));
        }

        $msg = "Timetable draft generated: {$result['scheduled']} session(s) scheduled.";

        if ($result['unscheduled'] > 0) {
            $msg .= " {$result['unscheduled']} session(s) could not be placed — review Conflicts page.";
        }

        if ($result['conflicts'] > 0) {
            $msg .= " {$result['conflicts']} conflict(s) detected.";
        }

        foreach ($result['messages'] as $warning) {
            $msg .= " ⚠ {$warning}";
        }

        $level = ($result['unscheduled'] > 0 || $result['conflicts'] > 0) ? 'warning' : 'success';

        return redirect()->route('hod.generate-timetable')->with($level, $msg);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Timetable lifecycle
    // ──────────────────────────────────────────────────────────────────────────

    public function activate(Timetable $timetable)
    {
        $this->authoriseTimetable($timetable);

        // Archive only the previously-active timetable for the SAME semester
        // (other semesters' active timetables stay active — rooms/teachers are shared)
        Timetable::where('department_id', $timetable->department_id)
            ->where('term', $timetable->term)
            ->where('year', $timetable->year)
            ->where('semester', $timetable->semester)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $timetable->update(['status' => 'active']);

        // Check for room/teacher conflicts with other semesters' active timetables
        // (all semesters share the same physical rooms and teachers)
        $crossConflicts = $this->conflictService->detectCrossTimetableConflicts($timetable);
        if ($crossConflicts > 0) {
            $timetable->update(['conflicts_count' => $timetable->conflicts()->count()]);
        }

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'activate_timetable',
            'entity_type' => 'timetable',
            'entity_id'   => $timetable->id,
            'details'     => "Activated timetable ID {$timetable->id}" .
                             ($crossConflicts > 0 ? " — {$crossConflicts} cross-semester conflict(s) detected" : ''),
            'created_at'  => now(),
        ]);

        $msg = 'Timetable activated.';
        if ($crossConflicts > 0) {
            $msg .= " Warning: {$crossConflicts} room/teacher conflict(s) found with other active semester timetables. Review the Conflicts page.";
        }

        return redirect()->route('hod.generate-timetable')
            ->with($crossConflicts > 0 ? 'warning' : 'success', $msg);
    }

    public function deactivate(Timetable $timetable)
    {
        $this->authoriseTimetable($timetable);
        abort_if($timetable->status !== 'active', 422, 'Only active timetables can be deactivated.');

        $timetable->update(['status' => 'draft']);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'deactivate_timetable',
            'entity_type' => 'timetable',
            'entity_id'   => $timetable->id,
            'details'     => "Deactivated timetable ID {$timetable->id} → draft",
            'created_at'  => now(),
        ]);

        return redirect()->route('hod.generate-timetable')
            ->with('success', 'Timetable deactivated and moved back to draft.');
    }

    public function destroy(Timetable $timetable)
    {
        $this->authoriseTimetable($timetable);
        abort_if($timetable->status === 'active', 403, 'Cannot delete an active timetable. Deactivate it first.');

        DB::transaction(function () use ($timetable) {
            $timetable->conflicts()->delete();
            $timetable->timetableSlots()->delete();
            $timetable->delete();
        });

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'delete_timetable',
            'entity_type' => 'timetable',
            'entity_id'   => $timetable->id,
            'details'     => "Deleted timetable ID {$timetable->id}",
            'created_at'  => now(),
        ]);

        return redirect()->route('hod.generate-timetable')
            ->with('success', 'Timetable deleted.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Manual slot editing
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Show the edit form for a single slot (used by HOD to manually reschedule).
     */
    public function editSlot(TimetableSlot $slot)
    {
        $this->authoriseSlot($slot);

        $slot->load(['timetable', 'courseSection.course', 'teacher', 'room']);

        $rooms = Room::where('status', 'available')->orderBy('room_number')->get();

        $days      = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $timeSlots = [
            ['start' => '08:00:00', 'end' => '09:30:00'],
            ['start' => '09:40:00', 'end' => '11:10:00'],
            ['start' => '11:20:00', 'end' => '12:50:00'],
            ['start' => '13:50:00', 'end' => '15:20:00'],
            ['start' => '15:30:00', 'end' => '17:00:00'],
        ];

        return view('hod.edit_slot', compact('slot', 'rooms', 'days', 'timeSlots'));
    }

    /**
     * Apply a manual reschedule to a single slot.
     *
     * Validates all hard constraints with the current slot excluded so that
     * we do not conflict with the booking we are replacing.  Saves only if
     * all checks pass; otherwise returns validation errors to the HOD.
     */
    public function updateSlot(Request $request, TimetableSlot $slot)
    {
        $this->authoriseSlot($slot);

        $request->validate([
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time'  => 'required|date_format:H:i:s',
            'end_time'    => 'required|date_format:H:i:s|after:start_time',
            'room_id'     => 'required|integer|exists:rooms,id',
        ]);

        $slot->loadMissing(['timetable', 'courseSection.course', 'teacher']);

        $timetable   = $slot->timetable;
        $term        = $timetable->term;
        $year        = $timetable->year;
        $day         = $request->day_of_week;
        $startTime   = $request->start_time;
        $endTime     = $request->end_time;
        $roomId      = (int) $request->room_id;
        $teacherId   = $slot->teacher_id;
        $sectionId   = $slot->course_section_id;
        $component   = $slot->component;
        $enrolled    = (int) ($slot->courseSection->enrolled_students ?? 0);

        $errors = [];

        // ── Teacher availability ───────────────────────────────────────────────
        if (!$this->constraintService->isTeacherAvailableForSlot($teacherId, $day, $startTime, $endTime, $term, $year)) {
            $errors[] = 'Teacher is not available in that time window.';
        }

        // ── Teacher overlap (exclude this slot) ───────────────────────────────
        if ($this->constraintService->teacherHasOverlap($teacherId, $day, $startTime, $endTime, $term, $year, $timetable->id, $slot->id)) {
            $errors[] = 'Teacher already has a class at the new time.';
        }

        // ── Teacher 6-hour daily limit (exclude this slot so old booking isn't double-counted) ──
        if ($this->constraintService->teacherExceedsDailyHours($teacherId, $day, $startTime, $endTime, $term, $year, $timetable->id, $slot->id)) {
            $errors[] = 'Teacher exceeds 6-hour daily limit.';
        }

        // ── Section overlap ───────────────────────────────────────────────────
        if ($this->constraintService->sectionHasOverlap($sectionId, $day, $startTime, $endTime, $term, $year, $timetable->id, $slot->id)) {
            $errors[] = 'This section already has a class at the new time.';
        }

        // ── Student cross-section overlap ─────────────────────────────────────
        if ($this->constraintService->studentsHaveOverlap($sectionId, $day, $startTime, $endTime, $term, $year, $timetable->id, $slot->id)) {
            $errors[] = 'One or more enrolled students would have a class conflict at the new time.';
        }

        // ── Room validation ───────────────────────────────────────────────────
        $room = Room::find($roomId);
        if (!$room) {
            $errors[] = 'Selected room does not exist.';
        } else {
            if ($room->capacity < $enrolled) {
                $errors[] = "Room {$room->room_number} capacity ({$room->capacity}) is too small for {$enrolled} enrolled students.";
            }

            if (!$this->constraintService->roomMatchesCourseType($room->type, $component)) {
                $errors[] = "Room type '{$room->type}' does not match component '{$component}'.";
            }

            if (!$this->constraintService->isRoomAvailableForSlot($roomId, $day, $startTime, $endTime)) {
                $errors[] = "Room {$room->room_number} is not available in that time window.";
            }

            if ($this->constraintService->roomHasOverlap($roomId, $day, $startTime, $endTime, $term, $year, $timetable->id, $slot->id)) {
                $errors[] = "Room {$room->room_number} is already booked at the new time.";
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // ── All checks pass: apply the change ────────────────────────────────
        $oldDetails = "day={$slot->day_of_week} {$slot->start_time}–{$slot->end_time} room={$slot->room_id}";

        $slot->update([
            'day_of_week' => $day,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'room_id'     => $roomId,
        ]);

        // Remove any old conflicts linked to this slot and re-run the scan
        Conflict::where('slot_id_1', $slot->id)
            ->orWhere('slot_id_2', $slot->id)
            ->delete();

        $timetable->load('timetableSlots');
        $newConflicts = $this->conflictService->detectAndRecordConflicts($timetable);
        $timetable->update(['conflicts_count' => $timetable->conflicts()->count()]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'update_timetable_slot',
            'entity_type' => 'timetable_slot',
            'entity_id'   => $slot->id,
            'details'     => "Manual reschedule: {$oldDetails} → day={$day} {$startTime}–{$endTime} room={$roomId}",
            'created_at'  => now(),
        ]);

        return redirect()->route('hod.department-timetable', ['timetable_id' => $timetable->id])
            ->with('success', 'Slot rescheduled successfully.');
    }

    /**
     * Delete a single timetable slot (manual removal by HOD).
     *
     * Cleans up any conflicts referencing the slot and updates the count.
     */
    public function destroySlot(TimetableSlot $slot)
    {
        $this->authoriseSlot($slot);

        $slot->loadMissing('timetable');
        $timetable = $slot->timetable;

        DB::transaction(function () use ($slot, $timetable) {
            Conflict::where('slot_id_1', $slot->id)
                ->orWhere('slot_id_2', $slot->id)
                ->delete();

            $slot->delete();

            $timetable->update(['conflicts_count' => $timetable->conflicts()->count()]);
        });

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'delete_timetable_slot',
            'entity_type' => 'timetable_slot',
            'entity_id'   => $slot->id,
            'details'     => "Deleted slot from timetable ID {$timetable->id}",
            'created_at'  => now(),
        ]);

        return redirect()->route('hod.department-timetable', ['timetable_id' => $timetable->id])
            ->with('success', 'Slot removed from the timetable.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Authorisation helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Abort with 403 if the current HOD does not own the timetable's department.
     */
    private function authoriseTimetable(Timetable $timetable): void
    {
        $hod = Hod::where('user_id', Auth::id())->first();

        abort_if(
            !$hod || $hod->department_id !== $timetable->department_id,
            403,
            'You do not have permission to manage this timetable.'
        );
    }

    /**
     * Abort with 403 if the current HOD does not own the slot's timetable's department.
     */
    private function authoriseSlot(TimetableSlot $slot): void
    {
        $slot->loadMissing('timetable');
        $this->authoriseTimetable($slot->timetable);
    }
}
