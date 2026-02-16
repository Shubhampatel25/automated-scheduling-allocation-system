<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->enum('conflict_type', ['room_conflict', 'teacher_conflict', 'student_conflict']);
            $table->text('description');
            $table->foreignId('slot_id_1')->constrained('timetable_slots')->onDelete('cascade');
            $table->foreignId('slot_id_2')->nullable()->constrained('timetable_slots')->onDelete('cascade');
            $table->enum('status', ['unresolved', 'resolved'])->default('unresolved');
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflicts');
    }
};
