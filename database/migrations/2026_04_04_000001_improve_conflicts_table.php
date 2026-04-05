<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Improve the conflicts table to support the full constraint-based scheduler.
 *
 * Actual table state discovered at migration time:
 *   - conflict_type is already VARCHAR(50)          → skip the ALTER
 *   - course_section_id column already exists       → skip adding it
 *   - slot_id_1 has only an index, NO FK constraint → just MODIFY the column
 *
 * What this migration actually does:
 *   1. Makes slot_id_1 nullable so that unschedulable-assignment conflicts
 *      (where no TimetableSlot was ever created) can be stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Make slot_id_1 nullable ────────────────────────────────────────────
        // The column has an index but no FOREIGN KEY constraint, so we can
        // MODIFY it directly without touching any constraint.
        if (!$this->columnIsNullable('conflicts', 'slot_id_1')) {
            DB::unprepared(
                'ALTER TABLE conflicts MODIFY COLUMN slot_id_1 BIGINT UNSIGNED NULL'
            );
        }
    }

    public function down(): void
    {
        // Restore NOT NULL (only safe if there are no NULL values in the column)
        DB::statement('UPDATE conflicts SET slot_id_1 = 0 WHERE slot_id_1 IS NULL');
        DB::unprepared(
            'ALTER TABLE conflicts MODIFY COLUMN slot_id_1 BIGINT UNSIGNED NOT NULL'
        );
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function columnIsNullable(string $table, string $column): bool
    {
        $result = DB::select(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = ?
               AND COLUMN_NAME  = ?",
            [$table, $column]
        );

        return !empty($result) && $result[0]->IS_NULLABLE === 'YES';
    }
};
