<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `form_template` column to fc_form_fields.
 *
 * When a document (file) field has a form_template (e.g. "family_details"),
 * the joining-document checklist replaces the file-upload box with a
 * "Fill Form" button. The candidate fills the structured form online; on
 * submit a PDF is generated and stored into the field's target_column just
 * like a normal upload — so the existing save/view/status logic is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fc_form_fields', 'form_template')) {
            Schema::table('fc_form_fields', function (Blueprint $table) {
                $table->string('form_template', 100)->nullable()->after('file_extensions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fc_form_fields', 'form_template')) {
            Schema::table('fc_form_fields', function (Blueprint $table) {
                $table->dropColumn('form_template');
            });
        }
    }
};
