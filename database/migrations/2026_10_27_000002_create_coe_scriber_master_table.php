<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Master Data → Scriber's Master.
 *
 * The people who write an examination for an Officer Trainee who cannot. Each is
 * either a Primary or a Secondary scriber, and the OT Laptop Status dialog's
 * "Scribers Details" picks from this list by that role.
 *
 * Mirrors the DDL approved on 2026-10-27. The Active / Archived tabs of the screen are
 * active_inactive 1 / 0 — there is no separate archive flag.
 *
 * ── Deployment ──
 *   Order        : this migration, then 2026_10_27_000003 (which references it), then code.
 *   Rollback     : roll 000003 back first; down() here drops the table and every scriber.
 *   Verification : open /admin/coe/scriber-master, add a scriber, and confirm it is
 *                  offered in OT Laptop Status → Add Single Record → Scribers Details.
 */
return new class extends Migration
{
    private const TABLE = 'coe_scriber_master';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');
            $table->string('scriber_name', 150);
            $table->string('phone_number', 15);
            $table->string('email', 150);
            $table->tinyInteger('scriber_role')->comment('1=Primary, 2=Secondary');
            $table->string('remarks', 500)->nullable();
            $table->boolean('active_inactive')->default(1)->comment('1=Active, 0=Inactive (Archived tab)');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();

            $table->unique('email', 'coe_sm_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
