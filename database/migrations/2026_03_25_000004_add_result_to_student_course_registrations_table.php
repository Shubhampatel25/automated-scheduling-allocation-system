<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_course_registrations', function (Blueprint $table) {
            $table->enum('result', ['pass', 'fail'])->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_course_registrations', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
