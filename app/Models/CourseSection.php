<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StudentCourseRegistration;
use Illuminate\Support\Facades\DB;

class CourseSection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'course_id',
        'section_number',
        'term',
        'year',
        'max_students',
        'enrolled_students',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_course_registrations')
                    ->withPivot('status', 'registered_at');
    }

    public function studentCourseRegistrations()
    {
        return $this->hasMany(StudentCourseRegistration::class);
    }

    // ── Counter reconciliation ────────────────────────────────────────────────

    /**
     * Reconcile enrolled_students across ALL course sections in one SQL statement.
     *
     * Sets enrolled_students = COUNT(student_course_registrations WHERE status='enrolled')
     * for every section where the stored counter is out of sync with reality.
     *
     * Uses a LEFT JOIN subquery so sections with zero enrolled students (which
     * would be absent from an INNER JOIN) are also reset to 0 when needed.
     *
     * Only rows that are actually out of sync are touched (WHERE clause filters),
     * so the DB write is minimal and the operation is safe to call at any time.
     *
     * Returns the number of sections whose counter was corrected.
     * No schema changes required — operates on existing columns only.
     */
    public static function syncAllEnrolledCounts(): int
    {
        return DB::update("
            UPDATE course_sections cs
            LEFT JOIN (
                SELECT course_section_id, COUNT(*) AS actual_count
                FROM student_course_registrations
                WHERE status = 'enrolled'
                GROUP BY course_section_id
            ) reg ON reg.course_section_id = cs.id
            SET cs.enrolled_students = COALESCE(reg.actual_count, 0)
            WHERE cs.enrolled_students != COALESCE(reg.actual_count, 0)
        ");
    }
}
