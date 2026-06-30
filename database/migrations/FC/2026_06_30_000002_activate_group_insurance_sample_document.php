<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data fix: the "Nomination for Central Government Employees Group Insurance
 * Scheme, 1980 (Form-7 / Form-8)" sample (doc_group_insurance) was missing on
 * production — its row was absent / inactive and the sample PDF was renamed to
 * sample_group_insurance.pdf in this release. A file rename in git does not
 * update the DB path, so without this the "View Sample" column stays "—".
 *
 * Ensures the row exists, points at the renamed file, and is active. Works
 * whether the row is missing, inactive, or pointing at the old filename.
 * Idempotent: safe to run more than once.
 */
return new class extends Migration
{
    private const FIELD = 'doc_group_insurance';
    private const PATH  = 'admin_assets/sample/joining_documents/sample_group_insurance.pdf';

    public function up(): void
    {
        if (! Schema::hasTable('fc_joining_sample_documents')) {
            return;
        }

        $table = DB::table('fc_joining_sample_documents');

        if ($table->where('field_name', self::FIELD)->exists()) {
            $table->where('field_name', self::FIELD)->update([
                'sample_file_path'     => self::PATH,
                'sample_original_name' => 'sample_group_insurance.pdf',
                'is_active'            => 1,
                'updated_at'           => now(),
            ]);

            return;
        }

        // Pull a representative label/section from the form field, falling back
        // to the known values if the field is not present.
        $field = DB::table('fc_form_fields')
            ->where('field_name', self::FIELD)
            ->where('field_type', 'file')
            ->first(['label', 'section_heading']);

        $table->insert([
            'field_name'           => self::FIELD,
            'document_title'       => $field->label
                ?? 'Nomination for Central Government Employees Group Insurance Scheme, 1980 (Form-7 / Form-8)',
            'section'              => $field->section_heading ?? 'Accounts Section Related Documents',
            'sample_file_path'     => self::PATH,
            'sample_original_name' => 'sample_group_insurance.pdf',
            'display_order'        => 15,
            'is_active'            => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    public function down(): void
    {
        // No-op: reverting would re-hide a working sample.
    }
};
