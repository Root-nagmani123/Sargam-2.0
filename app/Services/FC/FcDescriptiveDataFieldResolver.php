<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the Descriptive Data report's logical columns to real tables/columns for ONE form.
 *
 * Why this exists: the FC registration schema is form-driven. Every form maps its own fields
 * through fc_form_fields.target_table/target_column, so two active courses genuinely differ —
 * form 21 ("Foundation Course 101") stores Nationality in student_master_seconds.nationality
 * and splits the trainee's name into first/middle/last, while form 1 has neither column mapped
 * and keeps a single full_name. A report with a hardcoded column list therefore renders empty
 * cells on one course and errors on another.
 *
 * So a logical column is emitted only when BOTH are true:
 *   1. the selected form actually maps that table.column (fc_form_fields), and
 *   2. the column exists in the database (fc_schema_has_column).
 * Anything else is silently dropped from that course's report rather than faked.
 *
 * Lookup metadata (religion_master.pk -> religion_name, caste_category_master.pk -> Seat_name,
 * ...) is read from the same form definition instead of being restated here, so renaming a
 * lookup in the form builder does not need a code change.
 */
class FcDescriptiveDataFieldResolver
{
    /** Cache the per-form resolution; the form definition only changes in the form builder. */
    private const CACHE_TTL_MINUTES = 60;

