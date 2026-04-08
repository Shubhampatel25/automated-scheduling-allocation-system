<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an optional weekly_sessions column to the courses table.
 *
 * This lets admins/HODs specify exactly how many 90-minute sessions per week a
 * course needs instead of relying on the credit-derived default.
 *
 * If NULL the scheduler falls back to deriving the count from credits:
 *   1–2 credits  → 1 session / week
 *   3–4 credits  → 2 sessions / week
 *   5+  credits  → 3 sessions / week
 *   lab component → always 1 session / week (regardless of credits)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekly_sessions')
                  ->nullable()
                  ->after('credits')
                  ->comment('Override: how many 90-min sessions per week this course needs. NULL = derive from credits.');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('weekly_sessions');
        });
    }
};
