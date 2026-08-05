<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renames the Accounts Section "NPS Subscription Registration Form" document
 * title to also cover the Unified Pension Scheme (UPS), since the same form is
 * now used for both schemes:
 *
 *   "National Pension System (NPS) Subscription Registration Form"
 *     -> "National Pension System (NPS) / Unified Pension Scheme (UPS) Subscription Registration Form"
 *
 * Only the display label changes — field_name (doc_nps_subscription), the
 * upload target and every other attribute are untouched.
 *
 * Idempotent: safe to run more than once.
 */
return new class extends Migration
{
    private const FIELD = 'doc_nps_subscription';

    private const OLD_LABEL = 'National Pension System (NPS) Subscription Registration Form';
    private const NEW_LABEL = 'National Pension System (NPS) / Unified Pension Scheme (UPS) Subscription Registration Form';

    public function up(): void
    {
        if (! Schema::hasTable('fc_form_fields')) {
            return;
        }

        DB::table('fc_form_fields')
            ->where('field_name', self::FIELD)
            ->update(['label' => self::NEW_LABEL]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_fields')) {
            return;
        }

        DB::table('fc_form_fields')
            ->where('field_name', self::FIELD)
            ->update(['label' => self::OLD_LABEL]);
    }
};
