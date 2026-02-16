<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableSlot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'timetable_id',
        'course_section_id',
        'teacher_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'component',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
