<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Document Management → Signature Master.
 *
 * The signatures printed at the foot of a marksheet (Prepared By / Compared By /
 * Controller of Examination), one per designation holder. The image lives on the
 * private `local` disk; signature_path is relative to it.
 *
 * ── Deployment ──
 *   Rollback     : down() drops the table; the image files under
 *                  storage/app/coe/signatures stay and can be removed by hand.
 *   Verification : open /admin/coe/signature-master, add a signature, and confirm
 *                  "See attachment" shows the image.
 */
return new class extends Migration
{
    private const TABLE = 'coe_signature_master';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id('pk');
            $table->string('designation_name', 100);
            $table->string('person_name', 150);
            $table->string('signature_path', 255);
            $table->boolean('active_inactive')->default(1)->comment('1=Active, 0=Inactive');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamp('modified_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
