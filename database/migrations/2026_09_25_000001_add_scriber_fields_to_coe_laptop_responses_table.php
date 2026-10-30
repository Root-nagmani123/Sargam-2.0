<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — the disability / scriber half of the Laptop Availability Form.
 *
 * The form the Officer Trainee fills in now asks three more things: whether they are an
 * OT with disabilities, whether a scriber is required, and the remarks that explain the
 * scriber request. Both write paths — the trainee's own form and the COE Admin's
 * "Add Single Record" dialog — collect them, so they live beside the laptop answer on
 * the same one-row-per-participant table rather than in a second table that would have
 * to be kept in step with it.
 *
 * ── Why a separate migration ──
 * 2026_09_16_000004 creates coe_laptop_responses and returns early when the table already
 * exists, so editing it in place would add nothing on any database where it has already
 * run. This one is additive and column-guarded, which makes it correct in both cases: a
 * fresh install gets the columns here, an existing one gets them added.
 *
 * ── Why scriber_required is NULLABLE and laptop_required is not ──
 * A row only exists once the laptop question has been answered, so laptop_required is
 * always known. The scriber question is only ASKED when has_disability is 1 — the two
 * controls are hidden otherwise — so for every trainee who answered No there is no
 * answer to store, and NULL says "not applicable" where a 0 would falsely claim the
 * trainee was asked and declined.
 *
 * ── Deployment ──
 *   Order        : migration first, then code. Both forms post the new fields.
 *   Rollback     : down() drops the three columns and loses the scriber answers with
 *                  them. The laptop answers are untouched.
 *   Verification : open /admin/coe/laptop-availability → Import Laptop Status → Add
 *                  Single Record, set "OT with Disabilities" to Yes, and confirm
 *                  "Scriber Required" and "Remarks for Scriber" appear and save.
 */
return new class extends Migration
{
    private const TABLE = 'coe_laptop_responses';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'has_disability')) {
                $table->boolean('has_disability')->default(0)->after('reason')
                    ->comment('The form\'s "OT with Disabilities". 1=Yes — the scriber question was asked; 0=No');
            }

            if (! Schema::hasColumn(self::TABLE, 'scriber_required')) {
                $table->boolean('scriber_required')->nullable()->after('has_disability')
                    ->comment('1=Yes, 0=No, NULL=never asked because has_disability is 0');
            }

            if (! Schema::hasColumn(self::TABLE, 'scriber_remarks')) {
                $table->string('scriber_remarks', 500)->nullable()->after('scriber_required')
                    ->comment('The form\'s "Remarks for Scriber" — required when scriber_required is 1');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['scriber_remarks', 'scriber_required', 'has_disability'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