    /**
     * The report's columns, in display order.
     *
     * `source` is the join alias: s1 = student_master_firsts, s2 = student_master_seconds.
     * `columns` lists every physical column the field needs — a 'concat' field needs all of
     * them present before it can be shown at all.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function definition(): array
    {
        return [
            // ── Personal details ──────────────────────────────────────────
            'full_name_hindi' => ['label' => 'Full Name (Hindi)',      'group' => 'Personal Details', 'source' => 's1', 'columns' => ['full_name_hindi'], 'type' => 'text'],
            // Older courses map one `full_name` column instead of the three-part name, so both
            // shapes are declared and each course shows whichever its form actually maps.
            'full_name'       => ['label' => 'Full Name (English)',   'group' => 'Personal Details', 'source' => 's1', 'columns' => ['full_name'],      'type' => 'text'],
            'first_name'      => ['label' => 'First Name',            'group' => 'Personal Details', 'source' => 's1', 'columns' => ['first_name'],     'type' => 'text'],
            'middle_name'     => ['label' => 'Middle Name',           'group' => 'Personal Details', 'source' => 's1', 'columns' => ['middle_name'],    'type' => 'text'],
            'last_name'       => ['label' => 'Last Name / Surname',   'group' => 'Personal Details', 'source' => 's1', 'columns' => ['last_name'],      'type' => 'text'],

            // ── Service. NOT form-mapped: no FC form declares these in steps 1-2, they come
            //    off the registration roster. `derived` fields are exempt from the
            //    "form must map it" rule and are gated on schema availability instead. ──
            'service'         => ['label' => 'Service',                'group' => 'Service Details', 'type' => 'derived', 'derived' => 'service', 'filter' => 'service'],
            'rank'            => ['label' => 'Rank',                   'group' => 'Service Details', 'type' => 'derived', 'derived' => 'rank'],
            'cadre'           => ['label' => 'Cadre',                 'group' => 'Service Details', 'source' => 's1', 'columns' => ['cadre'],           'type' => 'text'],
            'allotted_state'  => ['label' => 'Allotted State',        'group' => 'Service Details', 'source' => 's1', 'columns' => ['allotted_state_id'], 'type' => 'lookup'],
            'session'         => ['label' => 'Session',               'group' => 'Service Details', 'source' => 's1', 'columns' => ['session_id'],      'type' => 'lookup'],
            'gender'          => ['label' => 'Gender',                'group' => 'Personal Details', 'source' => 's1', 'columns' => ['gender'],         'type' => 'text',   'filter' => 'select'],
            'date_of_birth'   => ['label' => 'Date Of Birth',         'group' => 'Personal Details', 'source' => 's1', 'columns' => ['date_of_birth'],  'type' => 'date',   'filter' => 'date_range'],
            'nationality'     => ['label' => 'Nationality',           'group' => 'Personal Details', 'source' => 's2', 'columns' => ['nationality'],    'type' => 'text',   'filter' => 'select'],
            'background'      => ['label' => 'Background',            'group' => 'Personal Details', 'source' => 's1', 'columns' => ['background'],     'type' => 'text'],
            'marital_status'  => ['label' => 'Marital Status',        'group' => 'Personal Details', 'source' => 's2', 'columns' => ['marital_status'], 'type' => 'text',   'filter' => 'select'],
            'religion'        => ['label' => 'Religion',              'group' => 'Personal Details', 'source' => 's2', 'columns' => ['religion_id'],    'type' => 'lookup'],
            'category'        => ['label' => 'Category',              'group' => 'Personal Details', 'source' => 's2', 'columns' => ['category_id'],    'type' => 'lookup', 'filter' => 'lookup'],
            'pan_card'        => ['label' => 'PAN Card',              'group' => 'Personal Details', 'source' => 's1', 'columns' => ['pan_card'],       'type' => 'text'],
            'aadhar_number'   => ['label' => 'Aadhar Number',         'group' => 'Personal Details', 'source' => 's1', 'columns' => ['aadhar_number'],  'type' => 'text'],
            'passport_no'     => ['label' => 'Passport No',           'group' => 'Personal Details', 'source' => 's1', 'columns' => ['passport_no'],    'type' => 'text'],

            // ── Birth place ───────────────────────────────────────────────
            'birth_state'     => ['label' => 'Birth State',           'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_state_id'],  'type' => 'lookup', 'filter' => 'lookup'],
            'birth_district'  => ['label' => 'Birth District',        'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_district'],  'type' => 'text'],
            'birth_area_type' => ['label' => 'Category (Area Type)',  'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_area_type'], 'type' => 'text'],
            'birth_city'      => ['label' => 'Village / City',        'group' => 'Birth Place Details', 'source' => 's2', 'columns' => ['birth_city'],      'type' => 'text'],

            // ── Contact ───────────────────────────────────────────────────
            'mobile_no'       => ['label' => 'Mobile No',             'group' => 'Contact Details', 'source' => 's1', 'columns' => ['mobile_no'],      'type' => 'text'],
            'alt_mobile_no'   => ['label' => 'Alternate Mobile No',   'group' => 'Contact Details', 'source' => 's1', 'columns' => ['alt_mobile_no'],  'type' => 'text'],
            'email'           => ['label' => 'Email Id',              'group' => 'Contact Details', 'source' => 's1', 'columns' => ['email'],          'type' => 'text'],
            'alt_email'       => ['label' => 'Alternate Email Id',    'group' => 'Contact Details', 'source' => 's1', 'columns' => ['alt_email'],      'type' => 'text'],
            'instagram_id'    => ['label' => 'Instagram ID',           'group' => 'Contact Details', 'source' => 's1', 'columns' => ['instagram_id'],   'type' => 'text'],
            'twitter_id'      => ['label' => 'Twitter ID',             'group' => 'Contact Details', 'source' => 's1', 'columns' => ['twitter_id'],     'type' => 'text'],

            // ── Parents. Built from the split name columns when the form maps them, else
            //    the single legacy column on student_master_firsts. ──────────
            'father_name'     => ['label' => "Father's Full Name",    'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_first_name', 'father_middle_name', 'father_last_name'], 'type' => 'concat', 'fallback' => ['source' => 's1', 'columns' => ['fathers_name']]],
            'mother_name'     => ['label' => "Mother's Full Name",    'group' => 'Family Details', 'source' => 's2', 'columns' => ['mother_first_name', 'mother_middle_name', 'mother_last_name'], 'type' => 'concat', 'fallback' => ['source' => 's1', 'columns' => ['mothers_name']]],

            'father_qualification' => ['label' => "Father's Qualification",  'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_qualification_id'], 'type' => 'lookup'],
            'father_profession'    => ['label' => "Father's Profession",     'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_profession_id'],    'type' => 'lookup'],
            'father_income'        => ['label' => "Father's Annual Income",  'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_annual_income'],    'type' => 'text'],
            'mother_qualification' => ['label' => "Mother's Qualification",  'group' => 'Family Details', 'source' => 's2', 'columns' => ['mother_qualification_id'], 'type' => 'lookup'],
            'mother_profession'    => ['label' => "Mother's Profession",     'group' => 'Family Details', 'source' => 's2', 'columns' => ['mother_profession_id'],    'type' => 'lookup'],
            'mother_income'        => ['label' => "Mother's Annual Income",  'group' => 'Family Details', 'source' => 's2', 'columns' => ['mother_annual_income'],    'type' => 'text'],
            'father_occupation_details' => ['label' => "Father's Occupation Details", 'group' => 'Family Details', 'source' => 's2', 'columns' => ['father_occupation_details'], 'type' => 'text'],

            // ── Guardian / spouse ─────────────────────────────────────────
            'guardian_relation' => ['label' => 'Guardian / Spouse',       'group' => 'Guardian Details', 'source' => 's2', 'columns' => ['guardian_or_spouse'],  'type' => 'text'],
            'guardian_name'     => ['label' => 'Guardian / Spouse Name',  'group' => 'Guardian Details', 'source' => 's2', 'columns' => ['guardian_first_name', 'guardian_middle_name', 'guardian_last_name'], 'type' => 'concat'],
            'guardian_contact'  => ['label' => 'Guardian Contact No',     'group' => 'Guardian Details', 'source' => 's2', 'columns' => ['guardian_contact_no'], 'type' => 'text'],
            'guardian_email'    => ['label' => 'Guardian E-mail ID',      'group' => 'Guardian Details', 'source' => 's2', 'columns' => ['guardian_email'],      'type' => 'text'],

            // ── Address (permanent), assembled into one readable cell ──────
            'perm_address'    => ['label' => 'Permanent Address',     'group' => 'Address', 'source' => 's2', 'columns' => ['perm_address_line1', 'perm_address_line2', 'perm_city', 'perm_district', 'perm_pincode'], 'type' => 'address', 'lookup_column' => 'perm_state_id', 'optional_columns' => ['perm_address_line2']],

            'perm_country'     => ['label' => 'Permanent Country',     'group' => 'Address', 'source' => 's2', 'columns' => ['perm_country_id'],  'type' => 'lookup'],
            'perm_city_name'   => ['label' => 'Permanent City / Town',  'group' => 'Address', 'source' => 's2', 'columns' => ['perm_city_name'],   'type' => 'text'],
            'domicile_state'   => ['label' => 'Domicile State',         'group' => 'Address', 'source' => 's2', 'columns' => ['domicile_state_id'], 'type' => 'lookup'],
            'domicile_district'=> ['label' => 'Domicile District',      'group' => 'Address', 'source' => 's2', 'columns' => ['domicile_district'], 'type' => 'text'],

            // Mailing address gets the same treatment as permanent: the free-text parts plus
            // the resolved state in one cell, with country and town alongside.
            'pres_address'     => ['label' => 'Mailing Address',        'group' => 'Address', 'source' => 's2', 'columns' => ['pres_address_line1', 'pres_address_line2', 'pres_city', 'pres_district', 'pres_pincode'], 'type' => 'address', 'lookup_column' => 'pres_state_id', 'optional_columns' => ['pres_address_line2']],
            'pres_country'     => ['label' => 'Mailing Country',        'group' => 'Address', 'source' => 's2', 'columns' => ['pres_country_id'],  'type' => 'lookup'],
            'pres_city_name'   => ['label' => 'Mailing City / Town',    'group' => 'Address', 'source' => 's2', 'columns' => ['pres_city_name'],   'type' => 'text'],

            // ── Physical details ──────────────────────────────────────────
            'height_cm'        => ['label' => 'Height (cm)',            'group' => 'Physical Details', 'source' => 's2', 'columns' => ['height_cm'],          'type' => 'text'],
            'weight_kg'        => ['label' => 'Weight (kg)',            'group' => 'Physical Details', 'source' => 's2', 'columns' => ['weight_kg'],          'type' => 'text'],
            'blood_group'      => ['label' => 'Blood Group',            'group' => 'Physical Details', 'source' => 's2', 'columns' => ['blood_group'],        'type' => 'text'],
            'dietary_pref'     => ['label' => 'Dietary Preference',     'group' => 'Physical Details', 'source' => 's2', 'columns' => ['dietary_preference'], 'type' => 'text'],
            'high_altitude'    => ['label' => 'High-Altitude Medical Condition', 'group' => 'Physical Details', 'source' => 's2', 'columns' => ['high_altitude_condition'], 'type' => 'text'],
            'high_altitude_remarks' => ['label' => 'High-Altitude Remarks',      'group' => 'Physical Details', 'source' => 's2', 'columns' => ['high_altitude_remarks'],   'type' => 'text'],

            'identification_mark1' => ['label' => 'Identification Mark 1', 'group' => 'Physical Details', 'source' => 's2', 'columns' => ['identification_mark1'], 'type' => 'text'],
            'identification_mark2' => ['label' => 'Identification Mark 2', 'group' => 'Physical Details', 'source' => 's2', 'columns' => ['identification_mark2'], 'type' => 'text'],

            // ── Emergency contact ─────────────────────────────────────────
            'emergency_contact_name'     => ['label' => 'Emergency Contact Name',   'group' => 'Emergency Contact', 'source' => 's2', 'columns' => ['emergency_contact_name'],     'type' => 'text'],
            'emergency_contact_relation' => ['label' => 'Emergency Contact Relation', 'group' => 'Emergency Contact', 'source' => 's2', 'columns' => ['emergency_contact_relation'], 'type' => 'text'],
            'emergency_contact_mobile'   => ['label' => 'Emergency Contact Mobile', 'group' => 'Emergency Contact', 'source' => 's2', 'columns' => ['emergency_contact_mobile'],   'type' => 'text'],

            // ── Education summary. A form GROUP whose target table is student_master_seconds,
            //    i.e. one row per trainee — so these are ordinary flat columns, not child
            //    rows, even though the PDF prints them under their own heading. ──
            'highest_stream'      => ['label' => 'Highest Qualification Stream', 'group' => 'Education Summary', 'source' => 's2', 'columns' => ['highest_stream_id'],  'type' => 'lookup'],
            'matric_state'        => ['label' => 'Matriculation State',          'group' => 'Education Summary', 'source' => 's2', 'columns' => ['matric_state_id'],    'type' => 'lookup'],
            'matric_district'     => ['label' => 'Matriculation District',       'group' => 'Education Summary', 'source' => 's2', 'columns' => ['matric_district'],    'type' => 'text'],
            'matric_city'         => ['label' => 'Matriculation City / Village', 'group' => 'Education Summary', 'source' => 's2', 'columns' => ['matric_city'],        'type' => 'text'],
            'matric_city_name'    => ['label' => 'Matriculation City / Town Name', 'group' => 'Education Summary', 'source' => 's2', 'columns' => ['matric_city_name'], 'type' => 'text'],
            'cse_attempts'        => ['label' => 'No. of CSE Attempts',          'group' => 'Education Summary', 'source' => 's2', 'columns' => ['cse_attempts'],       'type' => 'text'],
            'previous_service'    => ['label' => 'Previous Service Joined',      'group' => 'Education Summary', 'source' => 's2', 'columns' => ['previous_service_id'], 'type' => 'lookup'],
            'optional_subject_1'  => ['label' => 'Optional Subject First',       'group' => 'Education Summary', 'source' => 's2', 'columns' => ['optonal_subject_first'],  'type' => 'lookup'],
            'optional_subject_2'  => ['label' => 'Optional Subject Second',      'group' => 'Education Summary', 'source' => 's2', 'columns' => ['optional_subject_second'], 'type' => 'lookup'],

            // ── Language details (third table, s3) ────────────────────────
            'mother_tongue'    => ['label' => 'Mother Tongue',                  'group' => 'Language Details', 'source' => 's3', 'columns' => ['mother_tongue'],        'type' => 'lookup'],
            'medium_12th'      => ['label' => 'Medium in Class 12',             'group' => 'Language Details', 'source' => 's3', 'columns' => ['medium_12th'],          'type' => 'lookup'],
            'medium_graduation'=> ['label' => 'Medium in Graduation',           'group' => 'Language Details', 'source' => 's3', 'columns' => ['medium_graduation'],    'type' => 'lookup'],
            'medium_civil'     => ['label' => 'Medium in Civil Service Exam',   'group' => 'Language Details', 'source' => 's3', 'columns' => ['medium_civil_service'], 'type' => 'lookup'],
            'viva_language'    => ['label' => 'Civil Service Viva Language',    'group' => 'Language Details', 'source' => 's3', 'columns' => ['viva_language'],        'type' => 'lookup'],
            // Knowledge of Hindi — also a group on s3, one row per trainee.
            'hindi_matric'     => ['label' => 'Passed Matric with Hindi',        'group' => 'Knowledge of Hindi', 'source' => 's3', 'columns' => ['passed_matric_hindi'], 'type' => 'text'],
            'hindi_cse'        => ['label' => 'Selected in CSE with Hindi',      'group' => 'Knowledge of Hindi', 'source' => 's3', 'columns' => ['selected_cse_hindi'],  'type' => 'text'],
            'hindi_mother_tongue' => ['label' => 'Hindi is Mother Tongue',       'group' => 'Knowledge of Hindi', 'source' => 's3', 'columns' => ['hindi_mother_tongue'], 'type' => 'text'],

            // ── Repeating sections ────────────────────────────────────────
            // These live in child tables with MANY rows per trainee (a trainee has several
            // qualifications, several languages). They are NOT joined: a LEFT JOIN on a
            // non-unique key multiplies the driving row, so one trainee would appear once per
            // qualification in the table and in every export. Instead each is fetched in one
            // batched query per child table after the page/export rows are read, and the
            // child rows are collapsed into a single cell joined by " | ".
            //
            // Every column of one group keeps the SAME child-row order, so the n-th item in
            // "Degree" belongs with the n-th item in "University / Board Name".
            'lang_known'        => ['label' => 'Language Known',        'group' => 'Languages Known', 'type' => 'child', 'child' => ['table' => 'student_master_language_knowns', 'column' => 'language_id']],
            'lang_speak'        => ['label' => 'Language — Speak',      'group' => 'Languages Known', 'type' => 'child', 'child' => ['table' => 'student_master_language_knowns', 'column' => 'can_speak'], 'child_format' => 'bool'],
            'lang_read'         => ['label' => 'Language — Read',       'group' => 'Languages Known', 'type' => 'child', 'child' => ['table' => 'student_master_language_knowns', 'column' => 'can_read'], 'child_format' => 'bool'],
            'lang_write'        => ['label' => 'Language — Write',      'group' => 'Languages Known', 'type' => 'child', 'child' => ['table' => 'student_master_language_knowns', 'column' => 'can_write'], 'child_format' => 'bool'],

            'edu_degree'        => ['label' => 'Education — Degree',            'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'qualification_id']],
            'edu_board'         => ['label' => 'Education — University / Board', 'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'board_id']],
            'edu_institution'   => ['label' => 'Education — Institution',       'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'institution_name']],
            'edu_inst_type'     => ['label' => 'Education — Institution Type',  'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'institution_type']],
            'edu_from_year'     => ['label' => 'Education — From Year',         'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'year_of_passing']],
            'edu_to_year'       => ['label' => 'Education — To Year',           'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'to_year']],
            'edu_division'      => ['label' => 'Education — Division',          'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'division']],
            'edu_percentage'    => ['label' => 'Education — Percentage (%)',    'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'percentage_cgpa']],
            'edu_subjects'      => ['label' => 'Education — Subjects',          'group' => 'Educational Details', 'type' => 'child', 'child' => ['table' => 'student_master_qualification_details', 'column' => 'subject_details']],

            'job_organisation'  => ['label' => 'Previous Job — Organization', 'group' => 'Previous Job Experience', 'type' => 'child', 'child' => ['table' => 'student_master_employment_details', 'column' => 'organisation_name']],
            'job_designation'   => ['label' => 'Previous Job — Designation',  'group' => 'Previous Job Experience', 'type' => 'child', 'child' => ['table' => 'student_master_employment_details', 'column' => 'designation']],
            'job_from'          => ['label' => 'Previous Job — Period From',  'group' => 'Previous Job Experience', 'type' => 'child', 'child' => ['table' => 'student_master_employment_details', 'column' => 'from_date'], 'child_format' => 'date'],
            'job_to'            => ['label' => 'Previous Job — Period To',    'group' => 'Previous Job Experience', 'type' => 'child', 'child' => ['table' => 'student_master_employment_details', 'column' => 'to_date'], 'child_format' => 'date'],
            'job_nature'        => ['label' => 'Previous Job — Nature of Job', 'group' => 'Previous Job Experience', 'type' => 'child', 'child' => ['table' => 'student_master_employment_details', 'column' => 'job_type_id']],

            'academic_distinction' => ['label' => 'Academic Distinction', 'group' => 'Academic Distinction', 'type' => 'child', 'child' => ['table' => 'student_master_academic_distinctions', 'column' => 'distinction_type']],
            'hobbies'              => ['label' => 'Hobbies',              'group' => 'Hobbies',              'type' => 'child', 'child' => ['table' => 'student_master_hobbies_details', 'column' => 'hobbies']],

            'dress_shirt'       => ['label' => 'T-shirt Size',        'group' => 'Dress Code', 'type' => 'child', 'child' => ['table' => 'student_cloth_size_master_details', 'column' => 'shirt_size']],
            'dress_blazer'      => ['label' => 'Blazer / Jacket Size', 'group' => 'Dress Code', 'type' => 'child', 'child' => ['table' => 'student_cloth_size_master_details', 'column' => 'blazer_size']],
            'dress_trouser'     => ['label' => 'Trouser Size',        'group' => 'Dress Code', 'type' => 'child', 'child' => ['table' => 'student_cloth_size_master_details', 'column' => 'trouser_size']],
            'dress_track_suit'  => ['label' => 'Track Suit Size',     'group' => 'Dress Code', 'type' => 'child', 'child' => ['table' => 'student_cloth_size_master_details', 'column' => 'track_suit_size']],

            'spouse_in_cse'     => ['label' => 'Spouse Also in Civil Service', 'group' => 'Spouse in Civil Service', 'type' => 'child', 'child' => ['table' => 'student_master_spouse_masters', 'column' => 'spouse_in_cse']],
            'spouse_name'       => ['label' => 'Spouse Name',                 'group' => 'Spouse in Civil Service', 'type' => 'child', 'child' => ['table' => 'student_master_spouse_masters', 'column' => 'spouse_name']],

            // Pre-Medical History (fc_pre_history) is deliberately NOT here — excluded by
            // request, and it is course-scoped health data that does not belong in a bulk
            // roster export.

            // ── Uploads, rendered as links ────────────────────────────────
            'photo_path'      => ['label' => 'Photo',                 'group' => 'Uploads', 'source' => 's1', 'columns' => ['photo_path'],     'type' => 'file'],
            'signature_path'  => ['label' => 'Signature',             'group' => 'Uploads', 'source' => 's1', 'columns' => ['signature_path'], 'type' => 'file'],
        ];
    }

    private const SOURCE_TABLES = [
        's1' => 'student_master_firsts',
        's2' => 'student_master_seconds',
        // Language Details live in their own table, keyed 1:1 on user_id by a UNIQUE index.
        's3' => 'student_knowledge_hindi_masters',
    ];

    /**
     * The columns this form can actually show, each resolved to a physical table/column
     * (+ lookup metadata where the form declares one).
     *
     * @return array<string,array<string,mixed>>
     */
    public function forForm(FcForm $form): array
    {
        $key = self::cacheKey('fields', (int) $form->id);

        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache store unavailable — resolve inline rather than fail the report.
        }

