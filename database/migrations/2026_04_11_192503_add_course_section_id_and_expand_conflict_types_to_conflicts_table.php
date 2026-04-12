<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix two gaps left by earlier migrations:
 *
 *  1. Add `course_section_id` (nullable) — the conflict service writes this
 *     column for every unschedulable-assignment and slot-conflict record, but
 *     it was never present in the original CREATE TABLE.
 *
 *  2. Change `conflict_type` from a narrow ENUM to VARCHAR(50) so all values
 *     the scheduler now inserts are accepted:
 *       no_feasible_slot, missing_assignment, missing_teacher_availability,
 *       no_suitable_room, section_overlap, room_conflict, teacher_conflict
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add course_section_id if missing ───────────────────────────────
        if (!Schema::hasColumn('conflicts', 'course_section_id')) {
            Schema::table('conflicts', function (Blueprint $table) {
                $table->unsignedBigInteger('course_section_id')->nullable()->after('slot_id_2');
            });
        }

        // ── 2. Widen conflict_type to VARCHAR(50) ─────────────────────────────
        // The original column is ENUM('room_conflict','teacher_conflict').
        // Changing to VARCHAR accepts every type the code now writes without
        // requiring enum list maintenance on future changes.
        DB::unprepared("ALTER TABLE conflicts MODIFY COLUMN conflict_type VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Remove new conflict types before restoring the narrow enum
        DB::table('conflicts')
            ->whereNotIn('conflict_type', ['room_conflict', 'teacher_conflict'])
            ->delete();

        DB::unprepared("ALTER TABLE conflicts MODIFY COLUMN conflict_type ENUM('room_conflict','teacher_conflict') NOT NULL");

        if (Schema::hasColumn('conflicts', 'course_section_id')) {
            Schema::table('conflicts', function (Blueprint $table) {
                $table->dropColumn('course_section_id');
            });
        }
    }
};
