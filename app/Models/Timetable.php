<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'department_id',
        'term',
        'year',
        'semester',
        'status',
        'generated_by',
        'generated_at',
        'conflicts_count',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function conflicts()
    {
        return $this->hasMany(Conflict::class);
    }
}