        $resolved = $this->resolve($form);

        try {
            Cache::put($key, $resolved, now()->addMinutes(self::CACHE_TTL_MINUTES));
        } catch (\Throwable $e) {
            // Not worth failing the report over.
        }

        return $resolved;
    }

    /**
     * Invalidate everything this report caches for one form — the column resolution AND the
     * filter dropdown values. Called from the form builder whenever a field changes; without
     * it a newly-mapped column stays invisible for up to the TTL.
     *
     * Implemented by bumping a per-form generation counter that both cache keys embed, rather
     * than forgetting keys directly: the filter-options key also hashes the resolved field
     * list, which cannot be reconstructed here, and this project's cache driver is `file`,
     * which does not support tags. Incrementing one counter orphans every derived key at once.
     */
    public static function forgetForm(int $formId): void
    {
        if ($formId <= 0) {
            return;
        }

        try {
            Cache::forever(self::generationKey($formId), self::generation($formId) + 1);
        } catch (\Throwable $e) {
            // Best effort — a stale entry expires on its own TTL anyway.
        }
    }

    /** Cache key for one of this report's per-form payloads, pinned to the current generation. */
    public static function cacheKey(string $bucket, int $formId, string $suffix = ''): string
    {
        return 'fc_desc_data:'.$bucket.':'.$formId
            .':g'.self::generation($formId)
            .':'.self::definitionFingerprint()
            .($suffix !== '' ? ':'.$suffix : '');
    }

    private static function generationKey(int $formId): string
    {
        return 'fc_desc_data_gen:'.$formId;
    }

    private static function generation(int $formId): int
    {
        try {
            return (int) (Cache::get(self::generationKey($formId), 0));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Keyed on the definition itself so editing definition() invalidates every cached form. */
    private static function definitionFingerprint(): string
    {
        return substr(md5(serialize(array_keys(self::definition()))), 0, 8);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function resolve(FcForm $form): array
    {
        $declared = $this->declaredFields($form);
        $out = [];

        $frmJoined = $this->registrationMasterIsJoined($form);

        foreach (self::definition() as $key => $def) {
            if (($def['type'] ?? '') === 'derived') {
                $entry = $this->resolveDerived($key, $def, $frmJoined);
                if ($entry !== null) {
                    $out[$key] = $entry;
                }
                continue;
            }

            if (($def['type'] ?? '') === 'child') {
                $entry = $this->resolveChild($key, $def, $declared);
                if ($entry !== null) {
                    $out[$key] = $entry;
                }
                continue;
            }

            $source = $def['source'];
            $columns = $def['columns'];

            // Optional parts (e.g. address line 2) are dropped when a course does not map
            // them. Without this the whole address column would vanish for those courses.
            $optional = $def['optional_columns'] ?? [];
            if ($optional !== []) {
                $columns = array_values(array_filter(
                    $columns,
                    fn ($column) => ! in_array($column, $optional, true)
                        || $this->allUsable($source, [$column], $declared)
                ));
            }

            if (! $this->allUsable($source, $columns, $declared)) {
                // Try the legacy single-column fallback (e.g. fathers_name) before giving up.
                $fallback = $def['fallback'] ?? null;
                if ($fallback === null || ! $this->allUsable($fallback['source'], $fallback['columns'], $declared)) {
                    continue;
                }
                $source = $fallback['source'];
                $columns = $fallback['columns'];
                $def['type'] = 'text';
            }

            $entry = [
                'key' => $key,
                'label' => $def['label'],
                'group' => $def['group'],
                'type' => $def['type'],
                'table' => self::SOURCE_TABLES[$source],
                'alias' => $source,
                'columns' => $columns,
                'filter' => $def['filter'] ?? null,
            ];

            // A lookup/address column needs the form's own lookup metadata to render a name
            // instead of a numeric id.
            $lookupColumn = $def['lookup_column'] ?? $columns[0];
            $meta = $declared[self::SOURCE_TABLES[$source].'.'.$lookupColumn] ?? null;
            if ($meta && $meta->lookup_table && $meta->lookup_value_column && $meta->lookup_label_column
                && fc_schema_has_table($meta->lookup_table)
                && fc_schema_has_column($meta->lookup_table, $meta->lookup_value_column)
                && fc_schema_has_column($meta->lookup_table, $meta->lookup_label_column)
                // A LEFT JOIN on a non-unique column MULTIPLIES rows — the same trainee would
                // appear once per matching lookup row, silently, in the table and every export.
                // The form builder lets an admin point lookup_value_column at any column, so
                // this is checked rather than assumed. Every current lookup joins on a PK.
                && self::columnIsUniquelyIndexed($meta->lookup_table, $meta->lookup_value_column)) {
                $entry['lookup'] = [
                    'table' => $meta->lookup_table,
                    'value' => $meta->lookup_value_column,
                    'label' => $meta->lookup_label_column,
                    'column' => $lookupColumn,
                ];
            } elseif ($entry['type'] === 'lookup') {
                // Declared as a lookup but the target is missing — show the raw value rather
                // than emit a join against a table that does not exist.
                $entry['type'] = 'text';
                $entry['filter'] = $entry['filter'] === 'lookup' ? 'select' : $entry['filter'];
            }

            $out[$key] = $entry;
        }

        return $out;
    }

    /**
     * Is this column safe to LEFT JOIN on — i.e. covered by a single-column PRIMARY or UNIQUE
     * index, so at most one row can match?
     *
     * Multi-column unique indexes do not count: uniqueness of (a, b) says nothing about a.
     * Cached for an hour and keyed per table; the answer only changes with a schema migration.
     */
    public static function columnIsUniquelyIndexed(string $table, string $column): bool
    {
        try {
            $unique = Cache::remember('fc_desc_data_uniqcols:'.$table, now()->addHour(), function () use ($table) {
                // $table is already allowlisted by fc_schema_has_table() before we get here.
                $rows = DB::select('SHOW INDEX FROM `'.str_replace('`', '', $table).'` WHERE Non_unique = 0');

                $byIndex = [];
                foreach ($rows as $row) {
                    $byIndex[$row->Key_name][] = $row->Column_name;
                }

                // Keep only indexes made of exactly one column.
                return array_values(array_map(
                    fn ($cols) => $cols[0],
                    array_filter($byIndex, fn ($cols) => count($cols) === 1)
                ));
            });
        } catch (\Throwable $e) {
            // Cannot prove uniqueness → do not risk duplicating rows.
            return false;
        }

        return in_array($column, (array) $unique, true);
    }

    /**
     * Service / Rank come off the registration roster, not the form definition, so they are
     * resolved from schema availability alone.
     *
     * @return array<string,mixed>|null  null when the underlying data cannot be reached
     */
    private function resolveDerived(string $key, array $def, bool $frmJoined): ?array
    {
        $entry = [
            'key' => $key,
            'label' => $def['label'],
            'group' => $def['group'],
            'type' => 'derived',
            'derived' => $def['derived'],
            'table' => null,
            'alias' => null,
            'columns' => [],
            'filter' => $def['filter'] ?? null,
        ];

        if ($def['derived'] === 'service') {
            if (! fc_schema_has_table('service_master')) {
                return null;
            }

            // Either source will do; the SELECT coalesces whichever is populated.
            $fromS1 = fc_schema_has_column('student_master_firsts', 'service_id');
            $fromFrm = $frmJoined && fc_schema_has_column('fc_registration_master', 'service_master_pk');
            if (! $fromS1 && ! $fromFrm) {
                return null;
            }

            $entry['sources'] = ['s1' => $fromS1, 'frm' => $fromFrm];
            // Ordering by the resolved name is meaningful; the expression is built in
            // FcDescriptiveDataQuery so the two cannot disagree.
            $entry['orderable'] = true;

            return $entry;
        }

        if ($def['derived'] === 'rank') {
            if (! $frmJoined || ! fc_schema_has_column('fc_registration_master', 'rank')) {
                return null;
            }

            $entry['alias'] = 'frm';
            $entry['columns'] = ['rank'];
            $entry['orderable'] = true;

            return $entry;
        }

        return null;
    }

    /**
     * Is `frm` (fc_registration_master) actually in the query?
     *
     * fc_report_apply_tracker_user_resolution() only joins it when the tracker keys on
     * user_id — a legacy username-keyed form (e.g. form 16) never gets the alias, and naming
     * frm there would be invalid SQL. Checked rather than assumed.
     */
    private function registrationMasterIsJoined(FcForm $form): bool
    {
        return fc_user_col($form->trackerStorageTable()) === 'user_id'
            && fc_schema_has_table('fc_registration_master');
    }

    /** Every column must be mapped by the form AND present in the database. */
    private function allUsable(string $source, array $columns, array $declared): bool
    {
        $table = self::SOURCE_TABLES[$source] ?? null;
        if ($table === null || ! fc_schema_has_table($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! isset($declared[$table.'.'.$column]) || ! fc_schema_has_column($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * "table.column" => field row, for every active field this form maps.
     *
     * Explicit column list and a single query (G1/G4) — the report never asks the form
     * definition anything again after this.
     *
     * @return array<string,object>
     */
    private function declaredFields(FcForm $form): array
    {
        $rows = DB::table('fc_form_fields as f')
            ->join('fc_form_steps as s', 'f.step_id', '=', 's.id')
            ->where('s.form_id', $form->id)
            ->where('f.is_active', 1)
            ->where('s.is_active', 1)
            ->whereNotNull('f.target_table')
            ->whereNotNull('f.target_column')
            ->get([
                'f.target_table',
                'f.target_column',
                'f.lookup_table',
                'f.lookup_value_column',
                'f.lookup_label_column',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->target_table.'.'.$row->target_column] = $row;
        }

        // Repeating sections (Educational Details, Languages Known, ...) and the two 1:1
        // sections that render as their own PDF heading (Education Summary, Knowledge of
        // Hindi) are declared in fc_form_group_fields, NOT fc_form_fields. Without this
        // second query none of them would pass the "the form must map it" gate, and their
        // lookup metadata (which master table renders qualification_id as a degree name)
        // would be unavailable.
        $groupRows = DB::table('fc_form_group_fields as gf')
            ->join('fc_form_field_groups as g', 'gf.group_id', '=', 'g.id')
            ->join('fc_form_steps as s', 'g.step_id', '=', 's.id')
            ->where('s.form_id', $form->id)
            ->where('gf.is_active', 1)
            ->where('g.is_active', 1)
            ->where('s.is_active', 1)
            ->whereNotNull('g.target_table')
            ->whereNotNull('gf.target_column')
            ->get([
                'g.target_table',
                'gf.target_column',
                'gf.lookup_table',
                'gf.lookup_value_column',
                'gf.lookup_label_column',
            ]);

        foreach ($groupRows as $row) {
            // A flat field wins if both declare the same column — it is the one the report
            // already renders and its metadata has been in use longer.
            $map[$row->target_table.'.'.$row->target_column] ??= $row;
        }

        return $map;
    }

    /**
     * A repeating (or separately-keyed) child column: one entry per group field, fetched in
     * batch by FcDescriptiveDataChildLoader rather than joined.
     *
     * @return array<string,mixed>|null  null when the course does not have this section
     */
    private function resolveChild(string $key, array $def, array $declared): ?array
    {
        $table = $def['child']['table'];
        $column = $def['child']['column'];

        if (! fc_schema_has_table($table) || ! fc_schema_has_column($table, $column)) {
            return null;
        }

        // Same gate as every other column: the form must actually map it.
        $meta = $declared[$table.'.'.$column] ?? null;
        if ($meta === null) {
            return null;
        }

        $entry = [
            'key' => $key,
            'label' => $def['label'],
            'group' => $def['group'],
            'type' => 'child',
            'table' => $table,
            'alias' => null,
            'columns' => [$column],
            'filter' => null,
            'child' => [
                'table' => $table,
                'column' => $column,
                'user_column' => fc_user_col($table),
                'format' => $def['child_format'] ?? null,
            ],
        ];

        // Ids render as names, exactly as the PDF prints them.
        if ($meta->lookup_table && $meta->lookup_value_column && $meta->lookup_label_column
            && fc_schema_has_table($meta->lookup_table)
            && fc_schema_has_column($meta->lookup_table, $meta->lookup_value_column)
            && fc_schema_has_column($meta->lookup_table, $meta->lookup_label_column)
            && self::columnIsUniquelyIndexed($meta->lookup_table, $meta->lookup_value_column)) {
            $entry['child']['lookup'] = [
                'table' => $meta->lookup_table,
                'value' => $meta->lookup_value_column,
                'label' => $meta->lookup_label_column,
            ];
        }

        return $entry;
    }
}
