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
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('term');
            $table->integer('year');
            $table->integer('semester')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at')->nullable();
            $table->integer('conflicts_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
