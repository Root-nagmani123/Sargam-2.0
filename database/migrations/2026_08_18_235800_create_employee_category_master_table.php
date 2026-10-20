<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employee_category_master is a legacy master table (real historical data on
 * every environment that has it, going back to 2013) that has simply never
 * been created by a tracked migration — it exists on environments seeded
 * from a production-like data dump, but not on a fresh install or a clean
 * test database, which is exactly what let Member wizard Step 6 ("Employee
 * Grade Pay") ship referencing a table nothing here actually creates.
 *
 * Guarded so this is a no-op wherever the table already exists (production
 * included) — it never touches existing rows — and only creates the (empty)
 * table, with the same structure, on an environment that's missing it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_category_master')) {
            return;
        }

        Schema::create('employee_category_master', function (Blueprint $table) {
            $table->bigIncrements('pk');
            $table->string('category', 30)->nullable()->index('idx_employee_category');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->dateTime('modified_date')->nullable();
        });
    }

    public function down(): void
    {
        // Never drop a table that may hold real legacy data — if this
        // migration created it (fresh environment), it will be empty and
        // dropping it is safe; if it pre-existed (production), dropping it
        // would destroy real data. Since a migration can't tell those two
        // cases apart at rollback time, the safe choice is to do nothing.
    }
};
