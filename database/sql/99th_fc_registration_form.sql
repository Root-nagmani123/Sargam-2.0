-- =============================================================================
-- 99th FC Registration — Dynamic Form (ready-to-run SQL)
-- Maps all fields from "FC Registration Forms.zip" (Pages 4–11)
-- Steps follow the same DynamicFormService logic as fc-registration (form_id=1)
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 0 · Add new columns to existing tables
--   MySQL 8.0 does not support ADD COLUMN IF NOT EXISTS (MariaDB syntax).
--   We use a helper stored procedure that catches error 1060 (duplicate column)
--   so the script is safe to re-run.
-- ─────────────────────────────────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS _ac;
DELIMITER //
CREATE PROCEDURE _ac(tbl VARCHAR(100), col VARCHAR(100), def TEXT)
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;  -- 1060 = Duplicate column name
    SET @_ddl = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE _s FROM @_ddl;
    EXECUTE _s;
    DEALLOCATE PREPARE _s;
END //
DELIMITER ;

-- student_masters — tracker flags for the three new steps
CALL _ac('student_masters', 'health_done',  'TINYINT(1) NOT NULL DEFAULT 0');
CALL _ac('student_masters', 'special_done', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL _ac('student_masters', 'vision_done',  'TINYINT(1) NOT NULL DEFAULT 0');

-- student_master_firsts — step 1 personal extras + vision + joining docs
CALL _ac('student_master_firsts', 'full_name_hindi',              'VARCHAR(200) NULL');
CALL _ac('student_master_firsts', 'first_name',                   'VARCHAR(100) NULL');
CALL _ac('student_master_firsts', 'middle_name',                  'VARCHAR(100) NULL');
CALL _ac('student_master_firsts', 'last_name',                    'VARCHAR(100) NULL');
CALL _ac('student_master_firsts', 'pan_card',                     'VARCHAR(20)  NULL');
CALL _ac('student_master_firsts', 'aadhar_number',                'VARCHAR(20)  NULL');
CALL _ac('student_master_firsts', 'passport_no',                  'VARCHAR(20)  NULL');
CALL _ac('student_master_firsts', 'alt_mobile_no',                'VARCHAR(20)  NULL');
CALL _ac('student_master_firsts', 'alt_email',                    'VARCHAR(150) NULL');
CALL _ac('student_master_firsts', 'instagram_id',                 'VARCHAR(100) NULL');
CALL _ac('student_master_firsts', 'twitter_id',                   'VARCHAR(100) NULL');
-- Vision Statement (step 8)
CALL _ac('student_master_firsts', 'vision_statement',             'TEXT NULL');
CALL _ac('student_master_firsts', 'vision_completed',             'TINYINT(1) NOT NULL DEFAULT 0');
-- Joining Documents (step 5)
CALL _ac('student_master_firsts', 'doc_family_details_path',      'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_close_relation_path',      'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_dowry_decl_path',          'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_marital_status_path',      'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_home_town_path',           'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_immovable_prop_path',      'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_movable_prop_path',        'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_debts_liabilities_path',   'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_surety_bond_ias_path',     'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_surety_bond_others_path',  'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_oath_affirmation_path',    'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_assumption_charge_path',   'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_group_insurance_path',     'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_nps_subscription_path',    'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'doc_employee_info_sheet_path', 'VARCHAR(500) NULL');
CALL _ac('student_master_firsts', 'docs_completed',               'TINYINT(1) NOT NULL DEFAULT 0');

-- student_master_seconds — step 2 extras + health risk (step 6)
CALL _ac('student_master_seconds', 'birth_state_id',          'BIGINT UNSIGNED NULL');
CALL _ac('student_master_seconds', 'birth_district',          'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'birth_city',              'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'mother_first_name',       'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'mother_middle_name',      'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'mother_last_name',        'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'mother_qualification_id', 'BIGINT UNSIGNED NULL');
CALL _ac('student_master_seconds', 'mother_profession_id',    'BIGINT UNSIGNED NULL');
CALL _ac('student_master_seconds', 'mother_annual_income',    'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'father_first_name',       'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'father_middle_name',      'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'father_last_name',        'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'father_qualification_id', 'BIGINT UNSIGNED NULL');
CALL _ac('student_master_seconds', 'father_annual_income',    'VARCHAR(100)    NULL');
CALL _ac('student_master_seconds', 'dietary_preference',      'VARCHAR(50)     NULL');
CALL _ac('student_master_seconds', 'high_altitude_condition', 'VARCHAR(10)     NULL');
CALL _ac('student_master_seconds', 'high_altitude_remarks',   'TEXT            NULL');
CALL _ac('student_master_seconds', 'domicile_district',       'VARCHAR(100)    NULL');
-- Health Risk Factors (step 6)
CALL _ac('student_master_seconds', 'health_asthma',             'VARCHAR(10)  NULL');
CALL _ac('student_master_seconds', 'health_lung_disease',       'VARCHAR(50)  NULL');
CALL _ac('student_master_seconds', 'health_kidney_disease',     'VARCHAR(10)  NULL');
CALL _ac('student_master_seconds', 'health_diabetes',           'VARCHAR(10)  NULL');
CALL _ac('student_master_seconds', 'health_blood_disorder',     'VARCHAR(50)  NULL');
CALL _ac('student_master_seconds', 'health_immunocompromised',  'VARCHAR(100) NULL');
CALL _ac('student_master_seconds', 'health_liver_disease',      'VARCHAR(10)  NULL');
CALL _ac('student_master_seconds', 'health_cardiac_condition',  'VARCHAR(100) NULL');
CALL _ac('student_master_seconds', 'health_pregnant_lactating', 'VARCHAR(10)  NULL');
CALL _ac('student_master_seconds', 'health_additional_info',    'TEXT         NULL');
CALL _ac('student_master_seconds', 'health_completed',          'TINYINT(1) NOT NULL DEFAULT 0');

-- student_iosr_reasonable_adjust_masters — special assistance (step 7)
CALL _ac('student_iosr_reasonable_adjust_masters', 'physical_impairment_info', 'TEXT      NULL');
CALL _ac('student_iosr_reasonable_adjust_masters', 'special_completed',        'TINYINT(1) NOT NULL DEFAULT 0');

-- student_knowledge_hindi_masters — language knowledge group (step 3)
CALL _ac('student_knowledge_hindi_masters', 'mother_tongue',       'VARCHAR(100) NULL');
CALL _ac('student_knowledge_hindi_masters', 'medium_12th',         'VARCHAR(100) NULL');
CALL _ac('student_knowledge_hindi_masters', 'medium_graduation',   'VARCHAR(100) NULL');
CALL _ac('student_knowledge_hindi_masters', 'medium_civil_service','VARCHAR(100) NULL');
CALL _ac('student_knowledge_hindi_masters', 'viva_language',       'VARCHAR(100) NULL');
CALL _ac('student_knowledge_hindi_masters', 'passed_matric_hindi', 'VARCHAR(10)  NULL');
CALL _ac('student_knowledge_hindi_masters', 'selected_cse_hindi',  'VARCHAR(10)  NULL');
CALL _ac('student_knowledge_hindi_masters', 'hindi_mother_tongue', 'VARCHAR(10)  NULL');

DROP PROCEDURE IF EXISTS _ac;


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 1 · Create the Form
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_forms
    (form_name, form_slug, description, icon, consolidation_table, user_identifier, is_active, created_at, updated_at)
VALUES
    ('99th FC Registration',
     'fc-registration-99th',
     'Foundation Course Officer Trainee Registration – 99th Batch (Pages 4–11)',
     'bi-person-badge',
     'student_masters',
     'username',
     1, NOW(), NOW());

SET @form_id = LAST_INSERT_ID();


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 2 · Create Steps
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps
    (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES
    (@form_id, 'Personal Information',        '99th-step1',     1,
     'student_master_firsts',                      'step1_completed', 'step1_done',    1,
     'Basic personal, contact and service details', 'bi-person-fill', NOW(), NOW()),

    (@form_id, 'Family, Address & Physical',  '99th-step2',     2,
     'student_master_seconds',                     'step2_completed', 'step2_done',    1,
     'Parents, permanent/mailing address and physical details', 'bi-house-fill', NOW(), NOW()),

    (@form_id, 'Other Details',               '99th-step3',     3,
     'student_masters',                            NULL,              'step3_done',    1,
     'Languages, qualifications, employment, sports, hobbies and spouse', 'bi-list-ul', NOW(), NOW()),

    (@form_id, 'Bank Details',                '99th-bank',      4,
     'new_registration_bank_details_masters',      NULL,              'bank_done',     1,
     'Bank account details for salary processing', 'bi-bank', NOW(), NOW()),

    (@form_id, 'Joining Documents',           '99th-documents', 5,
     'student_master_firsts',                      'docs_completed',  'docs_done',     1,
     'Upload mandatory joining documents (Forms 3, 6-A/B/C, Oath, NPS etc.)', 'bi-folder-fill', NOW(), NOW()),

    (@form_id, 'Health Risk Factors',         '99th-health',    6,
     'student_master_seconds',                     'health_completed','health_done',   1,
     'Declaration of pre-existing medical conditions', 'bi-heart-pulse-fill', NOW(), NOW()),

    (@form_id, 'Special Assistance',          '99th-special',   7,
     'student_iosr_reasonable_adjust_masters',     'special_completed','special_done', 1,
     'Physical impairment and reasonable adjustment requests', 'bi-universal-access', NOW(), NOW()),

    (@form_id, 'Vision Statement',            '99th-vision',    8,
     'student_master_firsts',                      'vision_completed','vision_done',   1,
     'Your vision and aspirations in civil service (~100 words)', 'bi-lightbulb-fill', NOW(), NOW());

-- Capture step IDs into variables
SELECT id INTO @s1 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-step1';
SELECT id INTO @s2 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-step2';
SELECT id INTO @s3 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-step3';
SELECT id INTO @s4 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-bank';
SELECT id INTO @s5 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-documents';
SELECT id INTO @s6 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-health';
SELECT id INTO @s7 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-special';
SELECT id INTO @s8 FROM fc_form_steps WHERE form_id = @form_id AND step_slug = '99th-vision';


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 3 · STEP 1 — Personal Information  (flat fields → student_master_firsts)
-- Page 4: personal info, contact, service details, uploads
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

-- ── Personal Info ──────────────────────────────────────────────────────────
(@s1, 'session_id',      'Session',                     'select',
 'student_master_firsts', 'session_id',
 'required|exists:session_masters,id', 1, 1,
 NULL, NULL, NULL,
 NULL, 'session_masters','id','session_name','session_name',
 'Personal Information','col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'full_name',       'Full Name (English)',          'text',
 'student_master_firsts', 'full_name',
 'required|string|max:200', 1, 2,
 'As per service records', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'full_name_hindi', 'Full Name (Hindi / पूरा नाम)', 'text',
 'student_master_firsts', 'full_name_hindi',
 'nullable|string|max:200', 0, 3,
 'पूरा नाम', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'first_name',      'First Name',                  'text',
 'student_master_firsts', 'first_name',
 'required|string|max:100', 1, 4,
 'First name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'middle_name',     'Middle Name',                 'text',
 'student_master_firsts', 'middle_name',
 'nullable|string|max:100', 0, 5,
 'Middle name (if any)', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'last_name',       'Last Name / Surname',          'text',
 'student_master_firsts', 'last_name',
 'nullable|string|max:100', 0, 6,
 'Last name / Surname', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'gender',          'Gender',                      'select',
 'student_master_firsts', 'gender',
 'required|in:Male,Female,Transgender', 1, 7,
 NULL, NULL, NULL,
 '[{"value":"Male","label":"Male"},{"value":"Female","label":"Female"},{"value":"Transgender","label":"Transgender"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'date_of_birth',   'Date of Birth',               'date',
 'student_master_firsts', 'date_of_birth',
 'required|date|before:today', 1, 8,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'service_id',      'Service',                     'select',
 'student_master_firsts', 'service_id',
 'required|exists:service_master,pk', 1, 9,
 NULL, NULL, NULL,
 NULL, 'service_master','pk','service_name','service_name',
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'cadre',           'Cadre',                       'text',
 'student_master_firsts', 'cadre',
 'required|string|max:100', 1, 10,
 'e.g. IAS, IPS, IRTS', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'allotted_state_id','Allotted State',              'select',
 'student_master_firsts', 'allotted_state_id',
 'required|exists:state_master,pk', 1, 11,
 NULL, NULL, NULL,
 NULL, 'state_master','pk','state_name','state_name',
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'pan_card',        'PAN Card Number',             'text',
 'student_master_firsts', 'pan_card',
 'nullable|string|max:20|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', 0, 12,
 'e.g. ABCDE1234F', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'aadhar_number',   'Aadhar Number',               'text',
 'student_master_firsts', 'aadhar_number',
 'required|digits:12', 1, 13,
 '12-digit Aadhar number', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'passport_no',     'Passport Number',             'text',
 'student_master_firsts', 'passport_no',
 'nullable|string|max:20', 0, 14,
 'Passport number (if available)', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

-- ── Contact Details ────────────────────────────────────────────────────────
(@s1, 'mobile_no',       'Mobile Number',               'text',
 'student_master_firsts', 'mobile_no',
 'required|digits:10', 1, 15,
 '10-digit mobile number', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Contact Details','col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'alt_mobile_no',   'Alternate Mobile Number',     'text',
 'student_master_firsts', 'alt_mobile_no',
 'nullable|digits:10', 0, 16,
 'Alternate 10-digit mobile', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'email',           'Email Address',               'email',
 'student_master_firsts', 'email',
 'required|email|max:150', 1, 17,
 'Official email address', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'alt_email',       'Alternate Email',             'email',
 'student_master_firsts', 'alt_email',
 'nullable|email|max:150', 0, 18,
 'Alternate email address', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'instagram_id',    'Instagram ID',                'text',
 'student_master_firsts', 'instagram_id',
 'nullable|string|max:100', 0, 19,
 '@username', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s1, 'twitter_id',      'Twitter / X ID',             'text',
 'student_master_firsts', 'twitter_id',
 'nullable|string|max:100', 0, 20,
 '@handle', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

-- ── Uploads ───────────────────────────────────────────────────────────────
(@s1, 'photo',           'Photograph',                  'file',
 'student_master_firsts', 'photo_path',
 'nullable|image|mimes:jpeg,jpg,png|max:500', 0, 21,
 NULL, 'JPG/PNG, max 500 KB, recent passport-size', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Uploads','col-md-6', 500, 'jpeg,jpg,png', 1, NOW(), NOW()),

(@s1, 'signature',       'Signature',                   'file',
 'student_master_firsts', 'signature_path',
 'nullable|image|mimes:jpeg,jpg,png|max:200', 0, 22,
 NULL, 'JPG/PNG, max 200 KB, signature on white paper', NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', 200, 'jpeg,jpg,png', 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 4 · STEP 2 — Family, Address & Physical  (flat → student_master_seconds)
-- Page 4: background, parents, physical details, permanent/mailing address
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

-- ── Personal Background ────────────────────────────────────────────────────
(@s2, 'nationality',     'Nationality',                 'text',
 'student_master_seconds', 'nationality',
 'required|string|max:100', 1, 1,
 'e.g. Indian', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Personal Background','col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'category_id',     'Category',                   'select',
 'student_master_seconds', 'category_id',
 'required|exists:caste_category_master,pk', 1, 2,
 NULL, NULL, NULL,
 NULL, 'caste_category_master','pk','Seat_name',NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'religion_id',     'Religion',                   'select',
 'student_master_seconds', 'religion_id',
 'required|exists:religion_master,pk', 1, 3,
 NULL, NULL, NULL,
 NULL, 'religion_master','pk','religion_name',NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'marital_status',  'Marital Status',             'select',
 'student_master_seconds', 'marital_status',
 'required|in:Single,Married,Divorced,Widowed', 1, 4,
 NULL, NULL, NULL,
 '[{"value":"Single","label":"Single"},{"value":"Married","label":"Married"},{"value":"Divorced","label":"Divorced"},{"value":"Widowed","label":"Widowed"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

-- ── Birth Place ────────────────────────────────────────────────────────────
(@s2, 'birth_state_id',  'Birth State',                'select',
 'student_master_seconds', 'birth_state_id',
 'required|exists:state_master,pk', 1, 5,
 NULL, NULL, NULL,
 NULL, 'state_master','pk','state_name','state_name',
 'Birth Place Details','col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'birth_district',  'Birth District',             'text',
 'student_master_seconds', 'birth_district',
 'required|string|max:100', 1, 6,
 'District name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'birth_city',      'Birth Village / City',       'text',
 'student_master_seconds', 'birth_city',
 'required|string|max:100', 1, 7,
 'Village or city name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

-- ── Mother's Details ───────────────────────────────────────────────────────
(@s2, 'mother_first_name',  "Mother's First Name",     'text',
 'student_master_seconds', 'mother_first_name',
 'required|string|max:100', 1, 8,
 'First name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 "Mother's Details",'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'mother_middle_name', "Mother's Middle Name",    'text',
 'student_master_seconds', 'mother_middle_name',
 'nullable|string|max:100', 0, 9,
 'Middle name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'mother_last_name',   "Mother's Last Name",      'text',
 'student_master_seconds', 'mother_last_name',
 'nullable|string|max:100', 0, 10,
 'Last name / Surname', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'mother_qualification_id', "Mother's Qualification", 'select',
 'student_master_seconds', 'mother_qualification_id',
 'required|exists:parent_qualification_master,pk', 1, 11,
 NULL, NULL, NULL,
 NULL, 'parent_qualification_master','pk','qualification_name',NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'mother_profession_id',    "Mother's Profession",    'select',
 'student_master_seconds', 'mother_profession_id',
 'required|exists:parents_profession_master,pk', 1, 12,
 NULL, NULL, NULL,
 NULL, 'parents_profession_master','pk','profession_name',NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'mother_annual_income',    "Mother's Annual Income (₹)", 'number',
 'student_master_seconds', 'mother_annual_income',
 'required|numeric|min:0', 1, 13,
 'Annual income in rupees', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

-- ── Father's Details ───────────────────────────────────────────────────────
(@s2, 'father_first_name',  "Father's First Name",     'text',
 'student_master_seconds', 'father_first_name',
 'required|string|max:100', 1, 14,
 'First name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 "Father's Details",'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'father_middle_name', "Father's Middle Name",    'text',
 'student_master_seconds', 'father_middle_name',
 'nullable|string|max:100', 0, 15,
 'Middle name', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'father_last_name',   "Father's Last Name",      'text',
 'student_master_seconds', 'father_last_name',
 'nullable|string|max:100', 0, 16,
 'Last name / Surname', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'father_qualification_id', "Father's Qualification", 'select',
 'student_master_seconds', 'father_qualification_id',
 'required|exists:parent_qualification_master,pk', 1, 17,
 NULL, NULL, NULL,
 NULL, 'parent_qualification_master','pk','qualification_name',NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'father_profession_id',    "Father's Profession",    'select',
 'student_master_seconds', 'father_profession_id',
 'nullable|exists:father_professions,id', 0, 18,
 NULL, NULL, NULL,
 NULL, 'father_professions','id','profession_name',NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'father_annual_income',    "Father's Annual Income (₹)", 'number',
 'student_master_seconds', 'father_annual_income',
 'required|numeric|min:0', 1, 19,
 'Annual income in rupees', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

-- ── Physical Details ───────────────────────────────────────────────────────
(@s2, 'height_cm',       'Height (cm)',                 'number',
 'student_master_seconds', 'height_cm',
 'required|numeric|min:50|max:300', 1, 20,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Physical Details','col-md-3', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'weight_kg',       'Weight (kg)',                 'number',
 'student_master_seconds', 'weight_kg',
 'required|numeric|min:20|max:300', 1, 21,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-3', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'blood_group',     'Blood Group',                 'select',
 'student_master_seconds', 'blood_group',
 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-', 1, 22,
 NULL, NULL, NULL,
 '[{"value":"A+","label":"A+"},{"value":"A-","label":"A-"},{"value":"B+","label":"B+"},{"value":"B-","label":"B-"},{"value":"AB+","label":"AB+"},{"value":"AB-","label":"AB-"},{"value":"O+","label":"O+"},{"value":"O-","label":"O-"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-3', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'dietary_preference', 'Dietary Preference',       'select',
 'student_master_seconds', 'dietary_preference',
 'required|in:Vegetarian,Non-Vegetarian,Vegan,Jain', 1, 23,
 NULL, NULL, NULL,
 '[{"value":"Vegetarian","label":"Vegetarian"},{"value":"Non-Vegetarian","label":"Non-Vegetarian"},{"value":"Vegan","label":"Vegan"},{"value":"Jain","label":"Jain"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-3', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'high_altitude_condition', 'High-altitude medical condition?', 'radio',
 'student_master_seconds', 'high_altitude_condition',
 'required|in:Yes,No', 1, 24,
 NULL, NULL, NULL,
 '[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'high_altitude_remarks', 'High-altitude Remarks (if Yes)', 'textarea',
 'student_master_seconds', 'high_altitude_remarks',
 'nullable|string|max:500', 0, 25,
 'Provide details of the condition', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-12', NULL, NULL, 1, NOW(), NOW()),

-- ── Permanent Address ──────────────────────────────────────────────────────
(@s2, 'perm_address_line1', 'Permanent Address',        'textarea',
 'student_master_seconds', 'perm_address_line1',
 'required|string|max:300', 1, 26,
 'House no., street, locality', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Permanent Address','col-md-12', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'perm_country_id', 'Country',                    'select',
 'student_master_seconds', 'perm_country_id',
 'required|exists:country_master,pk', 1, 27,
 NULL, NULL, NULL,
 NULL, 'country_master','pk','country_name','country_name',
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'perm_state_id',   'State',                      'select',
 'student_master_seconds', 'perm_state_id',
 'required|exists:state_master,pk', 1, 28,
 NULL, NULL, NULL,
 NULL, 'state_master','pk','state_name','state_name',
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'perm_city',       'City / District',            'text',
 'student_master_seconds', 'perm_city',
 'required|string|max:100', 1, 29,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'perm_pincode',    'Pincode',                    'text',
 'student_master_seconds', 'perm_pincode',
 'required|digits:6', 1, 30,
 '6-digit postal code', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'domicile_state',  'Domicile State',             'text',
 'student_master_seconds', 'domicile_state',
 'nullable|string|max:100', 0, 31,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'domicile_district', 'Domicile District',        'text',
 'student_master_seconds', 'domicile_district',
 'nullable|string|max:100', 0, 32,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

-- ── Mailing Address ────────────────────────────────────────────────────────
(@s2, 'same_as_permanent', 'Same as Permanent Address', 'checkbox',
 'student_master_seconds', '_skip',
 'nullable|boolean', 0, 33,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Mailing Address','col-md-12', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'pres_address_line1', 'Mailing Address',          'textarea',
 'student_master_seconds', 'pres_address_line1',
 'required_without:same_as_permanent|nullable|string|max:300', 0, 34,
 'House no., street, locality', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-12', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'pres_country_id', 'Country',                    'select',
 'student_master_seconds', 'pres_country_id',
 'required_without:same_as_permanent|nullable|exists:country_master,pk', 0, 35,
 NULL, NULL, NULL,
 NULL, 'country_master','pk','country_name','country_name',
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'pres_state_id',   'State',                      'select',
 'student_master_seconds', 'pres_state_id',
 'required_without:same_as_permanent|nullable|exists:state_master,pk', 0, 36,
 NULL, NULL, NULL,
 NULL, 'state_master','pk','state_name','state_name',
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'pres_city',       'City / District',            'text',
 'student_master_seconds', 'pres_city',
 'required_without:same_as_permanent|nullable|string|max:100', 0, 37,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW()),

(@s2, 'pres_pincode',    'Pincode',                    'text',
 'student_master_seconds', 'pres_pincode',
 'required_without:same_as_permanent|nullable|digits:6', 0, 38,
 '6-digit postal code', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-4', NULL, NULL, 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 5 · STEP 4 — Bank Details  (flat → new_registration_bank_details_masters)
-- Page 8
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

(@s4, 'account_holder_name', 'Account Holder Name',    'text',
 'new_registration_bank_details_masters', 'account_holder_name',
 'required|string|max:200', 1, 1,
 'Name as on bank account', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Bank Details','col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'account_no',      'Account Number',             'text',
 'new_registration_bank_details_masters', 'account_no',
 'required|string|max:50', 1, 2,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'account_no_confirm', 'Confirm Account Number',  'text',
 'new_registration_bank_details_masters', '_skip',
 'required|same:account_no', 1, 3,
 NULL, 'Must match account number above', NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'ifsc_code',       'Bank IFSC Code',             'text',
 'new_registration_bank_details_masters', 'ifsc_code',
 'required|string|max:20|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/', 1, 4,
 'e.g. SBIN0001234', NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'bank_name',       'Bank Name',                  'text',
 'new_registration_bank_details_masters', 'bank_name',
 'required|string|max:200', 1, 5,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'branch_name',     'Branch Name',                'text',
 'new_registration_bank_details_masters', 'branch_name',
 'required|string|max:200', 1, 6,
 NULL, NULL, NULL,
 NULL, NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'account_type',    'Account Type',               'select',
 'new_registration_bank_details_masters', 'account_type',
 'required|in:Savings,Current', 1, 7,
 NULL, NULL, NULL,
 '[{"value":"Savings","label":"Savings"},{"value":"Current","label":"Current"}]',
 NULL, NULL, NULL, NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s4, 'aadhar_card_copy', 'Aadhar Card (Self Attested)', 'file',
 'new_registration_bank_details_masters', 'bank_passbook_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048', 0, 8,
 NULL, 'PDF/JPG/PNG, max 2 MB', NULL,
 NULL, NULL, NULL, NULL, NULL,
 'Supporting Documents','col-md-6', 2048, 'jpeg,jpg,png,pdf', 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 6 · STEP 5 — Joining Documents  (flat file → student_master_firsts)
-- Page 7: 15 mandatory documents
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

(@s5, 'doc_family_details',     'Family Details Form (Form-3)',                        'file',
 'student_master_firsts', 'doc_family_details_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 1,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 'Joining Documents','col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_close_relation',     'Declaration of Close Relation',                       'file',
 'student_master_firsts', 'doc_close_relation_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 2,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_dowry_decl',         'Dowry Declaration',                                   'file',
 'student_master_firsts', 'doc_dowry_decl_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 3,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_marital_status',     'Marital Status Declaration',                          'file',
 'student_master_firsts', 'doc_marital_status_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 4,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_home_town',          'Home Town Declaration',                               'file',
 'student_master_firsts', 'doc_home_town_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 5,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_immovable_prop',     'Statement of Immovable Property (Form 6-A)',          'file',
 'student_master_firsts', 'doc_immovable_prop_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 6,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_movable_prop',       'Statement of Movable Property (Form 6-B)',            'file',
 'student_master_firsts', 'doc_movable_prop_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 7,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_debts_liabilities',  'Statement of Debts & Liabilities (Form 6-C)',         'file',
 'student_master_firsts', 'doc_debts_liabilities_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 8,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_surety_bond_ias',    'Surety Bond (IAS / IPS / IFoS)',                     'file',
 'student_master_firsts', 'doc_surety_bond_ias_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 9,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_surety_bond_others', 'Surety Bond (Other Services)',                        'file',
 'student_master_firsts', 'doc_surety_bond_others_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 10,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_oath_affirmation',   'Form of Oath / Affirmation',                         'file',
 'student_master_firsts', 'doc_oath_affirmation_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 11,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_assumption_charge',  'Certificate of Assumption of Charge',                'file',
 'student_master_firsts', 'doc_assumption_charge_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 12,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_group_insurance',    'Nomination – Group Insurance (Form 7 / Form 8)',      'file',
 'student_master_firsts', 'doc_group_insurance_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 13,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_nps_subscription',   'NPS Subscription Registration Form',                 'file',
 'student_master_firsts', 'doc_nps_subscription_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 14,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW()),

(@s5, 'doc_employee_info_sheet','Employee Information Sheet Form',                     'file',
 'student_master_firsts', 'doc_employee_info_sheet_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 15,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL, NULL, NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 7 · STEP 6 — Health Risk Factors  (flat → student_master_seconds)
-- Page 9: radio declarations for pre-existing conditions
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

(@s6, 'health_asthma',          'Asthma',                          'radio',
 'student_master_seconds', 'health_asthma',
 'required|in:No,Yes', 1, 1, NULL, NULL, NULL,
 '[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]',
 NULL,NULL,NULL,NULL,
 'Health Risk Factors','col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_lung_disease',    'Chronic Lung Disease',            'radio',
 'student_master_seconds', 'health_lung_disease',
 'required|in:None,COPD,IPF,Cystic Fibrosis', 1, 2, NULL, NULL, NULL,
 '[{"value":"None","label":"None"},{"value":"COPD","label":"COPD"},{"value":"IPF","label":"IPF"},{"value":"Cystic Fibrosis","label":"Cystic Fibrosis"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_kidney_disease',  'Kidney Disease (requiring dialysis)', 'radio',
 'student_master_seconds', 'health_kidney_disease',
 'required|in:No,Yes', 1, 3, NULL, NULL, NULL,
 '[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_diabetes',        'Diabetes (Type I / II / Gestational)', 'radio',
 'student_master_seconds', 'health_diabetes',
 'required|in:No,Yes', 1, 4, NULL, NULL, NULL,
 '[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_blood_disorder',  'Blood Disorders',                 'radio',
 'student_master_seconds', 'health_blood_disorder',
 'required|in:None,Sickle Cell,Thalassemia,Anaemia', 1, 5, NULL, NULL, NULL,
 '[{"value":"None","label":"None"},{"value":"Sickle Cell","label":"Sickle Cell"},{"value":"Thalassemia","label":"Thalassemia"},{"value":"Anaemia","label":"Anaemia"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_immunocompromised', 'Immunocompromised Status',       'radio',
 'student_master_seconds', 'health_immunocompromised',
 'required|in:None,Cancer+Chemotherapy,Bone Marrow/Organ Transplant,HIV low CD4,Prolonged Corticosteroids', 1, 6,
 NULL, NULL, NULL,
 '[{"value":"None","label":"None"},{"value":"Cancer+Chemotherapy","label":"Cancer + Chemotherapy"},{"value":"Bone Marrow/Organ Transplant","label":"Bone Marrow / Organ Transplant"},{"value":"HIV low CD4","label":"HIV (low CD4 count)"},{"value":"Prolonged Corticosteroids","label":"Prolonged Corticosteroids"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_liver_disease',   'Liver Disease',                   'radio',
 'student_master_seconds', 'health_liver_disease',
 'required|in:No,Yes', 1, 7, NULL, NULL, NULL,
 '[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_cardiac_condition', 'Serious Cardiac Conditions',    'radio',
 'student_master_seconds', 'health_cardiac_condition',
 'required|in:None,Coronary Artery Disease,Coronary Heart Disease,Cardiomyopathies,Pulmonary Hypertension', 1, 8,
 NULL, NULL, NULL,
 '[{"value":"None","label":"None"},{"value":"Coronary Artery Disease","label":"Coronary Artery Disease"},{"value":"Coronary Heart Disease","label":"Coronary Heart Disease"},{"value":"Cardiomyopathies","label":"Cardiomyopathies"},{"value":"Pulmonary Hypertension","label":"Pulmonary Hypertension"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_pregnant_lactating', 'Pregnant or Lactating Mother', 'radio',
 'student_master_seconds', 'health_pregnant_lactating',
 'required|in:No,Yes', 1, 9, NULL, NULL, NULL,
 '[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]',
 NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s6, 'health_additional_info', 'Any Other Additional Information','textarea',
 'student_master_seconds', 'health_additional_info',
 'nullable|string|max:1000', 0, 10,
 'Provide any other relevant health information', NULL, NULL,
 NULL,NULL,NULL,NULL,NULL,
 NULL,'col-md-12', NULL, NULL, 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 8 · STEP 7 — Special Assistance  (flat → student_iosr_reasonable_adjust_masters)
-- Page 10
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

(@s7, 'physical_impairment_info', 'Physical Impairment Information', 'textarea',
 'student_iosr_reasonable_adjust_masters', 'physical_impairment_info',
 'nullable|string|max:1000', 0, 1,
 'Describe any physical impairment or disability', NULL, NULL,
 NULL,NULL,NULL,NULL,NULL,
 'Special Assistance','col-md-12', NULL, NULL, 1, NOW(), NOW()),

(@s7, 'adjustment_required',    'Reasonable Adjustments Requested', 'textarea',
 'student_iosr_reasonable_adjust_masters', 'adjustment_required',
 'nullable|string|max:1000', 0, 2,
 'Describe the adjustments required', NULL, NULL,
 NULL,NULL,NULL,NULL,NULL,
 NULL,'col-md-12', NULL, NULL, 1, NOW(), NOW()),

(@s7, 'adjustment_type',        'Document Title',                   'text',
 'student_iosr_reasonable_adjust_masters', 'adjustment_type',
 'nullable|string|max:200', 0, 3,
 'Title of the supporting document', NULL, NULL,
 NULL,NULL,NULL,NULL,NULL,
 NULL,'col-md-6', NULL, NULL, 1, NOW(), NOW()),

(@s7, 'doc_path',               'Supporting Document Upload',       'file',
 'student_iosr_reasonable_adjust_masters', 'doc_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 4,
 NULL, 'PDF/JPG/PNG, max 5 MB', NULL,
 NULL,NULL,NULL,NULL,NULL,
 NULL,'col-md-6', 5120, 'jpeg,jpg,png,pdf', 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 9 · STEP 8 — Vision Statement  (flat → student_master_firsts)
-- Page 11
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_fields
    (step_id, field_name, label, field_type,
     target_table, target_column,
     validation_rules, is_required, display_order,
     placeholder, help_text, default_value,
     options_json, lookup_table, lookup_value_column, lookup_label_column, lookup_order_column,
     section_heading, css_class, file_max_kb, file_extensions,
     is_active, created_at, updated_at)
VALUES

(@s8, 'vision_statement', 'Statement of Vision and Aspirations',    'textarea',
 'student_master_firsts', 'vision_statement',
 'required|string|min:50|max:1500', 1, 1,
 'Write your vision and aspirations in civil service (approx. 100 words)',
 'Describe your vision, goals and aspirations as a civil servant (~100 words).', NULL,
 NULL,NULL,NULL,NULL,NULL,
 'Vision Statement','col-md-12', NULL, NULL, 1, NOW(), NOW());


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 10 · STEP 3 — Other Details  (groups)
-- Page 5: languages, qualifications, employment, distinctions, hobbies, sports, spouse, dress code
-- Plus: language knowledge (mediums, hindi), pre-medical history
-- ─────────────────────────────────────────────────────────────────────────────

-- ── GROUP 1: Language Knowledge (medium of study, hindi)  ─────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'language_knowledge', 'Language Knowledge', 'student_knowledge_hindi_masters', 'upsert', 0, 1, 1, 1, NOW(), NOW());

SET @g1 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g1, 'mother_tongue',          'Mother Tongue',                      'text',   'mother_tongue',
 'nullable|string|max:100', 0, 1, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g1, 'medium_12th',            'Medium of Study in Class 12',        'text',   'medium_12th',
 'nullable|string|max:100', 0, 2, 'e.g. Hindi, English', NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g1, 'medium_graduation',      'Medium of Study in Graduation',      'text',   'medium_graduation',
 'nullable|string|max:100', 0, 3, 'e.g. Hindi, English', NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g1, 'medium_civil_service',   'Medium in Civil Service Exam',       'text',   'medium_civil_service',
 'nullable|string|max:100', 0, 4, 'e.g. Hindi, English', NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g1, 'viva_language',          'Language of CSE Viva / Interview',   'text',   'viva_language',
 'nullable|string|max:100', 0, 5, 'e.g. Hindi, English', NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g1, 'passed_matric_hindi',    'Passed Matriculation with Hindi?',   'select', 'passed_matric_hindi',
 'nullable|in:Yes,No', 0, 6, NULL,
 '[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]',
 NULL,NULL,NULL, 'col-md-4', 1, NOW(), NOW()),

(@g1, 'selected_cse_hindi',     'Selected in CSE 2023 with Hindi?',   'select', 'selected_cse_hindi',
 'nullable|in:Yes,No', 0, 7, NULL,
 '[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]',
 NULL,NULL,NULL, 'col-md-4', 1, NOW(), NOW()),

(@g1, 'hindi_mother_tongue',    'Is Hindi your Mother Tongue?',       'select', 'hindi_mother_tongue',
 'nullable|in:Yes,No', 0, 8, NULL,
 '[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]',
 NULL,NULL,NULL, 'col-md-4', 1, NOW(), NOW());


-- ── GROUP 2: Languages Known  ──────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'languages', 'Languages Known', 'student_master_language_knowns', 'replace_all', 0, 20, 2, 1, NOW(), NOW());

SET @g2 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g2, 'language_id',  'Language',   'select',   'language_id',
 'required|exists:language_master,pk', 1, 1, NULL, NULL,
 'language_master','pk','language_name', 'col-md-3', 1, NOW(), NOW()),

(@g2, 'can_read',     'Can Read',   'checkbox', 'can_read',
 'nullable', 0, 2, NULL, NULL, NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g2, 'can_write',    'Can Write',  'checkbox', 'can_write',
 'nullable', 0, 3, NULL, NULL, NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g2, 'can_speak',    'Can Speak',  'checkbox', 'can_speak',
 'nullable', 0, 4, NULL, NULL, NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g2, 'proficiency',  'Proficiency','select',   'proficiency',
 'nullable|in:Basic,Intermediate,Advanced,Native', 0, 5, NULL,
 '[{"value":"Basic","label":"Basic"},{"value":"Intermediate","label":"Intermediate"},{"value":"Advanced","label":"Advanced"},{"value":"Native","label":"Native"}]',
 NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW());


-- ── GROUP 3: Qualifications (10th / 12th / Graduation)  ───────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'qualifications', 'Academic Qualifications (10th onwards)', 'student_master_qualification_details', 'replace_all', 1, 10, 3, 1, NOW(), NOW());

SET @g3 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g3, 'qualification_id',  'Degree / Level',         'select', 'qualification_id',
 'required|exists:qualification_master,pk', 1, 1, NULL, NULL,
 'qualification_master','pk','qualification', 'col-md-3', 1, NOW(), NOW()),

(@g3, 'board_id',          'Board / University',     'select', 'board_id',
 'required|exists:university_board_name_master,pk', 1, 2, NULL, NULL,
 'university_board_name_master','pk','board_name', 'col-md-3', 1, NOW(), NOW()),

(@g3, 'institution_name',  'Institution',            'text',   'institution_name',
 'required|string|max:200', 1, 3, 'Name of school/college', NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g3, 'year_of_passing',   'Year of Passing',        'text',   'year_of_passing',
 'required|digits:4', 1, 4, 'e.g. 2015', NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g3, 'percentage_cgpa',   'Percentage / CGPA',      'text',   'percentage_cgpa',
 'nullable|string|max:10', 0, 5, 'e.g. 75.5', NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g3, 'stream_id',         'Stream',                 'select', 'stream_id',
 'nullable|exists:stream_master,pk', 0, 6, NULL, NULL,
 'stream_master','pk','stream_name', 'col-md-3', 1, NOW(), NOW()),

(@g3, 'subject_details',   'Subject Details',        'text',   'subject_details',
 'nullable|string|max:300', 0, 7, 'Key subjects', NULL,NULL,NULL,NULL, 'col-md-9', 1, NOW(), NOW());


-- ── GROUP 4: Higher Education  ────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'higher_education', 'Higher Education (Post Graduation etc.)', 'student_master_higher_educational_details', 'replace_all', 0, 5, 4, 1, NOW(), NOW());

SET @g4 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g4, 'degree_type',      'Degree Type',            'select', 'degree_type',
 'required|in:PG,PhD,Diploma,Certificate,Other', 1, 1, NULL,
 '[{"value":"PG","label":"Post Graduation"},{"value":"PhD","label":"PhD"},{"value":"Diploma","label":"Diploma"},{"value":"Certificate","label":"Certificate"},{"value":"Other","label":"Other"}]',
 NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g4, 'subject_name',     'Subject / Specialization','text',  'subject_name',
 'required|string|max:200', 1, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g4, 'university_name',  'University',             'text',   'university_name',
 'required|string|max:200', 1, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g4, 'year_of_passing',  'Year',                   'text',   'year_of_passing',
 'required|digits:4', 1, 4, 'e.g. 2020', NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g4, 'percentage_cgpa',  '% / CGPA',               'text',   'percentage_cgpa',
 'nullable|string|max:10', 0, 5, NULL, NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW());


-- ── GROUP 5: Employment  ──────────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'employment', 'Previous Job Experience', 'student_master_employment_details', 'replace_all', 0, 5, 5, 1, NOW(), NOW());

SET @g5 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g5, 'organisation_name','Organisation',           'text',   'organisation_name',
 'nullable|string|max:200', 0, 1, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g5, 'designation',      'Designation',            'text',   'designation',
 'nullable|string|max:100', 0, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g5, 'from_date',        'Period From',            'date',   'from_date',
 'nullable|date', 0, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g5, 'to_date',          'Period To',              'date',   'to_date',
 'nullable|date', 0, 4, NULL, NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g5, 'job_type_id',      'Nature of Job',          'select', 'job_type_id',
 'nullable|exists:job_type_masters,id', 0, 5, NULL, NULL,
 'job_type_masters','id','job_type_name', 'col-md-2', 1, NOW(), NOW());


-- ── GROUP 6: Academic Distinctions  ───────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'distinctions', 'Academic Distinctions / Awards', 'student_master_academic_distinctions', 'replace_all', 0, 5, 6, 1, NOW(), NOW());

SET @g6 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g6, 'distinction_type', 'Type',          'text', 'distinction_type',
 'nullable|string|max:100', 0, 1, 'e.g. Gold Medal', NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g6, 'description',      'Description',   'text', 'description',
 'nullable|string|max:300', 0, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g6, 'year',             'Year',          'text', 'year',
 'nullable|digits:4', 0, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-2', 1, NOW(), NOW()),

(@g6, 'awarding_body',    'Awarding Body', 'text', 'awarding_body',
 'nullable|string|max:200', 0, 4, NULL, NULL,NULL,NULL,NULL, 'col-md-4', 1, NOW(), NOW());


-- ── GROUP 7: Hobbies & Skills  ────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'hobbies', 'Hobbies & Skills', 'student_master_hobbies_details', 'upsert', 0, 10, 7, 1, NOW(), NOW());

SET @g7 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g7, 'hobbies',          'Hobbies (comma-separated)',          'textarea', 'hobbies',
 'nullable|string|max:500', 0, 1, 'e.g. Reading, Trekking', NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g7, 'special_skills',   'Special Skills',                     'textarea', 'special_skills',
 'nullable|string|max:500', 0, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g7, 'extra_curricular', 'Extra-Curricular Activities',        'textarea', 'extra_curricular',
 'nullable|string|max:500', 0, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW());


-- ── GROUP 8: Sports  ──────────────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'sports_played', 'Sports Played', 'student_sports_fitness_teach_masters', 'replace_all', 0, 10, 8, 1, NOW(), NOW());

SET @g8 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g8, 'sport_id',  'Sport',  'select', 'sport_id',
 'required|exists:sports_masters,id', 1, 1, NULL, NULL,
 'sports_masters','id','sport_name', 'col-md-3', 1, NOW(), NOW()),

(@g8, 'level',     'Level',  'select', 'level',
 'nullable|in:District,State,National,International', 0, 2, NULL,
 '[{"value":"District","label":"District"},{"value":"State","label":"State"},{"value":"National","label":"National"},{"value":"International","label":"International"}]',
 NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g8, 'role',      'Role',   'select', 'role',
 'nullable|in:Player,Captain,Coach,Other', 0, 3, NULL,
 '[{"value":"Player","label":"Player"},{"value":"Captain","label":"Captain"},{"value":"Coach","label":"Coach"},{"value":"Other","label":"Other"}]',
 NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW()),

(@g8, 'year',      'Year',   'text',   'year',
 'nullable|digits:4', 0, 4, NULL, NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW());


-- ── GROUP 9: Spouse Details  ──────────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'spouse', 'Spouse Details', 'student_master_spouse_masters', 'upsert', 0, 1, 9, 1, NOW(), NOW());

SET @g9 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g9, 'spouse_name',         'Spouse Full Name',        'text',     'spouse_name',
 'nullable|string|max:200', 0, 1, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g9, 'spouse_dob',          'Spouse Date of Birth',    'date',     'spouse_dob',
 'nullable|date', 0, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g9, 'spouse_occupation',   'Occupation',              'text',     'spouse_occupation',
 'nullable|string|max:100', 0, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g9, 'spouse_organisation', 'Organisation / Department','text',    'spouse_organisation',
 'nullable|string|max:200', 0, 4, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW()),

(@g9, 'no_of_children',      'No. of Children',         'number',   'no_of_children',
 'nullable|integer|min:0', 0, 5, NULL, NULL,NULL,NULL,NULL, 'col-md-4', 1, NOW(), NOW()),

(@g9, 'children_details',    'Children Details',        'textarea', 'children_details',
 'nullable|string|max:500', 0, 6, 'Name(s), age(s)', NULL,NULL,NULL,NULL, 'col-md-8', 1, NOW(), NOW());


-- ── GROUP 10: Dress / Clothing Sizes  ─────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'dress_code', 'Clothing / Dress Sizes', 'student_cloth_size_master_details', 'upsert', 0, 1, 10, 1, NOW(), NOW());

SET @g10 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g10, 'shirt_size',   'T-Shirt / Shirt Size',    'select', 'shirt_size',
 'nullable|exists:student_cloths_size_master,pk', 0, 1, NULL, NULL,
 'student_cloths_size_master','pk','cloth_size', 'col-md-3', 1, NOW(), NOW()),

(@g10, 'trouser_size', 'Trouser / Track Suit Size','select', 'trouser_size',
 'nullable|exists:student_cloths_size_master,pk', 0, 2, NULL, NULL,
 'student_cloths_size_master','pk','cloth_size', 'col-md-3', 1, NOW(), NOW()),

(@g10, 'blazer_size',  'Blazer / Jacket Size',    'select', 'blazer_size',
 'nullable|exists:student_cloths_size_master,pk', 0, 3, NULL, NULL,
 'student_cloths_size_master','pk','cloth_size', 'col-md-3', 1, NOW(), NOW()),

(@g10, 'shoe_size',    'Shoe Size',               'number', 'shoe_size',
 'nullable|integer|min:4|max:14', 0, 4, 'e.g. 8', NULL,NULL,NULL,NULL, 'col-md-3', 1, NOW(), NOW());


-- ── GROUP 11: Pre-Medical History  ────────────────────────────────────────
INSERT INTO fc_form_field_groups
    (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES
    (@s3, 'pre_medical_history', 'Pre-Medical History', 'fc_pre_history', 'upsert', 0, 1, 11, 1, NOW(), NOW());

SET @g11 = LAST_INSERT_ID();

INSERT INTO fc_form_group_fields
    (group_id, field_name, label, field_type, target_column,
     validation_rules, is_required, display_order, placeholder,
     options_json, lookup_table, lookup_value_column, lookup_label_column,
     css_class, is_active, created_at, updated_at)
VALUES
(@g11, 'allergy_illness',     'History of allergy / previous illness / injury / disability / asthma / slip disc / blood transfusion',
 'textarea', 'allergy_illness',
 'nullable|string|max:1000', 0, 1, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW()),

(@g11, 'prolonged_medication','History of prolonged medication',
 'textarea', 'prolonged_medication',
 'nullable|string|max:1000', 0, 2, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW()),

(@g11, 'hospital_history',    'History of hospitalisation / surgery',
 'textarea', 'hospital_history',
 'nullable|string|max:1000', 0, 3, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW()),

(@g11, 'altitude_illness',    'History of altitude illness / motion sickness',
 'textarea', 'altitude_illness',
 'nullable|string|max:1000', 0, 4, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW()),

(@g11, 'additional_info',     'Any other relevant medical information',
 'textarea', 'additional_info',
 'nullable|string|max:1000', 0, 5, NULL, NULL,NULL,NULL,NULL, 'col-md-12', 1, NOW(), NOW()),

(@g11, 'pre_med_doc',         'Supporting Document (PDF or image)',
 'file', 'doc_path',
 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', 0, 6, NULL, NULL,NULL,NULL,NULL, 'col-md-6', 1, NOW(), NOW());


-- =============================================================================
-- END — Verify with:
--   SELECT f.form_name, s.step_name, COUNT(ff.id) flat_fields,
--          COUNT(DISTINCT fg.id) groups
--   FROM fc_forms f
--   JOIN fc_form_steps s ON s.form_id = f.id
--   LEFT JOIN fc_form_fields ff ON ff.step_id = s.id
--   LEFT JOIN fc_form_field_groups fg ON fg.step_id = s.id
--   WHERE f.form_slug = 'fc-registration-99th'
--   GROUP BY f.form_name, s.step_name
--   ORDER BY s.step_number;
-- =============================================================================
