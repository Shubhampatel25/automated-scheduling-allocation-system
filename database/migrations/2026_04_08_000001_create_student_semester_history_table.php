<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_semester_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('year');
            $table->enum('result', ['pass', 'fail', 'in_progress'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index('student_id');
            $table->unique(['student_id', 'semester', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_semester_history');
    }
};
