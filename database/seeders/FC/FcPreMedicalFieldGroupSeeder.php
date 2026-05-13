<?php

namespace Database\Seeders\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\FcFormGroupField;
use App\Models\FC\FcFormStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds the Step 3 "Pre-medical history" group for databases seeded before that group existed.
 * Safe to run multiple times. Invoke:
 * php artisan db:seed --class=Database\\Seeders\\FC\\FcPreMedicalFieldGroupSeeder
 */
class FcPreMedicalFieldGroupSeeder extends Seeder
{
    public function run(): void
    {
        $form = FcForm::where('form_slug', 'fc-registration')->first();
        if (! $form) {
            $this->command?->warn('FcForm fc-registration not found; skip pre-medical group.');

            return;
        }

        $step3 = FcFormStep::where('form_id', $form->id)->where('step_slug', 'step3')->first();
        if (! $step3) {
            $this->command?->warn('FC registration step3 not found; skip pre-medical group.');

            return;
        }

        if (FcFormFieldGroup::where('step_id', $step3->id)->where('group_name', 'pre_medical_history')->exists()) {
            $this->command?->info('pre_medical_history group already exists; nothing to do.');

            return;
        }

        DB::transaction(function () use ($step3) {
            FcFormFieldGroup::where('step_id', $step3->id)->increment('display_order');

            $g = FcFormFieldGroup::create([
                'step_id' => $step3->id,
                'group_name' => 'pre_medical_history',
                'group_label' => 'Pre-medical history',
                'target_table' => 'fc_pre_history',
                'save_mode' => 'upsert',
                'min_rows' => 1,
                'max_rows' => 1,
                'display_order' => 0,
            ]);

            $fields = [
                ['field_name' => 'allergy_illness', 'label' => 'History of allergy / previous illness / injury / disability / asthma / slip disc / blood transfusion', 'field_type' => 'textarea', 'target_column' => 'allergy_illness', 'validation_rules' => 'nullable|string|max:60000', 'is_required' => 0, 'display_order' => 1, 'css_class' => 'col-12'],
                ['field_name' => 'prolonged_medication', 'label' => 'History of prolonged medication', 'field_type' => 'textarea', 'target_column' => 'prolonged_medication', 'validation_rules' => 'nullable|string|max:60000', 'is_required' => 0, 'display_order' => 2, 'css_class' => 'col-12'],
                ['field_name' => 'hospital_history', 'label' => 'History of hospitalisation / surgery', 'field_type' => 'textarea', 'target_column' => 'hospital_history', 'validation_rules' => 'nullable|string|max:60000', 'is_required' => 0, 'display_order' => 3, 'css_class' => 'col-12'],
                ['field_name' => 'altitude_illness', 'label' => 'History of altitude illness / motion sickness', 'field_type' => 'textarea', 'target_column' => 'altitude_illness', 'validation_rules' => 'nullable|string|max:60000', 'is_required' => 0, 'display_order' => 4, 'css_class' => 'col-12'],
                ['field_name' => 'additional_info', 'label' => 'Any other relevant medical information', 'field_type' => 'textarea', 'target_column' => 'additional_info', 'validation_rules' => 'nullable|string|max:60000', 'is_required' => 0, 'display_order' => 5, 'css_class' => 'col-12'],
                ['field_name' => 'pre_med_doc', 'label' => 'Supporting document (PDF or image)', 'field_type' => 'file', 'target_column' => 'doc_path', 'validation_rules' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', 'is_required' => 0, 'display_order' => 6, 'css_class' => 'col-12'],
            ];

            foreach ($fields as $f) {
                $f['group_id'] = $g->id;
                FcFormGroupField::create($f);
            }
        });

        $this->command?->info('Inserted pre_medical_history field group at display_order 0.');
    }
}
