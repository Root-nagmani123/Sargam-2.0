<?php

namespace Database\Seeders\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormField;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\FcFormGroupField;
use Illuminate\Database\Seeder;

class FcFormBuilderSeeder extends Seeder
{
    public function run(): void
    {
        // ─── FORM ────────────────────────────────────────────────────────
        $form = FcForm::firstOrCreate(
            ['form_slug' => 'fc-registration'],
            [
                'form_name'           => 'FC Registration',
                'description'         => 'Foundation Course Officer Trainee Registration Form',
                'icon'                => 'bi-person-badge',
                'consolidation_table' => 'student_masters',
                'user_identifier'     => 'username',
                'is_active'           => 1,
            ]
        );

        // ─── STEPS ───────────────────────────────────────────────────────
        $step1 = FcFormStep::create([
            'form_id'           => $form->id,
            'step_name'         => 'Basic Information',
            'step_slug'         => 'step1',
            'step_number'       => 1,
            'target_table'      => 'student_master_firsts',
            'completion_column' => 'step1_completed',
            'tracker_column'    => 'step1_done',
            'icon'              => 'bi-person-fill',
            'description'       => 'Please fill in your basic personal and service information.',
        ]);

        $step2 = FcFormStep::create([
            'form_id'           => $form->id,
            'step_name'         => 'Personal Details',
            'step_slug'         => 'step2',
            'step_number'       => 2,
            'target_table'      => 'student_master_seconds',
            'completion_column' => 'step2_completed',
            'tracker_column'    => 'step2_done',
            'icon'              => 'bi-card-checklist',
            'description'       => 'Category, address, emergency contact, and father\'s profession.',
        ]);

        $step3 = FcFormStep::create([
            'form_id'           => $form->id,
            'step_name'         => 'Other Details',
            'step_slug'         => 'step3',
            'step_number'       => 3,
            'target_table'      => 'student_masters',
            'completion_column' => null,
            'tracker_column'    => 'step3_done',
            'icon'              => 'bi-journal-text',
            'description'       => 'Qualifications, employment, languages, hobbies, sports, and module choice.',
        ]);

        $stepBank = FcFormStep::create([
            'form_id'           => $form->id,
            'step_name'         => 'Bank Details',
            'step_slug'         => 'bank',
            'step_number'       => 4,
            'target_table'      => 'new_registration_bank_details_masters',
            'completion_column' => null,
            'tracker_column'    => 'bank_done',
            'icon'              => 'bi-bank',
            'description'       => 'Bank account details for stipend payment.',
        ]);

        $stepDocs = FcFormStep::create([
            'form_id'           => $form->id,
            'step_name'         => 'Document Upload',
            'step_slug'         => 'documents',
            'step_number'       => 5,
            'target_table'      => 'fc_joining_related_documents_details_masters',
            'completion_column' => null,
            'tracker_column'    => 'docs_done',
            'icon'              => 'bi-file-earmark-arrow-up',
            'description'       => 'Upload required documents as per the checklist.',
        ]);

        // ─── STEP 1 FIELDS ──────────────────────────────────────────────
        $this->seedStep1Fields($step1);

        // ─── STEP 2 FIELDS ──────────────────────────────────────────────
        $this->seedStep2Fields($step2);

        // ─── STEP 3 GROUPS + FIELDS ─────────────────────────────────────
        $this->seedStep3Groups($step3);

        // ─── BANK FIELDS ────────────────────────────────────────────────
        $this->seedBankFields($stepBank);
    }

