<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'partial' to status enum
        DB::statement("ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid','pending','overdue','partial') NOT NULL DEFAULT 'pending'");

        // Add paid_amount column to track how much was actually paid
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        DB::statement("ALTER TABLE fee_payments MODIFY COLUMN status ENUM('paid','pending','overdue') NOT NULL DEFAULT 'pending'");
    }
};
