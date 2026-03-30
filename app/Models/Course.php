<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'semester',
        'prerequisite_course_code',
        'prerequisite_mandatory',
        'fee',
        'credits',
        'type',
        'description',
        'status',
        'created_at',
    ];

    protected $casts = [
        'prerequisite_mandatory' => 'boolean',
        'created_at'             => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class);
    }
}
