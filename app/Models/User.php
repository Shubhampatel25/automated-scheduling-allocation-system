<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'email',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function hod()
    {
        return $this->hasOne(Hod::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function generatedTimetables()
    {
        return $this->hasMany(Timetable::class, 'generated_by');
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Check if user is HOD
    public function isHod()
    {
        return $this->role === 'hod';
    }

    // Check if user is professor
    public function isProfessor()
    {
        return $this->role === 'professor';
    }
}