    private function seedStep1Fields(FcFormStep $step): void
    {
        $t = 'student_master_firsts';
        $fields = [
            ['field_name' => 'session_id',        'label' => 'Session',           'field_type' => 'select', 'target_column' => 'session_id',        'validation_rules' => 'required|exists:session_masters,id', 'is_required' => 1, 'display_order' => 1,  'lookup_table' => 'session_masters', 'lookup_value_column' => 'id', 'lookup_label_column' => 'session_name', 'section_heading' => 'Service & Session', 'css_class' => 'col-md-4'],
            ['field_name' => 'full_name',         'label' => 'Full Name',         'field_type' => 'text',   'target_column' => 'full_name',         'validation_rules' => 'required|string|max:200',            'is_required' => 1, 'display_order' => 2,  'section_heading' => 'Personal Details', 'css_class' => 'col-md-6'],
            ['field_name' => 'fathers_name',      'label' => "Father's Name",     'field_type' => 'text',   'target_column' => 'fathers_name',      'validation_rules' => 'required|string|max:200',            'is_required' => 1, 'display_order' => 3,  'section_heading' => 'Personal Details', 'css_class' => 'col-md-6'],
            ['field_name' => 'mothers_name',      'label' => "Mother's Name",     'field_type' => 'text',   'target_column' => 'mothers_name',      'validation_rules' => 'required|string|max:200',            'is_required' => 1, 'display_order' => 4,  'section_heading' => 'Personal Details', 'css_class' => 'col-md-6'],
            ['field_name' => 'date_of_birth',     'label' => 'Date of Birth',     'field_type' => 'date',   'target_column' => 'date_of_birth',     'validation_rules' => 'required|date|before:today',         'is_required' => 1, 'display_order' => 5,  'section_heading' => 'Personal Details', 'css_class' => 'col-md-3'],
            ['field_name' => 'gender',            'label' => 'Gender',            'field_type' => 'select', 'target_column' => 'gender',            'validation_rules' => 'required|in:Male,Female,Other',      'is_required' => 1, 'display_order' => 6,  'options_json' => json_encode([['value'=>'Male','label'=>'Male'],['value'=>'Female','label'=>'Female'],['value'=>'Other','label'=>'Other']]), 'section_heading' => 'Personal Details', 'css_class' => 'col-md-3'],
            ['field_name' => 'service_id',        'label' => 'Service',           'field_type' => 'select', 'target_column' => 'service_id',        'validation_rules' => 'required|exists:service_masters,id', 'is_required' => 1, 'display_order' => 7,  'lookup_table' => 'service_masters', 'lookup_value_column' => 'id', 'lookup_label_column' => 'service_name', 'section_heading' => 'Service & Session', 'css_class' => 'col-md-4'],
            ['field_name' => 'cadre',             'label' => 'Cadre',             'field_type' => 'text',   'target_column' => 'cadre',             'validation_rules' => 'required|string|max:100',            'is_required' => 1, 'display_order' => 8,  'section_heading' => 'Service & Session', 'css_class' => 'col-md-4'],
            ['field_name' => 'allotted_state_id', 'label' => 'Allotted State',    'field_type' => 'select', 'target_column' => 'allotted_state_id', 'validation_rules' => 'required|exists:state_masters,id',   'is_required' => 1, 'display_order' => 9,  'lookup_table' => 'state_masters', 'lookup_value_column' => 'id', 'lookup_label_column' => 'state_name', 'lookup_order_column' => 'state_name', 'section_heading' => 'Service & Session', 'css_class' => 'col-md-4'],
            ['field_name' => 'mobile_no',         'label' => 'Mobile Number',     'field_type' => 'text',   'target_column' => 'mobile_no',         'validation_rules' => 'required|digits:10',                 'is_required' => 1, 'display_order' => 10, 'placeholder' => '10-digit mobile number', 'section_heading' => 'Contact Information', 'css_class' => 'col-md-4'],
            ['field_name' => 'email',             'label' => 'Email Address',     'field_type' => 'email',  'target_column' => 'email',             'validation_rules' => 'required|email|max:150',             'is_required' => 1, 'display_order' => 11, 'section_heading' => 'Contact Information', 'css_class' => 'col-md-4'],
            ['field_name' => 'photo',             'label' => 'Photograph',        'field_type' => 'file',   'target_column' => 'photo_path',        'validation_rules' => 'nullable|image|mimes:jpeg,jpg,png|max:500', 'is_required' => 0, 'display_order' => 12, 'file_max_kb' => 500, 'file_extensions' => 'jpeg,jpg,png', 'help_text' => 'Passport size photo. Max 500KB. JPEG/PNG only.', 'section_heading' => 'Photo & Signature', 'css_class' => 'col-md-6'],
            ['field_name' => 'signature',         'label' => 'Signature',         'field_type' => 'file',   'target_column' => 'signature_path',    'validation_rules' => 'nullable|image|mimes:jpeg,jpg,png|max:200', 'is_required' => 0, 'display_order' => 13, 'file_max_kb' => 200, 'file_extensions' => 'jpeg,jpg,png', 'help_text' => 'Scanned signature. Max 200KB. JPEG/PNG only.', 'section_heading' => 'Photo & Signature', 'css_class' => 'col-md-6'],
        ];

        foreach ($fields as $f) {
            $f['step_id']      = $step->id;
            $f['target_table'] = $t;
            FcFormField::create($f);
        }
    }

