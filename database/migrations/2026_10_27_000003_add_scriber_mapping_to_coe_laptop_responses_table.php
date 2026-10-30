<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — which scribers write for an OT: the "Scribers Details" rows of the OT Laptop
 * Status dialog.
 *
 * One primary and one secondary per OT, so two columns on the OT's existing
 * one-row-per-participant answer rather than a mapping table. NULL = none assigned.
 *
 * Mirrors the DDL approved on 2026-10-27. The foreign keys are RESTRICT (MySQL's
 * default): a scriber assigned to an OT cannot be deleted, only made Inactive — the
 * Scriber's Master screen says so instead of silently unassigning them.
 *
 * ── Deployment ──
 *   Order        : after 2026_10_27_000002 (coe_scriber_master), then code.
 *   Rollback     : down() drops the keys and both columns, and every assignment with them.
 *   Verification : OT Laptop Status → Add Single Record → pick a Primary Scriber, save,
 *                  and confirm the grid's Primary Scriber column shows the name.
 */
return new class extends Migration
{
    private const TABLE = 'coe_laptop_responses';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasTable('coe_scriber_master')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'primary_scriber_pk')) {
                $table->unsignedBigInteger('primary_scriber_pk')->nullable()->after('scriber_remarks');
                $table->foreign('primary_scriber_pk', 'coe_lr_primary_scriber_foreign')
                    ->references('pk')->on('coe_scriber_master');
            }

            if (! Schema::hasColumn(self::TABLE, 'secondary_scriber_pk')) {
                $table->unsignedBigInteger('secondary_scriber_pk')->nullable()->after('primary_scriber_pk');
                $table->foreign('secondary_scriber_pk', 'coe_lr_secondary_scriber_foreign')
                    ->references('pk')->on('coe_scriber_master');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['primary_scriber_pk' => 'coe_lr_primary_scriber_foreign', 'secondary_scriber_pk' => 'coe_lr_secondary_scriber_foreign'] as $column => $key) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropForeign($key);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
