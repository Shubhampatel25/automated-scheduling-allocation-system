<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssignment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'course_section_id',
        'teacher_id',
        'component',
        'assigned_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
