<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds composite indexes to timetable_slots for the overlap-check queries used
 * throughout TimetableConstraintService.
 *
 * Every overlap query filters on (entity_id, day_of_week, start_time, end_time)
 * joined against the timetables table for (term, year, status).  These indexes
 * make those queries fast even when the table grows to tens of thousands of rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            // Teacher overlap:  teacher + day + times
            $table->index(
                ['teacher_id', 'day_of_week', 'start_time', 'end_time'],
                'ts_teacher_day_time'
            );

            // Room overlap:  room + day + times
            $table->index(
                ['room_id', 'day_of_week', 'start_time', 'end_time'],
                'ts_room_day_time'
            );

            // Section / student-group overlap:  section + day + times
            $table->index(
                ['course_section_id', 'day_of_week', 'start_time', 'end_time'],
                'ts_section_day_time'
            );

            // Used when loading all slots for a timetable
            $table->index('timetable_id', 'ts_timetable_id');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->dropIndex('ts_teacher_day_time');
            $table->dropIndex('ts_room_day_time');
            $table->dropIndex('ts_section_day_time');
            $table->dropIndex('ts_timetable_id');
        });
    }
};
