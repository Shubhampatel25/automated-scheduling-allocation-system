<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'room_number',
        'building',
        'type',
        'capacity',
        'equipment',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function roomAvailabilities()
    {
        return $this->hasMany(RoomAvailability::class);
    }
}
