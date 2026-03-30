<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->enum('type', ['regular', 'supplemental'])->default('regular')->after('student_id');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn(['type', 'course_id']);
        });
    }
};
