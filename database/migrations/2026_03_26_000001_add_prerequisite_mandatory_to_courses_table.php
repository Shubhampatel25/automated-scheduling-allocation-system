<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // false = advisory warning only; true = hard block on registration
            $table->boolean('prerequisite_mandatory')->default(false)->after('prerequisite_course_code');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('prerequisite_mandatory');
        });
    }
};
