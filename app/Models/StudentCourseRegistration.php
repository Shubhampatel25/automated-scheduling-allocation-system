<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCourseRegistration extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'student_course_registrations';

    protected $fillable = [
        'student_id',
        'course_section_id',
        'status',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }
}
