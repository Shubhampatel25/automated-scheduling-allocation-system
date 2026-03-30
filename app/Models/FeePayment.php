<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'type',
        'course_id',
        'semester',
        'year',
        'amount',
        'paid_amount',
        'status',
        'paid_at',
        'created_at',
    ];

    protected $casts = [
        'paid_at'     => 'datetime',
        'created_at'  => 'datetime',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
