<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE marksheets are signed one by one before they are published: the UDC signs,
 * then the AAO (and the UDC's place if still blank), then the COE (and any blank
 * place), which publishes it. `signed` records each signature put on the marksheet:
 *   {"UDC": {"signature": <coe_signature_master.pk>, "by": <user_credentials.user_id>, "at": "Y-m-d H:i:s"}, ...}
 *
 * Idempotent: a column already added (e.g. by DBA DDL) is left as it is.
 */
return new class extends Migration
{
    private const TABLE = 'coe_marksheets';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasColumn(self::TABLE, 'signed')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->json('signed')->nullable()->after('status')->comment('Signatures put on the marksheet, by designation');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn(self::TABLE, 'signed')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('signed');
            });
        }
    }
};
