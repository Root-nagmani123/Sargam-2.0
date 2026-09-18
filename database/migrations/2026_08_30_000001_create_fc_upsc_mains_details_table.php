<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage table for the "Appearing in UPSC CSE (Mains)" section of the dynamic form.
 *
 * Only the key columns are created here. The question columns themselves are added by
 * the Form Builder when an admin adds the fields (FormBuilderController::ensureColumnExists),
 * which is why this table starts out with nothing but the user key — the builder can add
 * COLUMNS to an existing table but never creates the TABLE, and a group pointing at a
 * missing table fails only when the first trainee saves.
 *
 * It gets its own table on purpose. A group whose fields are all blank deletes its whole
 * row (DynamicFormService::saveGroupData, upsert branch), so sharing a table with another
 * field group would let one section wipe the other's answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fc_upsc_mains_details')) {
            return;
        }

        Schema::create('fc_upsc_mains_details', function (Blueprint $table) {
            $table->id();
            // One row per trainee — matches the upsert save mode used by single-row groups.
            $table->unsignedBigInteger('user_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_upsc_mains_details');
    }
};
