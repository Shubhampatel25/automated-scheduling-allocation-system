<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop resolved_at only if it still exists (guards against partial re-runs)
        if (Schema::hasColumn('conflicts', 'resolved_at')) {
            Schema::table('conflicts', function (Blueprint $table) {
                $table->dropColumn('resolved_at');
            });
        }

        // Step 1: Expand enum to include both old and new values so UPDATE queries work
        DB::unprepared("ALTER TABLE conflicts MODIFY COLUMN conflict_type ENUM('room_conflict','teacher_conflict','room_overlap','teacher_overlap','student_overlap','capacity_exceeded','student_conflict') NOT NULL");

        // Step 2: Migrate old enum values to what detectConflicts() actually inserts
        DB::table('conflicts')->where('conflict_type', 'room_overlap')->update(['conflict_type' => 'room_conflict']);
        DB::table('conflicts')->where('conflict_type', 'teacher_overlap')->update(['conflict_type' => 'teacher_conflict']);

        // Step 3: Delete rows with obsolete types that no code creates or handles
        DB::table('conflicts')->whereIn('conflict_type', ['student_overlap', 'capacity_exceeded', 'student_conflict'])->delete();

        // Step 4: Lock enum down to only the two types the code uses
        DB::unprepared("ALTER TABLE conflicts MODIFY COLUMN conflict_type ENUM('room_conflict','teacher_conflict') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('conflicts', 'resolved_at')) {
            Schema::table('conflicts', function (Blueprint $table) {
                $table->timestamp('resolved_at')->nullable();
            });
        }

        DB::unprepared("ALTER TABLE conflicts MODIFY COLUMN conflict_type ENUM('room_conflict','teacher_conflict','student_conflict') NOT NULL");
    }
};
