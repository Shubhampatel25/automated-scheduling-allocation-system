<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'section_number',
        'term',
        'year',
        'max_students',
        'enrolled_students',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_course_registrations')
                    ->withPivot('status', 'registered_at');
    }
}
