<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (! Schema::hasColumn('fc_registration_master', 'fc_Prev_comp_doc')) {
                // Certificate of the previously completed Foundation Course, uploaded with a
                // "Previously Completed Foundation Course" exemption application. Stores the
                // path on the public disk, exactly like medical_exemption_doc beside it.
                $table->string('fc_Prev_comp_doc', 255)->nullable()->after('medical_exemption_doc');
            }
        });

        $this->forgetSchemaCache();
    }

    public function down(): void
    {
        Schema::table('fc_registration_master', function (Blueprint $table) {
            if (Schema::hasColumn('fc_registration_master', 'fc_Prev_comp_doc')) {
                $table->dropColumn('fc_Prev_comp_doc');
            }
        });

        $this->forgetSchemaCache();
    }

    /**
     * fc_schema_columns() caches the column listing for 24h. Without this the application
     * keeps reading the pre-migration list, fc_schema_has_column() reports the new column as
     * absent, and the guarded write in FrontPageController::apply_exemptionstore() silently
     * skips it — the certificate would validate as required and then not be stored.
     */
    private function forgetSchemaCache(): void
    {
        try {
            if (function_exists('fc_schema_cache_key')) {
                Cache::forget(fc_schema_cache_key('fc_registration_master'));
            }
            if (function_exists('fc_schema_columns')) {
                fc_schema_columns('fc_registration_master', true);
            }
            if (function_exists('fc_schema_has_column')) {
                fc_schema_has_column('fc_registration_master', '', true);
            }
        } catch (\Throwable $e) {
            // Cache store unavailable during migrate — the TTL heals it, and the guarded
            // write skips the column until then rather than throwing.
        }
    }
};
