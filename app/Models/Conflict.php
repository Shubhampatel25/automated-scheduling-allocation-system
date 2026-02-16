<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function slot1()
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id_1');
    }

    public function slot2()
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id_2');
    }
}