    private function seedStep2Fields(FcFormStep $step): void
    {
        $t = 'student_master_seconds';
        $fields = [
            // Classification
            ['field_name'=>'category_id',              'label'=>'Category',              'field_type'=>'select',   'target_column'=>'category_id',              'validation_rules'=>'required|exists:category_masters,id',      'is_required'=>1, 'display_order'=>1,  'lookup_table'=>'category_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'category_name', 'section_heading'=>'Classification', 'css_class'=>'col-md-4'],
            ['field_name'=>'religion_id',              'label'=>'Religion',              'field_type'=>'select',   'target_column'=>'religion_id',              'validation_rules'=>'required|exists:religion_masters,id',      'is_required'=>1, 'display_order'=>2,  'lookup_table'=>'religion_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'religion_name', 'section_heading'=>'Classification', 'css_class'=>'col-md-4'],
            ['field_name'=>'domicile_state',           'label'=>'Domicile State',        'field_type'=>'text',     'target_column'=>'domicile_state',           'validation_rules'=>'nullable|string|max:100',                  'is_required'=>0, 'display_order'=>3,  'section_heading'=>'Classification', 'css_class'=>'col-md-4'],
            ['field_name'=>'marital_status',           'label'=>'Marital Status',        'field_type'=>'select',   'target_column'=>'marital_status',           'validation_rules'=>'required|in:Single,Married,Divorced,Widowed', 'is_required'=>1, 'display_order'=>4,  'options_json'=>json_encode([['value'=>'Single','label'=>'Single'],['value'=>'Married','label'=>'Married'],['value'=>'Divorced','label'=>'Divorced'],['value'=>'Widowed','label'=>'Widowed']]), 'section_heading'=>'Classification', 'css_class'=>'col-md-3'],
            ['field_name'=>'blood_group',              'label'=>'Blood Group',           'field_type'=>'select',   'target_column'=>'blood_group',              'validation_rules'=>'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',   'is_required'=>1, 'display_order'=>5,  'options_json'=>json_encode([['value'=>'A+','label'=>'A+'],['value'=>'A-','label'=>'A-'],['value'=>'B+','label'=>'B+'],['value'=>'B-','label'=>'B-'],['value'=>'AB+','label'=>'AB+'],['value'=>'AB-','label'=>'AB-'],['value'=>'O+','label'=>'O+'],['value'=>'O-','label'=>'O-']]), 'section_heading'=>'Classification', 'css_class'=>'col-md-3'],
            ['field_name'=>'height_cm',                'label'=>'Height (cm)',           'field_type'=>'number',   'target_column'=>'height_cm',                'validation_rules'=>'nullable|numeric|min:50|max:300',          'is_required'=>0, 'display_order'=>6,  'section_heading'=>'Classification', 'css_class'=>'col-md-3'],
            ['field_name'=>'weight_kg',                'label'=>'Weight (kg)',           'field_type'=>'number',   'target_column'=>'weight_kg',                'validation_rules'=>'nullable|numeric|min:20|max:300',          'is_required'=>0, 'display_order'=>7,  'section_heading'=>'Classification', 'css_class'=>'col-md-3'],
            ['field_name'=>'identification_mark1',     'label'=>'Identification Mark 1', 'field_type'=>'text',     'target_column'=>'identification_mark1',     'validation_rules'=>'nullable|string|max:300',                  'is_required'=>0, 'display_order'=>8,  'section_heading'=>'Classification', 'css_class'=>'col-md-6'],
            ['field_name'=>'identification_mark2',     'label'=>'Identification Mark 2', 'field_type'=>'text',     'target_column'=>'identification_mark2',     'validation_rules'=>'nullable|string|max:300',                  'is_required'=>0, 'display_order'=>9,  'section_heading'=>'Classification', 'css_class'=>'col-md-6'],

            // Permanent Address
            ['field_name'=>'perm_address_line1',       'label'=>'Address Line 1',        'field_type'=>'text',     'target_column'=>'perm_address_line1',       'validation_rules'=>'required|string|max:300',                  'is_required'=>1, 'display_order'=>10, 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-6'],
            ['field_name'=>'perm_address_line2',       'label'=>'Address Line 2',        'field_type'=>'text',     'target_column'=>'perm_address_line2',       'validation_rules'=>'nullable|string|max:300',                  'is_required'=>0, 'display_order'=>11, 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-6'],
            ['field_name'=>'perm_city',                'label'=>'City',                  'field_type'=>'text',     'target_column'=>'perm_city',                'validation_rules'=>'required|string|max:100',                  'is_required'=>1, 'display_order'=>12, 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'perm_state_id',            'label'=>'State',                 'field_type'=>'select',   'target_column'=>'perm_state_id',            'validation_rules'=>'required|exists:state_masters,id',         'is_required'=>1, 'display_order'=>13, 'lookup_table'=>'state_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'state_name', 'lookup_order_column'=>'state_name', 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'perm_pincode',             'label'=>'Pincode',               'field_type'=>'text',     'target_column'=>'perm_pincode',             'validation_rules'=>'required|digits:6',                        'is_required'=>1, 'display_order'=>14, 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'perm_country_id',          'label'=>'Country',               'field_type'=>'select',   'target_column'=>'perm_country_id',          'validation_rules'=>'required|exists:country_masters,id',       'is_required'=>1, 'display_order'=>15, 'lookup_table'=>'country_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'country_name', 'lookup_order_column'=>'country_name', 'section_heading'=>'Permanent Address', 'css_class'=>'col-md-3'],

            // Present Address
            ['field_name'=>'same_as_permanent',        'label'=>'Same as Permanent Address', 'field_type'=>'checkbox', 'target_column'=>'same_as_permanent',    'validation_rules'=>'nullable|boolean',                         'is_required'=>0, 'display_order'=>16, 'section_heading'=>'Present Address', 'css_class'=>'col-md-12'],
            ['field_name'=>'pres_address_line1',       'label'=>'Address Line 1',        'field_type'=>'text',     'target_column'=>'pres_address_line1',       'validation_rules'=>'required_without:same_as_permanent|nullable|string|max:300', 'is_required'=>0, 'display_order'=>17, 'section_heading'=>'Present Address', 'css_class'=>'col-md-6'],
            ['field_name'=>'pres_address_line2',       'label'=>'Address Line 2',        'field_type'=>'text',     'target_column'=>'pres_address_line2',       'validation_rules'=>'nullable|string|max:300',                  'is_required'=>0, 'display_order'=>18, 'section_heading'=>'Present Address', 'css_class'=>'col-md-6'],
            ['field_name'=>'pres_city',                'label'=>'City',                  'field_type'=>'text',     'target_column'=>'pres_city',                'validation_rules'=>'required_without:same_as_permanent|nullable|string|max:100', 'is_required'=>0, 'display_order'=>19, 'section_heading'=>'Present Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'pres_state_id',            'label'=>'State',                 'field_type'=>'select',   'target_column'=>'pres_state_id',            'validation_rules'=>'required_without:same_as_permanent|nullable|exists:state_masters,id', 'is_required'=>0, 'display_order'=>20, 'lookup_table'=>'state_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'state_name', 'lookup_order_column'=>'state_name', 'section_heading'=>'Present Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'pres_pincode',             'label'=>'Pincode',               'field_type'=>'text',     'target_column'=>'pres_pincode',             'validation_rules'=>'required_without:same_as_permanent|nullable|digits:6', 'is_required'=>0, 'display_order'=>21, 'section_heading'=>'Present Address', 'css_class'=>'col-md-3'],
            ['field_name'=>'pres_country_id',          'label'=>'Country',               'field_type'=>'select',   'target_column'=>'pres_country_id',          'validation_rules'=>'required_without:same_as_permanent|nullable|exists:country_masters,id', 'is_required'=>0, 'display_order'=>22, 'lookup_table'=>'country_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'country_name', 'lookup_order_column'=>'country_name', 'section_heading'=>'Present Address', 'css_class'=>'col-md-3'],

            // Emergency Contact
            ['field_name'=>'emergency_contact_name',   'label'=>'Emergency Contact Name',     'field_type'=>'text', 'target_column'=>'emergency_contact_name',   'validation_rules'=>'required|string|max:200',  'is_required'=>1, 'display_order'=>23, 'section_heading'=>'Emergency Contact', 'css_class'=>'col-md-4'],
            ['field_name'=>'emergency_contact_relation','label'=>'Relation',                  'field_type'=>'text', 'target_column'=>'emergency_contact_relation','validation_rules'=>'required|string|max:100', 'is_required'=>1, 'display_order'=>24, 'section_heading'=>'Emergency Contact', 'css_class'=>'col-md-4'],
            ['field_name'=>'emergency_contact_mobile', 'label'=>'Emergency Contact Mobile',   'field_type'=>'text', 'target_column'=>'emergency_contact_mobile', 'validation_rules'=>'required|digits:10',       'is_required'=>1, 'display_order'=>25, 'placeholder'=>'10-digit mobile number', 'section_heading'=>'Emergency Contact', 'css_class'=>'col-md-4'],

            // Father
            ['field_name'=>'father_profession_id',     'label'=>"Father's Profession",        'field_type'=>'select', 'target_column'=>'father_profession_id',   'validation_rules'=>'nullable|exists:father_professions,id', 'is_required'=>0, 'display_order'=>26, 'lookup_table'=>'father_professions', 'lookup_value_column'=>'id', 'lookup_label_column'=>'profession_name', 'section_heading'=>"Father's Profession", 'css_class'=>'col-md-6'],
            ['field_name'=>'father_occupation_details', 'label'=>'Occupation Details',         'field_type'=>'text',   'target_column'=>'father_occupation_details','validation_rules'=>'nullable|string|max:300', 'is_required'=>0, 'display_order'=>27, 'section_heading'=>"Father's Profession", 'css_class'=>'col-md-6'],
        ];

        foreach ($fields as $f) {
            $f['step_id']      = $step->id;
            $f['target_table'] = $t;
            FcFormField::create($f);
        }
    }

    private function seedStep3Groups(FcFormStep $step): void
    {
        // ── Qualifications ──
        $gQual = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'qualifications', 'group_label'=>'Educational Qualifications', 'target_table'=>'student_master_qualification_details', 'save_mode'=>'replace_all', 'min_rows'=>1, 'max_rows'=>10, 'display_order'=>1]);
        $this->createGroupFields($gQual, [
            ['field_name'=>'qualification_id', 'label'=>'Qualification', 'field_type'=>'select',  'target_column'=>'qualification_id', 'validation_rules'=>'required|exists:qualification_masters,id', 'is_required'=>1, 'display_order'=>1, 'lookup_table'=>'qualification_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'qualification_name', 'css_class'=>'col-md-4'],
            ['field_name'=>'degree_name',      'label'=>'Degree Name',   'field_type'=>'text',    'target_column'=>'degree_name',      'validation_rules'=>'required|string|max:200', 'is_required'=>1, 'display_order'=>2, 'css_class'=>'col-md-4'],
            ['field_name'=>'board_id',         'label'=>'Board/University','field_type'=>'select', 'target_column'=>'board_id',         'validation_rules'=>'nullable|exists:board_name_masters,id', 'is_required'=>0, 'display_order'=>3, 'lookup_table'=>'board_name_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'board_name', 'css_class'=>'col-md-4'],
            ['field_name'=>'institution_name', 'label'=>'Institution',   'field_type'=>'text',    'target_column'=>'institution_name', 'validation_rules'=>'required|string|max:300', 'is_required'=>1, 'display_order'=>4, 'css_class'=>'col-md-4'],
            ['field_name'=>'year_of_passing',  'label'=>'Year of Passing','field_type'=>'text',   'target_column'=>'year_of_passing',  'validation_rules'=>'required|digits:4',       'is_required'=>1, 'display_order'=>5, 'css_class'=>'col-md-2'],
            ['field_name'=>'percentage_cgpa',  'label'=>'Percentage/CGPA','field_type'=>'text',   'target_column'=>'percentage_cgpa',  'validation_rules'=>'required|string|max:20',  'is_required'=>1, 'display_order'=>6, 'css_class'=>'col-md-2'],
            ['field_name'=>'stream_id',        'label'=>'Stream',         'field_type'=>'select', 'target_column'=>'stream_id',        'validation_rules'=>'nullable|exists:highest_stream_masters,id', 'is_required'=>0, 'display_order'=>7, 'lookup_table'=>'highest_stream_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'stream_name', 'css_class'=>'col-md-4'],
            ['field_name'=>'subject_details',  'label'=>'Subject Details','field_type'=>'text',   'target_column'=>'subject_details',  'validation_rules'=>'nullable|string|max:500', 'is_required'=>0, 'display_order'=>8, 'css_class'=>'col-md-4'],
        ]);

        // ── Higher Education ──
        $gHigher = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'higher_education', 'group_label'=>'Higher Education', 'target_table'=>'student_master_higher_educational_details', 'save_mode'=>'replace_all', 'min_rows'=>0, 'max_rows'=>10, 'display_order'=>2]);
        $this->createGroupFields($gHigher, [
            ['field_name'=>'degree_type',     'label'=>'Degree Type',   'field_type'=>'select', 'target_column'=>'degree_type',     'validation_rules'=>'required|exists:degree_master,pk', 'is_required'=>1, 'display_order'=>1, 'lookup_table'=>'degree_master', 'lookup_value_column'=>'pk', 'lookup_label_column'=>'degree_name', 'css_class'=>'col-md-3'],
            ['field_name'=>'subject_name',    'label'=>'Subject',      'field_type'=>'text',   'target_column'=>'subject_name',    'validation_rules'=>'nullable|string|max:200', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-3'],
            ['field_name'=>'university_name', 'label'=>'University',   'field_type'=>'text',   'target_column'=>'university_name', 'validation_rules'=>'required|string|max:300', 'is_required'=>1, 'display_order'=>3, 'css_class'=>'col-md-3'],
            ['field_name'=>'year_of_passing', 'label'=>'Year',         'field_type'=>'text',   'target_column'=>'year_of_passing', 'validation_rules'=>'required|digits:4',       'is_required'=>1, 'display_order'=>4, 'css_class'=>'col-md-2'],
            ['field_name'=>'percentage_cgpa', 'label'=>'%/CGPA',       'field_type'=>'text',   'target_column'=>'percentage_cgpa', 'validation_rules'=>'nullable|string|max:20',  'is_required'=>0, 'display_order'=>5, 'css_class'=>'col-md-1'],
        ]);

        // ── Employment ──
        $gEmploy = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'employment', 'group_label'=>'Employment History', 'target_table'=>'student_master_employment_details', 'save_mode'=>'replace_all', 'min_rows'=>0, 'max_rows'=>20, 'display_order'=>3]);
        $this->createGroupFields($gEmploy, [
            ['field_name'=>'organisation_name', 'label'=>'Organisation',  'field_type'=>'text',     'target_column'=>'organisation_name', 'validation_rules'=>'required|string|max:300', 'is_required'=>1, 'display_order'=>1, 'css_class'=>'col-md-4'],
            ['field_name'=>'designation',       'label'=>'Designation',   'field_type'=>'text',     'target_column'=>'designation',       'validation_rules'=>'required|string|max:200', 'is_required'=>1, 'display_order'=>2, 'css_class'=>'col-md-3'],
            ['field_name'=>'job_type_id',       'label'=>'Job Type',      'field_type'=>'select',   'target_column'=>'job_type_id',       'validation_rules'=>'nullable|exists:job_type_masters,id', 'is_required'=>0, 'display_order'=>3, 'lookup_table'=>'job_type_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'job_type_name', 'css_class'=>'col-md-3'],
            ['field_name'=>'from_date',         'label'=>'From Date',     'field_type'=>'date',     'target_column'=>'from_date',         'validation_rules'=>'required|date',           'is_required'=>1, 'display_order'=>4, 'css_class'=>'col-md-3'],
            ['field_name'=>'to_date',           'label'=>'To Date',       'field_type'=>'date',     'target_column'=>'to_date',           'validation_rules'=>'nullable|date',           'is_required'=>0, 'display_order'=>5, 'css_class'=>'col-md-3'],
            ['field_name'=>'is_current',        'label'=>'Currently Working', 'field_type'=>'checkbox', 'target_column'=>'is_current',    'validation_rules'=>'nullable|boolean',        'is_required'=>0, 'display_order'=>6, 'css_class'=>'col-md-2'],
        ]);

        // ── Spouse ──
        $gSpouse = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'spouse', 'group_label'=>'Spouse / Family', 'target_table'=>'student_master_spouse_masters', 'save_mode'=>'upsert', 'min_rows'=>0, 'max_rows'=>1, 'display_order'=>4]);
        $this->createGroupFields($gSpouse, [
            ['field_name'=>'spouse_name',         'label'=>'Spouse Name',       'field_type'=>'text', 'target_column'=>'spouse_name',         'validation_rules'=>'nullable|string|max:200', 'is_required'=>0, 'display_order'=>1, 'css_class'=>'col-md-6'],
            ['field_name'=>'spouse_dob',          'label'=>'Spouse DOB',        'field_type'=>'date', 'target_column'=>'spouse_dob',          'validation_rules'=>'nullable|date|before:today', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-3'],
            ['field_name'=>'spouse_occupation',   'label'=>'Occupation',        'field_type'=>'text', 'target_column'=>'spouse_occupation',   'validation_rules'=>'nullable|string|max:200', 'is_required'=>0, 'display_order'=>3, 'css_class'=>'col-md-3'],
            ['field_name'=>'spouse_organisation', 'label'=>'Organisation',      'field_type'=>'text', 'target_column'=>'spouse_organisation', 'validation_rules'=>'nullable|string|max:300', 'is_required'=>0, 'display_order'=>4, 'css_class'=>'col-md-6'],
            ['field_name'=>'no_of_children',      'label'=>'No. of Children',   'field_type'=>'text', 'target_column'=>'no_of_children',      'validation_rules'=>'nullable|string|max:10',  'is_required'=>0, 'display_order'=>5, 'css_class'=>'col-md-3'],
            ['field_name'=>'children_details',    'label'=>'Children Details',   'field_type'=>'textarea', 'target_column'=>'children_details','validation_rules'=>'nullable|string|max:500', 'is_required'=>0, 'display_order'=>6, 'css_class'=>'col-md-12'],
        ]);

        // ── Languages ──
        $gLang = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'languages', 'group_label'=>'Languages Known', 'target_table'=>'student_master_language_knowns', 'save_mode'=>'replace_all', 'min_rows'=>1, 'max_rows'=>10, 'display_order'=>5]);
        $this->createGroupFields($gLang, [
            ['field_name'=>'language_id',  'label'=>'Language',    'field_type'=>'select',   'target_column'=>'language_id',  'validation_rules'=>'required|exists:language_master,id', 'is_required'=>1, 'display_order'=>1, 'lookup_table'=>'language_master', 'lookup_value_column'=>'id', 'lookup_label_column'=>'language_name', 'css_class'=>'col-md-3'],
            ['field_name'=>'can_read',     'label'=>'Can Read',    'field_type'=>'checkbox', 'target_column'=>'can_read',     'validation_rules'=>'nullable|boolean', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-2'],
            ['field_name'=>'can_write',    'label'=>'Can Write',   'field_type'=>'checkbox', 'target_column'=>'can_write',    'validation_rules'=>'nullable|boolean', 'is_required'=>0, 'display_order'=>3, 'css_class'=>'col-md-2'],
            ['field_name'=>'can_speak',    'label'=>'Can Speak',   'field_type'=>'checkbox', 'target_column'=>'can_speak',    'validation_rules'=>'nullable|boolean', 'is_required'=>0, 'display_order'=>4, 'css_class'=>'col-md-2'],
            ['field_name'=>'proficiency',  'label'=>'Proficiency', 'field_type'=>'select',   'target_column'=>'proficiency',  'validation_rules'=>'nullable|in:Basic,Intermediate,Fluent', 'is_required'=>0, 'display_order'=>5, 'options_json'=>json_encode([['value'=>'Basic','label'=>'Basic'],['value'=>'Intermediate','label'=>'Intermediate'],['value'=>'Fluent','label'=>'Fluent']]), 'css_class'=>'col-md-3'],
        ]);

        // ── Hobbies ──
        $gHobbies = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'hobbies', 'group_label'=>'Hobbies & Skills', 'target_table'=>'student_master_hobbies_details', 'save_mode'=>'upsert', 'min_rows'=>0, 'max_rows'=>1, 'display_order'=>6]);
        $this->createGroupFields($gHobbies, [
            ['field_name'=>'hobbies',          'label'=>'Hobbies',              'field_type'=>'textarea', 'target_column'=>'hobbies',          'validation_rules'=>'nullable|string', 'is_required'=>0, 'display_order'=>1, 'css_class'=>'col-md-12'],
            ['field_name'=>'special_skills',   'label'=>'Special Skills',       'field_type'=>'textarea', 'target_column'=>'special_skills',   'validation_rules'=>'nullable|string', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-12'],
            ['field_name'=>'extra_curricular', 'label'=>'Extra-Curricular',     'field_type'=>'textarea', 'target_column'=>'extra_curricular', 'validation_rules'=>'nullable|string', 'is_required'=>0, 'display_order'=>3, 'css_class'=>'col-md-12'],
        ]);

        // ── Distinctions ──
        $gDist = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'distinctions', 'group_label'=>'Academic Distinctions', 'target_table'=>'student_master_academic_distinctions', 'save_mode'=>'replace_all', 'min_rows'=>0, 'max_rows'=>20, 'display_order'=>7]);
        $this->createGroupFields($gDist, [
            ['field_name'=>'distinction_type', 'label'=>'Type',           'field_type'=>'text', 'target_column'=>'distinction_type', 'validation_rules'=>'required|string|max:200', 'is_required'=>1, 'display_order'=>1, 'css_class'=>'col-md-3'],
            ['field_name'=>'description',      'label'=>'Description',    'field_type'=>'text', 'target_column'=>'description',      'validation_rules'=>'nullable|string|max:500', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-4'],
            ['field_name'=>'year',             'label'=>'Year',           'field_type'=>'text', 'target_column'=>'year',             'validation_rules'=>'nullable|digits:4',       'is_required'=>0, 'display_order'=>3, 'css_class'=>'col-md-2'],
            ['field_name'=>'awarding_body',    'label'=>'Awarding Body',  'field_type'=>'text', 'target_column'=>'awarding_body',    'validation_rules'=>'nullable|string|max:200', 'is_required'=>0, 'display_order'=>4, 'css_class'=>'col-md-3'],
        ]);

        // ── Sports Played ──
        $gSports = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'sports_played', 'group_label'=>'Sports', 'target_table'=>'student_sports_fitness_teach_masters', 'save_mode'=>'replace_all', 'min_rows'=>0, 'max_rows'=>20, 'display_order'=>8]);
        $this->createGroupFields($gSports, [
            ['field_name'=>'sport_id', 'label'=>'Sport',  'field_type'=>'select', 'target_column'=>'sport_id', 'validation_rules'=>'required|exists:sports_masters,id', 'is_required'=>1, 'display_order'=>1, 'lookup_table'=>'sports_masters', 'lookup_value_column'=>'id', 'lookup_label_column'=>'sport_name', 'css_class'=>'col-md-3'],
            ['field_name'=>'level',    'label'=>'Level',  'field_type'=>'select', 'target_column'=>'level',    'validation_rules'=>'nullable|string|max:100', 'is_required'=>0, 'display_order'=>2, 'options_json'=>json_encode([['value'=>'National','label'=>'National'],['value'=>'State','label'=>'State'],['value'=>'District','label'=>'District'],['value'=>'University','label'=>'University'],['value'=>'School','label'=>'School']]), 'css_class'=>'col-md-3'],
            ['field_name'=>'role',     'label'=>'Role',   'field_type'=>'select', 'target_column'=>'role',     'validation_rules'=>'nullable|string|max:100', 'is_required'=>0, 'display_order'=>3, 'options_json'=>json_encode([['value'=>'Player','label'=>'Player'],['value'=>'Captain','label'=>'Captain'],['value'=>'Coach','label'=>'Coach'],['value'=>'Manager','label'=>'Manager']]), 'css_class'=>'col-md-3'],
            ['field_name'=>'year',     'label'=>'Year',   'field_type'=>'text',   'target_column'=>'year',     'validation_rules'=>'nullable|digits:4',       'is_required'=>0, 'display_order'=>4, 'css_class'=>'col-md-3'],
        ]);

