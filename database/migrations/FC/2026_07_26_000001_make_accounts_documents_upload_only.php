<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reverts the three "Accounts Section Related Documents" to plain file uploads.
 *
 *   - Nomination for CGEGIS, 1980 (Form-7 / Form-8)  (doc_group_insurance)
 *   - National Pension System (NPS) Subscription Form (doc_nps_subscription)
 *   - Employee Information Sheet Form                 (doc_employee_info_sheet)
 *
 * These are statutory forms the officer must download, fill and sign offline,
 * then upload — not fill in online. Clearing `form_template` is what switches
 * the checklist row from a "Fill Form" button back to the upload control (see
 * resources/views/fc/registration/partials/document-checklist.blade.php).
 *
 * All three already have active sample PDFs in fc_joining_sample_documents, so
 * the "View Sample" download the officer fills from is unaffected.
 *
 * The PDF templates in App\Support\FC\DocumentFormTemplates are deliberately
 * left in place — nothing looks them up while form_template is NULL, and they
 * are needed again if the decision is reversed.
 *
 * Idempotent: safe to run more than once.
 */
return new class extends Migration
{
    private const FIELDS = [
        'doc_group_insurance',
        'doc_nps_subscription',
        'doc_employee_info_sheet',
    ];

    /** Restores the pre-migration state on rollback. */
    private const TEMPLATES = [
        'doc_group_insurance'     => 'group_insurance',
        'doc_nps_subscription'    => 'nps_subscription',
        'doc_employee_info_sheet' => 'employee_info_sheet',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('fc_form_fields') || ! Schema::hasColumn('fc_form_fields', 'form_template')) {
            return;
        }

        DB::table('fc_form_fields')
            ->whereIn('field_name', self::FIELDS)
            ->where('field_type', 'file')
            ->update(['form_template' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_fields') || ! Schema::hasColumn('fc_form_fields', 'form_template')) {
            return;
        }

        // Three separate UPDATEs (one per template key), so they are wrapped in a
        // transaction (G7) — a partial rollback would leave the checklist showing
        // "Fill Form" for some Accounts documents and "Upload" for others.
        DB::transaction(function () {
            foreach (self::TEMPLATES as $fieldName => $templateKey) {
                DB::table('fc_form_fields')
                    ->where('field_name', $fieldName)
                    ->where('field_type', 'file')
                    ->update(['form_template' => $templateKey]);
            }
        });
    }
};
