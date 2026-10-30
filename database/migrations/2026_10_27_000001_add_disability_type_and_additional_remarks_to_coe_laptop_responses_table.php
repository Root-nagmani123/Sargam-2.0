<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE — Disability Type and Additional Remarks on the OT Laptop Status record.
 *
 * The redesigned "Add Single Record" dialog asks which disability an OT with
 * disabilities has, and closes with a free-text Additional Remarks box. Both belong to
 * the same one-row-per-participant answer, so they sit beside has_disability and
 * scriber_remarks rather than in a table of their own.
 *
 * Mirrors the DDL approved on 2026-10-27:
 *   ALTER TABLE coe_laptop_responses
 *     ADD COLUMN disability_type TINYINT NULL
 *       COMMENT '1=Handicapped, 2=Blind; NULL when has_disability=0' AFTER has_disability,
 *     ADD COLUMN additional_remarks VARCHAR(500) NULL AFTER scriber_remarks;
 *
 * The scriber mapping (OT → primary / secondary scriber) and the Scriber Status column
 * wait for the scriber master and are NOT part of this migration.
 *
 * ── Deployment ──
 *   Order        : migration first, then code. The dialog posts both new fields.
 *   Rollback     : down() drops the two columns and the answers in them. Nothing else
 *                  reads them yet.
 *   Verification : COE → OT Laptop Status → Import Laptop Status → Add Single Record,
 *                  set "OT with Disabilities" to Yes, pick a Disability Type, save, and
 *                  confirm the grid's Disability Type column shows it.
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
            if (! Schema::hasColumn(self::TABLE, 'disability_type')) {
                $table->tinyInteger('disability_type')->nullable()->after('has_disability')
                    ->comment('1=Handicapped, 2=Blind; NULL when has_disability=0');
            }

            if (! Schema::hasColumn(self::TABLE, 'additional_remarks')) {
                $table->string('additional_remarks', 500)->nullable()->after('scriber_remarks');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['additional_remarks', 'disability_type'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