        // ── Module Choice ──
        $gModule = FcFormFieldGroup::create(['step_id'=>$step->id, 'group_name'=>'module', 'group_label'=>'Module Choice', 'target_table'=>'student_master_module_masters', 'save_mode'=>'upsert', 'min_rows'=>1, 'max_rows'=>1, 'display_order'=>9]);
        $this->createGroupFields($gModule, [
            ['field_name'=>'chosen_module', 'label'=>'Preferred Module',  'field_type'=>'text', 'target_column'=>'chosen_module', 'validation_rules'=>'required|string|max:100', 'is_required'=>1, 'display_order'=>1, 'css_class'=>'col-md-6'],
            ['field_name'=>'second_module', 'label'=>'Second Preference', 'field_type'=>'text', 'target_column'=>'second_module', 'validation_rules'=>'nullable|string|max:100', 'is_required'=>0, 'display_order'=>2, 'css_class'=>'col-md-6'],
        ]);
    }

    private function seedBankFields(FcFormStep $step): void
    {
        $t = 'new_registration_bank_details_masters';
        $fields = [
            ['field_name'=>'bank_name',           'label'=>'Bank Name',            'field_type'=>'text',   'target_column'=>'bank_name',           'validation_rules'=>'required|string|max:200',            'is_required'=>1, 'display_order'=>1, 'css_class'=>'col-md-6'],
            ['field_name'=>'branch_name',         'label'=>'Branch Name',          'field_type'=>'text',   'target_column'=>'branch_name',         'validation_rules'=>'required|string|max:200',            'is_required'=>1, 'display_order'=>2, 'css_class'=>'col-md-6'],
            ['field_name'=>'ifsc_code',           'label'=>'IFSC Code',            'field_type'=>'text',   'target_column'=>'ifsc_code',           'validation_rules'=>'required|string|max:20|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', 'is_required'=>1, 'display_order'=>3, 'placeholder'=>'e.g. SBIN0001234', 'css_class'=>'col-md-4'],
            ['field_name'=>'account_no',          'label'=>'Account Number',       'field_type'=>'text',   'target_column'=>'account_no',          'validation_rules'=>'required|string|max:50',             'is_required'=>1, 'display_order'=>4, 'css_class'=>'col-md-4'],
            ['field_name'=>'account_no_confirm',  'label'=>'Confirm Account No.',  'field_type'=>'text',   'target_column'=>'_skip',               'validation_rules'=>'required|same:account_no',           'is_required'=>1, 'display_order'=>5, 'css_class'=>'col-md-4'],
            ['field_name'=>'account_holder_name', 'label'=>'Account Holder Name',  'field_type'=>'text',   'target_column'=>'account_holder_name', 'validation_rules'=>'required|string|max:200',            'is_required'=>1, 'display_order'=>6, 'css_class'=>'col-md-6'],
            ['field_name'=>'account_type',        'label'=>'Account Type',         'field_type'=>'select', 'target_column'=>'account_type',        'validation_rules'=>'required|in:Savings,Current',        'is_required'=>1, 'display_order'=>7, 'options_json'=>json_encode([['value'=>'Savings','label'=>'Savings'],['value'=>'Current','label'=>'Current']]), 'css_class'=>'col-md-3'],
            ['field_name'=>'bank_passbook',       'label'=>'Bank Passbook Copy',   'field_type'=>'file',   'target_column'=>'bank_passbook_path',  'validation_rules'=>'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048', 'is_required'=>0, 'display_order'=>8, 'file_max_kb'=>2048, 'file_extensions'=>'jpeg,jpg,png,pdf', 'help_text'=>'Upload passbook front page copy. Max 2MB.', 'css_class'=>'col-md-6'],
        ];

        foreach ($fields as $f) {
            $f['step_id']      = $step->id;
            $f['target_table'] = $t;
            FcFormField::create($f);
        }
    }

    private function createGroupFields(FcFormFieldGroup $group, array $fields): void
    {
        foreach ($fields as $f) {
            $f['group_id'] = $group->id;
            FcFormGroupField::create($f);
        }
    }
}
