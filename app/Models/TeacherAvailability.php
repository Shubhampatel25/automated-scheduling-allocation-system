<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'teacher_availability';

    protected $fillable = [
        'teacher_id',
        'term',
        'year',
        'day_of_week',
        'start_time',
        'end_time',
        'max_hours_per_week',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
