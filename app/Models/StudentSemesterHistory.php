<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSemesterHistory extends Model
{
    public $timestamps = false;

    protected $table = 'student_semester_history';

    protected $fillable = [
        'student_id',
        'semester',
        'year',
        'result',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
