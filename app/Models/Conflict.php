<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Conflict model.
 *
 * conflict_type is now a plain VARCHAR(50) column (see migration
 * 2026_04_04_000001) so new types can be added without schema changes.
 *
 * Recognised types:
 *   Scheduling failures (slot_id_1 = null — no slot was created):
 *     missing_assignment
 *     no_feasible_slot
 *     no_suitable_room
 *
 *   Post-generation / manual-edit scan findings (slot_id_1 set):
 *     teacher_conflict
 *     room_conflict
 *     section_overlap
 */
class Conflict extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'timetable_id',
        'conflict_type',
        'description',
        'slot_id_1',
        'slot_id_2',
        'course_section_id',
        'status',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    /** The first (or only) slot involved in the conflict. Nullable — may be null
     *  for scheduling-failure conflicts where no slot was ever created. */
    public function slot1()
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id_1');
    }

    /** The second slot involved (e.g. the other side of a double-booking). */
    public function slot2()
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id_2');
    }

    /** The section that could not be placed, or whose slot caused the conflict. */
    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** True when this conflict has no associated timetable slot (unscheduled assignment). */
    public function isUnschedulable(): bool
    {
        return $this->slot_id_1 === null;
    }

    /** Human-readable label for the conflict type, suitable for views. */
    public function typeLabel(): string
    {
        return match ($this->conflict_type) {
            'teacher_conflict'  => 'Teacher Double-Booking',
            'room_conflict'     => 'Room Double-Booking',
            'section_overlap'   => 'Section Overlap',
            'student_overlap'   => 'Student Group Overlap',
            'capacity_exceeded' => 'Room Capacity Exceeded',
            'missing_assignment'=> 'Missing Teacher Assignment',
            'no_suitable_room'  => 'No Suitable Room Available',
            'no_feasible_slot'  => 'No Feasible Time Slot',
            default             => ucwords(str_replace('_', ' ', $this->conflict_type)),
        };
    }
}
