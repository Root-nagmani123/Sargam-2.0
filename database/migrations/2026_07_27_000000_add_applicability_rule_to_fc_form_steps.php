<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes "this step does not apply to this trainee" a configured property of the step
 * instead of a hard-coded step-name match spread across three services.
 *
 * Only rule shipped today is `ph_value_present` (Special Assistant, enabled per trainee
 * via fc_registration_master.ph_value). Steps with a NULL rule always apply, which is
 * the behaviour every other step already had.
 */
return new class extends Migration
{
    private const RULE = 'ph_value_present';

    public function up(): void
    {
        if (! Schema::hasTable('fc_form_steps')) {
            return;
        }

        if (! Schema::hasColumn('fc_form_steps', 'applicability_rule')) {
            Schema::table('fc_form_steps', function (Blueprint $table) {
                $table->string('applicability_rule', 40)->nullable()->after('tracker_column');
            });
        }

        // Backfill the steps the hard-coded rule already covered, so behaviour is
        // identical the moment this deploys. Single UPDATE, no per-row loop.
        DB::table('fc_form_steps')
            ->whereNull('applicability_rule')
            ->where('step_name', 'like', 'special assist%')
            ->update(['applicability_rule' => self::RULE]);

        $this->forgetSchemaCache();
    }

    public function down(): void
    {
        if (Schema::hasTable('fc_form_steps') && Schema::hasColumn('fc_form_steps', 'applicability_rule')) {
            Schema::table('fc_form_steps', function (Blueprint $table) {
                $table->dropColumn('applicability_rule');
            });
        }

        $this->forgetSchemaCache();
    }

    /**
     * fc_schema_columns() caches the column listing for 24h; without this the app
     * keeps reading the pre-migration list and silently falls back to the legacy
     * step-name match.
     */
    private function forgetSchemaCache(): void
    {
        try {
            if (function_exists('fc_schema_cache_key')) {
                Cache::forget(fc_schema_cache_key('fc_form_steps'));
            }
            if (function_exists('fc_schema_has_column')) {
                fc_schema_has_column('fc_form_steps', '', true);
            }
        } catch (\Throwable $e) {
            // Cache store unavailable during migrate — the 24h TTL will heal it, and the
            // service falls back to the legacy name match until then.
        }
    }
};
