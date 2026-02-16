<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'roll_no',
        'name',
        'email',
        'department_id',
        'semester',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function studentCourseRegistrations()
    {
        return $this->hasMany(StudentCourseRegistration::class);
    }

    public function courseSections()
    {
        return $this->belongsToMany(CourseSection::class, 'student_course_registrations')
                    ->withPivot('status', 'registered_at');
    }
}
