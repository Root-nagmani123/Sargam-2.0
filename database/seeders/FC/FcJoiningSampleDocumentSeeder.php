<?php

namespace Database\Seeders\FC;

use App\Models\FC\FcJoiningSampleDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the joining sample-document master from the existing static sample PDFs
 * (public/admin_assets/sample/joining_documents). Idempotent: keyed by field_name.
 * Admins can replace these via the Sample Document Master page afterwards.
 */
class FcJoiningSampleDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $base = 'admin_assets/sample/joining_documents/';

        // field_name => static sample file (relative to public/)
        $map = [
            'doc_family_details'     => 'sample_family_details1.pdf',
            'doc_close_relation'     => 'sample_close_relations_2.pdf',
            'doc_dowry_decl'         => 'sample_dowry_declaration3.pdf',
            'doc_marital_status'     => 'sample_marital_declaration4.pdf',
            'doc_home_town'          => 'sample_home_town5.pdf',
            'doc_immovable_prop'     => 'sample_immovable_property6a.pdf',
            'doc_movable_prop'       => 'sample_movable_property6b.pdf',
            'doc_debts_liabilities'  => 'sample_debts_other_liabilities6c.pdf',
            'doc_surety_bond_ias'    => 'sample_surety_bond_iasips7a.pdf',
            'doc_surety_bond_others' => 'sample_surety_bond_other_services7b.pdf',
            'doc_oath_affirmation'   => 'sample_main_assumption_charge.pdf',
            'doc_assumption_charge'  => 'sample_main_assumption_charge.pdf',
            'doc_group_insurance'    => 'sample_group_insurance.pdf',
            'doc_nps_subscription'   => 'sample_nps_form10.pdf',
            'doc_employee_info_sheet' => 'sample_employee_information11.pdf',
        ];

        // Pull a representative label + section per field_name from the form fields.
        $fieldMeta = DB::table('fc_form_fields')
            ->where('field_name', 'like', 'doc_%')
            ->where('field_type', 'file')
            ->whereNotNull('label')
            ->get(['field_name', 'label', 'section_heading'])
            ->groupBy('field_name');

        $order = 0;
        foreach ($map as $fieldName => $file) {
            $meta = optional($fieldMeta->get($fieldName))->first();

            FcJoiningSampleDocument::updateOrCreate(
                ['field_name' => $fieldName],
                [
                    'document_title'       => $meta->label ?? $fieldName,
                    'section'              => $meta->section_heading ?? null,
                    'sample_file_path'     => $base . $file,
                    'sample_original_name' => $file,
                    'display_order'        => ++$order,
                    'is_active'            => true,
                ]
            );
        }
    }
}
