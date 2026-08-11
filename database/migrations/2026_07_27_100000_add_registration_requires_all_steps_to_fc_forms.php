<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Per-form switch for what fc_registration_master.is_registered means.
 *
 *   0 / null (default, every existing form)
 *       is_registered = 1 once the FIRST TWO active steps are done — the long-standing
 *       behaviour. Existing rows are never re-evaluated under the new rule, so nothing
 *       drifts and no backfill is required.
 *
 *   1 (opt in per form, intended for upcoming Foundation Course intakes)
 *       is_registered = 1 only when EVERY APPLICABLE step is done, i.e. steps that do not
 *       apply to a trainee (Special Assistant without a ph_value) are excluded — see
 *       FcStepApplicabilityService. A raw "all active steps" count would lock those
 *       trainees out of the migrate-students pipeline permanently.
 *
 * fc_registration_master.application_type deliberately stays on the first-two-steps rule
 * either way: it answers "registration or exemption?", which is settled early, and the
 * exemption / front-page guards read it.
 */
return new class extends Migration
{
    private const COLUMN = 'registration_requires_all_steps';

    public function up(): void
    {
        if (! Schema::hasTable('fc_forms') || Schema::hasColumn('fc_forms', self::COLUMN)) {
            return;
        }

        Schema::table('fc_forms', function (Blueprint $table) {
            $table->boolean(self::COLUMN)->default(0)->after('user_identifier');
        });

        $this->forgetSchemaCache();
    }

    public function down(): void
    {
        if (Schema::hasTable('fc_forms') && Schema::hasColumn('fc_forms', self::COLUMN)) {
            Schema::table('fc_forms', function (Blueprint $table) {
                $table->dropColumn(self::COLUMN);
            });
        }

        $this->forgetSchemaCache();
    }

    /**
     * fc_schema_columns() caches the column listing for 24h; without this the app keeps
     * reading the pre-migration list and the new flag reads as absent.
     */
    private function forgetSchemaCache(): void
    {
        try {
            if (function_exists('fc_schema_cache_key')) {
                Cache::forget(fc_schema_cache_key('fc_forms'));
            }
            if (function_exists('fc_schema_has_column')) {
                fc_schema_has_column('fc_forms', '', true);
            }
        } catch (\Throwable $e) {
            // Cache store unavailable during migrate — the TTL heals it, and the sync
            // falls back to the existing first-two-steps rule until then.
        }
    }
};
