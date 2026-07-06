-- =============================================================================
-- Build a FRESH dynamic form: "99th Foundation Course Registration"
-- Mirrors the legacy PHP form (PDF) — tab sequence, section headings & fields.
-- Uses the existing dynamic engine ONLY (fc_forms / fc_form_steps /
-- fc_form_fields / fc_form_field_groups / fc_form_group_fields). No code changes.
--
-- Steps:
--   1 Descriptive Roll                (flat; firsts + seconds + knowledge_hindi)
--   2 Descriptive Roll Continue…      (grouped: 10 groups incl. Pre-Medical History)
--   3 Joining Documents               (flat; 15 files -> fc_joining_documents_user_uploads)
--   4 Bank Details                    (flat; bank + 3 doc uploads)
--   5 Health Risk Factors             (flat; 9 radios + notes)
--   6 Special Assistant               (flat; impairment + adjustments + doc)
--   7 Vision Statement                (flat; single textarea)
--
-- ASSUMPTIONS (change if wrong — all isolated & easy to edit):
--   • "Background" options = Rural/Urban   (Section 0 / Step 1)
--   • Guardian fields stored in new guardian_* columns on student_master_seconds
--   • Hobbies & Academic Distinction = repeatable rows (replace_all)
--   • Pre-Medical History group (fc_pre_history) always present in Step 2
--
-- HOW TO RUN: set @course_pk below, then run the whole file in phpMyAdmin or
-- `mysql`. SAFE TO RE-RUN: it deletes any existing copy of this form first and
-- only adds columns that don't already exist. Form is created INACTIVE; preview
-- via its admin/edit URL, then flip is_active = 1 when ready.
-- =============================================================================

SET @course_pk = 11;                       -- <<< 99th Foundation Course (course_master.pk). CHANGE if needed.
SET @form_slug = 'fc-99th-fresh';          -- <<< unique slug for the new form
SET @now = NOW();
SET SQL_SAFE_UPDATES = 0;                  -- allow the cleanup DELETEs below

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 0 · Delete any existing copy of this form (children first)
-- ─────────────────────────────────────────────────────────────────────────────

SET @old_id = (SELECT id FROM fc_forms WHERE form_slug = @form_slug LIMIT 1);

DELETE gf FROM fc_form_group_fields gf
    JOIN fc_form_field_groups g ON g.id = gf.group_id
    JOIN fc_form_steps s        ON s.id = g.step_id
    WHERE s.form_id = @old_id;

DELETE g FROM fc_form_field_groups g
    JOIN fc_form_steps s ON s.id = g.step_id
    WHERE s.form_id = @old_id;

DELETE f FROM fc_form_fields f
    JOIN fc_form_steps s ON s.id = f.step_id
    WHERE s.form_id = @old_id;

DELETE FROM fc_form_steps WHERE form_id = @old_id;
DELETE FROM fc_forms      WHERE id      = @old_id;

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 1 · New columns the PDF needs (idempotent — only adds if missing)
-- ─────────────────────────────────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS fc99_add_col;
DELIMITER $$
CREATE PROCEDURE fc99_add_col(IN p_tbl VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_tbl AND COLUMN_NAME = p_col
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_tbl, '` ADD COLUMN ', p_def);
        PREPARE st FROM @ddl; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

CALL fc99_add_col('student_master_firsts','background','`background` VARCHAR(100) NULL');

CALL fc99_add_col('student_master_seconds','birth_area_type','`birth_area_type` VARCHAR(50) NULL');
CALL fc99_add_col('student_master_seconds','guardian_or_spouse','`guardian_or_spouse` VARCHAR(20) NULL');
CALL fc99_add_col('student_master_seconds','guardian_first_name','`guardian_first_name` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','guardian_middle_name','`guardian_middle_name` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','guardian_last_name','`guardian_last_name` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','guardian_contact_no','`guardian_contact_no` VARCHAR(20) NULL');
CALL fc99_add_col('student_master_seconds','guardian_email','`guardian_email` VARCHAR(150) NULL');
CALL fc99_add_col('student_master_seconds','perm_district','`perm_district` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','perm_city_name','`perm_city_name` VARCHAR(150) NULL');
CALL fc99_add_col('student_master_seconds','pres_district','`pres_district` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','pres_city_name','`pres_city_name` VARCHAR(150) NULL');
CALL fc99_add_col('student_master_seconds','highest_stream_id','`highest_stream_id` BIGINT NULL');
CALL fc99_add_col('student_master_seconds','matric_state_id','`matric_state_id` BIGINT NULL');
CALL fc99_add_col('student_master_seconds','matric_district','`matric_district` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','matric_city','`matric_city` VARCHAR(100) NULL');
CALL fc99_add_col('student_master_seconds','matric_city_name','`matric_city_name` VARCHAR(150) NULL');
CALL fc99_add_col('student_master_seconds','cse_attempts','`cse_attempts` INT NULL');
CALL fc99_add_col('student_master_seconds','previous_service_id','`previous_service_id` BIGINT NULL');

CALL fc99_add_col('student_master_qualification_details','institution_type','`institution_type` VARCHAR(50) NULL');
CALL fc99_add_col('student_master_qualification_details','to_year','`to_year` VARCHAR(10) NULL');
CALL fc99_add_col('student_master_qualification_details','division','`division` VARCHAR(20) NULL');

CALL fc99_add_col('student_cloth_size_master_details','track_suit_size','`track_suit_size` VARCHAR(20) NULL');

CALL fc99_add_col('student_master_spouse_masters','spouse_in_cse','`spouse_in_cse` VARCHAR(10) NULL');

CALL fc99_add_col('new_registration_bank_details_masters','doc_aadhar_path','`doc_aadhar_path` VARCHAR(255) NULL');
CALL fc99_add_col('new_registration_bank_details_masters','doc_pan_path','`doc_pan_path` VARCHAR(255) NULL');
CALL fc99_add_col('new_registration_bank_details_masters','doc_cancel_cheque_path','`doc_cancel_cheque_path` VARCHAR(255) NULL');

-- Core student-master columns the form writes to that may be absent on leaner
-- installs (present on some DBs, missing on others). Idempotent — added if missing.
CALL fc99_add_col('student_master_firsts','full_name_hindi','`full_name_hindi` varchar(200) NULL');
CALL fc99_add_col('student_master_firsts','first_name','`first_name` varchar(100) NULL');
CALL fc99_add_col('student_master_firsts','middle_name','`middle_name` varchar(100) NULL');
CALL fc99_add_col('student_master_firsts','last_name','`last_name` varchar(100) NULL');
CALL fc99_add_col('student_master_firsts','pan_card','`pan_card` varchar(20) NULL');
CALL fc99_add_col('student_master_firsts','aadhar_number','`aadhar_number` varchar(20) NULL');
CALL fc99_add_col('student_master_firsts','passport_no','`passport_no` varchar(20) NULL');
CALL fc99_add_col('student_master_firsts','alt_mobile_no','`alt_mobile_no` varchar(20) NULL');
CALL fc99_add_col('student_master_firsts','alt_email','`alt_email` varchar(150) NULL');
CALL fc99_add_col('student_master_firsts','instagram_id','`instagram_id` varchar(100) NULL');
CALL fc99_add_col('student_master_firsts','twitter_id','`twitter_id` varchar(100) NULL');
CALL fc99_add_col('student_master_firsts','vision_statement','`vision_statement` text NULL');
CALL fc99_add_col('student_master_seconds','birth_state_id','`birth_state_id` bigint unsigned NULL');
CALL fc99_add_col('student_master_seconds','birth_district','`birth_district` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','birth_city','`birth_city` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','mother_first_name','`mother_first_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','mother_middle_name','`mother_middle_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','mother_last_name','`mother_last_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','mother_qualification_id','`mother_qualification_id` bigint unsigned NULL');
CALL fc99_add_col('student_master_seconds','mother_profession_id','`mother_profession_id` bigint unsigned NULL');
CALL fc99_add_col('student_master_seconds','mother_annual_income','`mother_annual_income` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','father_first_name','`father_first_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','father_middle_name','`father_middle_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','father_last_name','`father_last_name` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','father_qualification_id','`father_qualification_id` bigint unsigned NULL');
CALL fc99_add_col('student_master_seconds','father_annual_income','`father_annual_income` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','domicile_state_id','`domicile_state_id` BIGINT UNSIGNED NULL');
CALL fc99_add_col('student_master_seconds','domicile_district','`domicile_district` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','dietary_preference','`dietary_preference` varchar(50) NULL');
CALL fc99_add_col('student_master_seconds','high_altitude_condition','`high_altitude_condition` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','high_altitude_remarks','`high_altitude_remarks` text NULL');
CALL fc99_add_col('student_master_seconds','health_asthma','`health_asthma` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','health_lung_disease','`health_lung_disease` varchar(50) NULL');
CALL fc99_add_col('student_master_seconds','health_kidney_disease','`health_kidney_disease` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','health_diabetes','`health_diabetes` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','health_blood_disorder','`health_blood_disorder` varchar(50) NULL');
CALL fc99_add_col('student_master_seconds','health_immunocompromised','`health_immunocompromised` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','health_liver_disease','`health_liver_disease` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','health_cardiac_condition','`health_cardiac_condition` varchar(100) NULL');
CALL fc99_add_col('student_master_seconds','health_pregnant_lactating','`health_pregnant_lactating` varchar(10) NULL');
CALL fc99_add_col('student_master_seconds','health_additional_info','`health_additional_info` text NULL');

-- Step completion-flag columns (written on each step's target table when saved).
CALL fc99_add_col('student_master_firsts','step1_completed','`step1_completed` tinyint(1) NULL DEFAULT 0');
CALL fc99_add_col('student_master_seconds','health_completed','`health_completed` tinyint(1) NULL DEFAULT 0');
CALL fc99_add_col('student_master_firsts','vision_completed','`vision_completed` tinyint(1) NULL DEFAULT 0');
CALL fc99_add_col('student_iosr_reasonable_adjust_masters','special_completed','`special_completed` tinyint(1) NULL DEFAULT 0');

DROP PROCEDURE IF EXISTS fc99_add_col;


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 2 · The form
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_forms (form_name, form_slug, description, icon, consolidation_table, user_identifier, is_active, course_master_pk, created_at, updated_at)
VALUES ('99th Foundation Course Registration', @form_slug, 'Descriptive roll based registration for the 99th Foundation Course.', 'bi-file-text', 'student_masters', 'user_id', 0, @course_pk, @now, @now);
SET @form_id = LAST_INSERT_ID();


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 3 · STEP 1 — Descriptive Roll  (flat; firsts + seconds + knowledge_hindi)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Descriptive Roll', 'fc99-step1', 1, 'student_master_firsts', 'step1_completed', 'step1_done', 1, 'Personal, family, address, physical & language details.', 'bi-person-fill', @now, @now);
SET @s1 = LAST_INSERT_ID();

INSERT INTO fc_form_fields
  (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, options_json, lookup_table, lookup_value_column, lookup_label_column, section_heading, css_class, file_max_kb, file_extensions, is_active, created_at, updated_at)
VALUES
-- § Personal Details
(@s1,'full_name_hindi','पूरा नाम ( हिंदी )','text','student_master_firsts','full_name_hindi','required|string|max:200',1,1,NULL,NULL,NULL,NULL,'Personal Details','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'first_name','First Name','text','student_master_firsts','first_name','required|string|max:100',1,2,NULL,NULL,NULL,NULL,'Personal Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'middle_name','Middle Name','text','student_master_firsts','middle_name','nullable|string|max:100',0,3,NULL,NULL,NULL,NULL,'Personal Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'last_name','Last Name / Surname','text','student_master_firsts','last_name','nullable|string|max:100',0,4,NULL,NULL,NULL,NULL,'Personal Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'gender','Gender','select','student_master_firsts','gender','required|in:Male,Female,Transgender',1,5,'[{"value":"Male","label":"Male"},{"value":"Female","label":"Female"},{"value":"Transgender","label":"Transgender"}]',NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'date_of_birth','Date Of Birth','date','student_master_firsts','date_of_birth','required|date|before:today',1,6,NULL,NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'nationality','Nationality','select','student_master_seconds','nationality','required|in:Indian,Other',1,7,'[{"value":"Indian","label":"Indian"},{"value":"Other","label":"Other"}]',NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'background','Background','select','student_master_firsts','background','required|in:Rural,Urban',1,8,'[{"value":"Rural","label":"Rural"},{"value":"Urban","label":"Urban"}]',NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'marital_status','Marital Status','select','student_master_seconds','marital_status','required|in:Single,Married,Divorced,Widowed',1,9,'[{"value":"Single","label":"Single"},{"value":"Married","label":"Married"},{"value":"Divorced","label":"Divorced"},{"value":"Widowed","label":"Widowed"}]',NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'religion_id','Religion','select','student_master_seconds','religion_id','required|exists:religion_master,pk',1,10,NULL,'religion_master','pk','religion_name','Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'category_id','Category','select','student_master_seconds','category_id','required|exists:caste_category_master,pk',1,11,NULL,'caste_category_master','pk','Seat_name','Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'pan_card','PAN Card','text','student_master_firsts','pan_card','nullable|string|max:20|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',0,12,NULL,NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'aadhar_number','Aadhar Number','text','student_master_firsts','aadhar_number','required|digits:12',1,13,NULL,NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'passport_no','Passport No','text','student_master_firsts','passport_no','nullable|string|max:20',0,14,NULL,NULL,NULL,NULL,'Personal Details','col-md-6',NULL,NULL,1,@now,@now),
-- § Birth Place Details
(@s1,'birth_state_id','State','select','student_master_seconds','birth_state_id','required|exists:state_master,pk',1,15,NULL,'state_master','pk','state_name','Birth Place Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'birth_district','District','text','student_master_seconds','birth_district','required|string|max:100',1,16,NULL,NULL,NULL,NULL,'Birth Place Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'birth_area_type','Category (Area Type)','select','student_master_seconds','birth_area_type','required|in:City,Town,Village',1,17,'[{"value":"City","label":"City"},{"value":"Town","label":"Town"},{"value":"Village","label":"Village"}]',NULL,NULL,NULL,'Birth Place Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'birth_city','Village / City','text','student_master_seconds','birth_city','required|string|max:100',1,18,NULL,NULL,NULL,NULL,'Birth Place Details','col-md-3',NULL,NULL,1,@now,@now),
-- § Contact Details
(@s1,'mobile_no','Mobile No','text','student_master_firsts','mobile_no','required|digits:10',1,19,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'alt_mobile_no','Alternate Mobile No','text','student_master_firsts','alt_mobile_no','nullable|digits:10',0,20,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'email','Email Id','email','student_master_firsts','email','required|email|max:150',1,21,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'alt_email','Alternate Email Id','email','student_master_firsts','alt_email','nullable|email|max:150',0,22,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'instagram_id','Instagram ID','text','student_master_firsts','instagram_id','nullable|string|max:100',0,23,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'twitter_id','Twitter ID','text','student_master_firsts','twitter_id','nullable|string|max:100',0,24,NULL,NULL,NULL,NULL,'Contact Details','col-md-6',NULL,NULL,1,@now,@now),
-- § Mother's Detail
(@s1,'mother_first_name','First Name','text','student_master_seconds','mother_first_name','required|string|max:100',1,25,NULL,NULL,NULL,NULL,'Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'mother_middle_name','Middle Name','text','student_master_seconds','mother_middle_name','nullable|string|max:100',0,26,NULL,NULL,NULL,NULL,'Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'mother_last_name','Last Name / Surname','text','student_master_seconds','mother_last_name','nullable|string|max:100',0,27,NULL,NULL,NULL,NULL,'Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'mother_qualification_id','Qualification','select','student_master_seconds','mother_qualification_id','required|exists:parent_qualification_master,pk',1,28,NULL,'parent_qualification_master','pk','qualification_name','Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'mother_profession_id','Main Profession','select','student_master_seconds','mother_profession_id','required|exists:parents_profession_master,pk',1,29,NULL,'parents_profession_master','pk','profession_name','Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'mother_annual_income','Annual Income (Rs)','number','student_master_seconds','mother_annual_income','required|numeric|min:0',1,30,NULL,NULL,NULL,NULL,'Mother''s Detail','col-md-4',NULL,NULL,1,@now,@now),
-- § Father's Details
(@s1,'father_first_name','First Name','text','student_master_seconds','father_first_name','required|string|max:100',1,31,NULL,NULL,NULL,NULL,'Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'father_middle_name','Middle Name','text','student_master_seconds','father_middle_name','nullable|string|max:100',0,32,NULL,NULL,NULL,NULL,'Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'father_last_name','Last Name / Surname','text','student_master_seconds','father_last_name','required|string|max:100',1,33,NULL,NULL,NULL,NULL,'Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'father_qualification_id','Qualification','select','student_master_seconds','father_qualification_id','required|exists:parent_qualification_master,pk',1,34,NULL,'parent_qualification_master','pk','qualification_name','Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'father_profession_id','Main Profession','select','student_master_seconds','father_profession_id','required|exists:father_professions,id',1,35,NULL,'father_professions','id','profession_name','Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'father_annual_income','Annual Income (Rs)','number','student_master_seconds','father_annual_income','required|numeric|min:0',1,36,NULL,NULL,NULL,NULL,'Father''s Details','col-md-4',NULL,NULL,1,@now,@now),
-- § Guardian's / Spouse Detail
(@s1,'guardian_or_spouse','Guardian / Spouse','radio','student_master_seconds','guardian_or_spouse','nullable|in:Guardian,Spouse',0,37,'[{"value":"Guardian","label":"Guardian''s Detail"},{"value":"Spouse","label":"Spouse Detail"}]',NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'guardian_first_name','First Name','text','student_master_seconds','guardian_first_name','nullable|string|max:100',0,38,NULL,NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'guardian_middle_name','Middle Name','text','student_master_seconds','guardian_middle_name','nullable|string|max:100',0,39,NULL,NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'guardian_last_name','Last Name / Surname','text','student_master_seconds','guardian_last_name','nullable|string|max:100',0,40,NULL,NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'guardian_contact_no','Contact No','text','student_master_seconds','guardian_contact_no','nullable|digits:10',0,41,NULL,NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'guardian_email','E-mail ID','email','student_master_seconds','guardian_email','nullable|email|max:150',0,42,NULL,NULL,NULL,NULL,'Guardian''s / Spouse Detail','col-md-6',NULL,NULL,1,@now,@now),
-- § Permanent Address
(@s1,'perm_address_line1','Address','textarea','student_master_seconds','perm_address_line1','required|string|max:300',1,43,NULL,NULL,NULL,NULL,'Permanent Address','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'perm_country_id','Country','select','student_master_seconds','perm_country_id','required|exists:country_master,pk',1,44,NULL,'country_master','pk','country_name','Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'perm_state_id','State','select','student_master_seconds','perm_state_id','required|exists:state_master,pk',1,45,NULL,'state_master','pk','state_name','Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'perm_district','District','text','student_master_seconds','perm_district','required|string|max:100',1,46,NULL,NULL,NULL,NULL,'Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'perm_city','City / Village','text','student_master_seconds','perm_city','required|string|max:100',1,47,NULL,NULL,NULL,NULL,'Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'perm_city_name','Enter City / Town Name','text','student_master_seconds','perm_city_name','required|string|max:150',1,48,NULL,NULL,NULL,NULL,'Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'perm_pincode','Postal Code','text','student_master_seconds','perm_pincode','required|digits:6',1,49,NULL,NULL,NULL,NULL,'Permanent Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'domicile_state_id','Domicile State','select','student_master_seconds','domicile_state_id','required|exists:state_master,pk',1,50,NULL,'state_master','pk','state_name','Permanent Address','col-md-6',NULL,NULL,1,@now,@now),
(@s1,'domicile_district','Domicile District','text','student_master_seconds','domicile_district','required|string|max:100',1,51,NULL,NULL,NULL,NULL,'Permanent Address','col-md-6',NULL,NULL,1,@now,@now),
-- § Mailing Address
(@s1,'same_as_permanent','Same as above','checkbox','student_master_seconds','_skip','nullable|boolean',0,52,NULL,NULL,NULL,NULL,'Mailing Address','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'pres_address_line1','Address','textarea','student_master_seconds','pres_address_line1','required_without:same_as_permanent|nullable|string|max:300',0,53,NULL,NULL,NULL,NULL,'Mailing Address','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'pres_country_id','Country','select','student_master_seconds','pres_country_id','required_without:same_as_permanent|nullable|exists:country_master,pk',0,54,NULL,'country_master','pk','country_name','Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'pres_state_id','State','select','student_master_seconds','pres_state_id','required_without:same_as_permanent|nullable|exists:state_master,pk',0,55,NULL,'state_master','pk','state_name','Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'pres_district','District','text','student_master_seconds','pres_district','nullable|string|max:100',0,56,NULL,NULL,NULL,NULL,'Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'pres_city','City / Village','text','student_master_seconds','pres_city','required_without:same_as_permanent|nullable|string|max:100',0,57,NULL,NULL,NULL,NULL,'Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'pres_city_name','Enter City / Town Name','text','student_master_seconds','pres_city_name','nullable|string|max:150',0,58,NULL,NULL,NULL,NULL,'Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'pres_pincode','Postal Code','text','student_master_seconds','pres_pincode','required_without:same_as_permanent|nullable|digits:6',0,59,NULL,NULL,NULL,NULL,'Mailing Address','col-md-4',NULL,NULL,1,@now,@now),
-- § Physical Details
(@s1,'height_cm','Height (cm)','number','student_master_seconds','height_cm','required|numeric|min:50|max:300',1,60,NULL,NULL,NULL,NULL,'Physical Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'weight_kg','Weight (kg)','number','student_master_seconds','weight_kg','required|numeric|min:20|max:300',1,61,NULL,NULL,NULL,NULL,'Physical Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'blood_group','Blood Group','select','student_master_seconds','blood_group','required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',1,62,'[{"value":"A+","label":"A+"},{"value":"A-","label":"A-"},{"value":"B+","label":"B+"},{"value":"B-","label":"B-"},{"value":"AB+","label":"AB+"},{"value":"AB-","label":"AB-"},{"value":"O+","label":"O+"},{"value":"O-","label":"O-"}]',NULL,NULL,NULL,'Physical Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'dietary_preference','Dietary Preference','select','student_master_seconds','dietary_preference','required|in:Vegetarian,Non-Vegetarian,Vegan,Jain',1,63,'[{"value":"Vegetarian","label":"Vegetarian"},{"value":"Non-Vegetarian","label":"Non-Vegetarian"},{"value":"Vegan","label":"Vegan"},{"value":"Jain","label":"Jain"}]',NULL,NULL,NULL,'Physical Details','col-md-3',NULL,NULL,1,@now,@now),
(@s1,'high_altitude_condition','Do you have any medical condition that might adversely affect you in a high-altitude trek (above 400 meters)?','radio','student_master_seconds','high_altitude_condition','required|in:Yes,No',1,64,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]',NULL,NULL,NULL,'Physical Details','col-md-12',NULL,NULL,1,@now,@now),
(@s1,'high_altitude_remarks','Remarks (If any)','textarea','student_master_seconds','high_altitude_remarks','nullable|string|max:500',0,65,NULL,NULL,NULL,NULL,'Physical Details','col-md-12',NULL,NULL,1,@now,@now),
-- § Image Upload
(@s1,'photo','Upload your Photo','file','student_master_firsts','photo_path','required|image|mimes:jpeg,jpg,png|max:500',1,66,NULL,NULL,NULL,NULL,'Image Upload','col-md-6',500,'jpeg,jpg,png',1,@now,@now),
(@s1,'signature','Upload your Signature','file','student_master_firsts','signature_path','required|image|mimes:jpeg,jpg,png|max:200',1,67,NULL,NULL,NULL,NULL,'Image Upload','col-md-6',200,'jpeg,jpg,png',1,@now,@now),
-- § Language Details
(@s1,'mother_tongue','Mother Tongue','select','student_knowledge_hindi_masters','mother_tongue','required|exists:language_master,pk',1,68,NULL,'language_master','pk','language_name','Language Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'medium_12th','Medium in Class 12','select','student_knowledge_hindi_masters','medium_12th','required|exists:language_master,pk',1,69,NULL,'language_master','pk','language_name','Language Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'medium_graduation','Medium in Graduation','select','student_knowledge_hindi_masters','medium_graduation','required|exists:language_master,pk',1,70,NULL,'language_master','pk','language_name','Language Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'medium_civil_service','Medium in Civil Service Exam','select','student_knowledge_hindi_masters','medium_civil_service','required|exists:language_master,pk',1,71,NULL,'language_master','pk','language_name','Language Details','col-md-4',NULL,NULL,1,@now,@now),
(@s1,'viva_language','Language of Civil Service Exam Viva / Interview','select','student_knowledge_hindi_masters','viva_language','required|exists:language_master,pk',1,72,NULL,'language_master','pk','language_name','Language Details','col-md-4',NULL,NULL,1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 4 · STEP 2 — Descriptive Roll Continue…  (grouped: 10 groups)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Descriptive Roll Continue…', 'fc99-step3', 2, 'student_masters', NULL, 'step3_done', 1, 'Languages, education, employment, distinctions, hobbies, dress & spouse.', 'bi-list-ul', @now, @now);
SET @s2 = LAST_INSERT_ID();

-- Group 1: Languages Known (replace_all)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'languages','Languages Known','student_master_language_knowns','replace_all',1,20,1,1,@now,@now);
SET @g1 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at) VALUES
(@g1,'language_id','Language','select','language_id','required|exists:language_master,pk',1,1,NULL,'language_master','pk','language_name','col-md-3',1,@now,@now),
(@g1,'can_speak','Speak','checkbox','can_speak','nullable',0,2,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g1,'can_read','Read','checkbox','can_read','nullable',0,3,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g1,'can_write','Write','checkbox','can_write','nullable',0,4,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now);

-- Group 2: Knowledge of Hindi (upsert)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'knowledge_of_hindi','Knowledge of Hindi','student_knowledge_hindi_masters','upsert',0,1,2,1,@now,@now);
SET @g2 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, css_class, is_active, created_at, updated_at) VALUES
(@g2,'passed_matric_hindi','Have passed Matriculation OR equivalent / Higher Examination with Hindi as one of the subject?','select','passed_matric_hindi','required|in:Yes,No',1,1,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-12',1,@now,@now),
(@g2,'selected_cse_hindi','Have been selected in CSE with Hindi as qualifying subject?','select','selected_cse_hindi','required|in:Yes,No',1,2,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-12',1,@now,@now),
(@g2,'hindi_mother_tongue','Is Hindi your Mother Tongue?','select','hindi_mother_tongue','required|in:Yes,No',1,3,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-12',1,@now,@now);

-- Group 3: Educational Details (replace_all)  [Higher Education upto 10th]
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'qualifications','Educational Details [Starting from Higher Education upto 10th]','student_master_qualification_details','replace_all',1,10,3,1,@now,@now);
SET @g3 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at) VALUES
(@g3,'qualification_id','Degree','select','qualification_id','required|exists:qualification_master,pk',1,1,NULL,'qualification_master','pk','qualification','col-md-3',1,@now,@now),
(@g3,'board_id','University / Board Name','select','board_id','required|exists:university_board_name_master,pk',1,2,NULL,'university_board_name_master','pk','board_name','col-md-3',1,@now,@now),
(@g3,'institution_name','Institution','text','institution_name','required|string|max:200',1,3,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g3,'institution_type','Institution Type','select','institution_type','required|in:Government,Private,Autonomous,Deemed',1,4,'[{"value":"Government","label":"Government"},{"value":"Private","label":"Private"},{"value":"Autonomous","label":"Autonomous"},{"value":"Deemed","label":"Deemed"}]',NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g3,'year_of_passing','Year','text','year_of_passing','required|digits:4',1,5,NULL,NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g3,'to_year','To Year','text','to_year','required|digits:4',1,6,NULL,NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g3,'division','Division','select','division','required|in:First,Second,Third',1,7,'[{"value":"First","label":"First"},{"value":"Second","label":"Second"},{"value":"Third","label":"Third"}]',NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g3,'percentage_cgpa','Percentage (%)','text','percentage_cgpa','nullable|string|max:10',0,8,NULL,NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g3,'subject_details','Subjects','text','subject_details','required|string|max:300',1,9,NULL,NULL,NULL,NULL,'col-md-4',1,@now,@now);

-- Group 4: Education Summary (upsert -> student_master_seconds)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'education_summary','Education Summary','student_master_seconds','upsert',0,1,4,1,@now,@now);
SET @g4 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at) VALUES
(@g4,'highest_stream_id','Highest Qualification Stream','select','highest_stream_id','required|exists:stream_master,pk',1,1,NULL,'stream_master','pk','stream_name','col-md-6',1,@now,@now),
(@g4,'matric_state_id','Matriculation State','select','matric_state_id','required|exists:state_master,pk',1,2,NULL,'state_master','pk','state_name','col-md-6',1,@now,@now),
(@g4,'matric_district','Matriculation District','text','matric_district','required|string|max:100',1,3,NULL,NULL,NULL,NULL,'col-md-6',1,@now,@now),
(@g4,'matric_city','Matriculation City / Village','text','matric_city','required|string|max:100',1,4,NULL,NULL,NULL,NULL,'col-md-6',1,@now,@now),
(@g4,'matric_city_name','Enter City / Town Name','text','matric_city_name','required|string|max:150',1,5,NULL,NULL,NULL,NULL,'col-md-6',1,@now,@now),
(@g4,'cse_attempts','No. of Attempts at Civil Service Exam till date','number','cse_attempts','required|integer|min:0|max:20',1,6,NULL,NULL,NULL,NULL,'col-md-6',1,@now,@now),
(@g4,'previous_service_id','Previous service joined from a previous attempt, if any','select','previous_service_id','nullable|exists:service_master,pk',0,7,NULL,'service_master','pk','service_name','col-md-12',1,@now,@now);

-- Group 5: Previous Job Experience (replace_all)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'employment','Previous Job Experience Details If Any','student_master_employment_details','replace_all',0,3,5,1,@now,@now);
SET @g5 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at) VALUES
(@g5,'organisation_name','Organization','text','organisation_name','nullable|string|max:200',0,1,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g5,'designation','Designation','text','designation','nullable|string|max:100',0,2,NULL,NULL,NULL,NULL,'col-md-3',1,@now,@now),
(@g5,'from_date','Period From','date','from_date','nullable|date',0,3,NULL,NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g5,'to_date','Period To','date','to_date','nullable|date',0,4,NULL,NULL,NULL,NULL,'col-md-2',1,@now,@now),
(@g5,'job_type_id','Nature of Job','select','job_type_id','nullable|exists:job_type_masters,id',0,5,NULL,'job_type_masters','id','job_type_name','col-md-2',1,@now,@now);

-- Group 6: Academic Distinction (replace_all)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'distinctions','Academic Distinction','student_master_academic_distinctions','replace_all',0,3,6,1,@now,@now);
SET @g6 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at) VALUES
(@g6,'distinction_type','Academic Distinction','text','distinction_type','nullable|string|max:200',0,1,'col-md-12',1,@now,@now);

-- Group 7: Hobbies (upsert — one row per user on student_master_hobbies_details)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'hobbies','Hobbies','student_master_hobbies_details','upsert',0,10,7,1,@now,@now);
SET @g7 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at) VALUES
(@g7,'hobbies','Hobbies','textarea','hobbies','nullable|string|max:1000',0,1,'col-md-12',1,@now,@now);

-- Group 8: Dress Code (upsert)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'dress_code','Dress Code','student_cloth_size_master_details','upsert',0,1,8,1,@now,@now);
SET @g8 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at) VALUES
(@g8,'shirt_size','Select your T-shirt size','select','shirt_size','required|exists:student_cloths_size_master,pk',1,1,'student_cloths_size_master','pk','cloth_size','col-md-3',1,@now,@now),
(@g8,'blazer_size','Select your Blazer / Jacket size','select','blazer_size','required|exists:student_cloths_size_master,pk',1,2,'student_cloths_size_master','pk','cloth_size','col-md-3',1,@now,@now),
(@g8,'trouser_size','Select your Trouser size','select','trouser_size','required|exists:student_cloths_size_master,pk',1,3,'student_cloths_size_master','pk','cloth_size','col-md-3',1,@now,@now),
(@g8,'track_suit_size','Select your Track Suit size','select','track_suit_size','required|exists:student_cloths_size_master,pk',1,4,'student_cloths_size_master','pk','cloth_size','col-md-3',1,@now,@now);

-- Group 9: Spouse in Civil Service (upsert)
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'spouse','For candidates whose Spouse is in Civil Service','student_master_spouse_masters','upsert',0,1,9,1,@now,@now);
SET @g9 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, css_class, is_active, created_at, updated_at) VALUES
(@g9,'spouse_in_cse','Is your spouse also registering for the Foundation Course?','radio','spouse_in_cse','nullable|in:Yes,No',0,1,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-12',1,@now,@now),
(@g9,'spouse_name','Spouse Name','text','spouse_name','nullable|string|max:200',0,2,NULL,'col-md-6',1,@now,@now);

-- Group 10: Pre-Medical History (upsert) — always present in the dynamic form
INSERT INTO fc_form_field_groups (step_id, group_name, group_label, target_table, save_mode, min_rows, max_rows, display_order, is_active, created_at, updated_at)
VALUES (@s2,'pre_medical_history','Pre-Medical History','fc_pre_history','upsert',0,1,10,1,@now,@now);
SET @g10 = LAST_INSERT_ID();
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at) VALUES
(@g10,'allergy_illness','History of allergy / previous illness / injury / disability / asthma / slip disc / blood transfusion','textarea','allergy_illness','nullable|string|max:1000',0,1,'col-md-12',1,@now,@now),
(@g10,'prolonged_medication','History of prolonged medication','textarea','prolonged_medication','nullable|string|max:1000',0,2,'col-md-12',1,@now,@now),
(@g10,'hospital_history','History of hospitalisation / surgery','textarea','hospital_history','nullable|string|max:1000',0,3,'col-md-12',1,@now,@now),
(@g10,'altitude_illness','History of altitude illness / motion sickness','textarea','altitude_illness','nullable|string|max:1000',0,4,'col-md-12',1,@now,@now),
(@g10,'additional_info','Any other relevant medical information','textarea','additional_info','nullable|string|max:1000',0,5,'col-md-12',1,@now,@now),
(@g10,'pre_med_doc','Supporting Document (PDF or image)','file','doc_path','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,6,'col-md-6',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 5 · STEP 3 — Joining Documents  (flat; 15 file uploads)
--   Stored in the legacy doc table fc_joining_documents_user_uploads (admin_*/
--   accounts_* varchar(255) path columns). Admin verification of each uploaded
--   field is tracked at runtime in fc_form_document_verifications (by form_field_id).
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Joining Documents', 'fc99-joining-documents', 3, 'fc_joining_documents_user_uploads', NULL, 'docs_done', 1, 'Upload duly filled and signed joining documents.', 'bi-folder-fill', @now, @now);
SET @s4 = LAST_INSERT_ID();
INSERT INTO fc_form_fields (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, section_heading, css_class, file_max_kb, file_extensions, is_active, created_at, updated_at) VALUES
(@s4,'doc_family_details','Family Details Form (Form-3) of Rule 54(12) of CCS (Pensions) Rules, 1972','file','fc_joining_documents_user_uploads','admin_family_details_form','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,1,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_close_relation','Declaration of Close Relation','file','fc_joining_documents_user_uploads','admin_close_relation_declaration','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,2,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_dowry_decl','Dowry Declaration','file','fc_joining_documents_user_uploads','admin_dowry_declaration','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,3,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_marital_status','Marital Status Declaration','file','fc_joining_documents_user_uploads','admin_marital_status','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,4,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_home_town','Home Town Declaration','file','fc_joining_documents_user_uploads','admin_home_town_declaration','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,5,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_immovable_prop','6-A: Statement of Immovable Property on first appointment','file','fc_joining_documents_user_uploads','admin_property_immovable','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,6,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_movable_prop','6-B: Statement of Movable Property on first appointment','file','fc_joining_documents_user_uploads','admin_property_movable','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,7,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_debts_liabilities','6-C: Statement of Debts and Other Liabilities on first appointment','file','fc_joining_documents_user_uploads','admin_property_liabilities','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,8,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_surety_bond_ias','Surety Bond for IAS or IPS or IFoS (whichever is applicable)','file','fc_joining_documents_user_uploads','admin_bond_ias_ips_ifos','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,9,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_surety_bond_others','Surety Bond for other services (other than All India Services) (if applicable)','file','fc_joining_documents_user_uploads','admin_bond_other_services','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,10,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_oath_affirmation','Form of OATH / Affirmation','file','fc_joining_documents_user_uploads','admin_oath_affirmation','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,11,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_assumption_charge','Certificate of Assumption of Charge','file','fc_joining_documents_user_uploads','admin_certificate_of_charge','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,12,'Administration Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_group_insurance','Nomination for Central Government Employees Group Insurance Scheme, 1980 (Form-7 / Form-8)','file','fc_joining_documents_user_uploads','accounts_nomination_form','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,13,'Accounts Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_nps_subscription','National Pension System (NPS) Subscription Registration Form','file','fc_joining_documents_user_uploads','accounts_nps_registration','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,14,'Accounts Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s4,'doc_employee_info_sheet','Employee Information Sheet Form','file','fc_joining_documents_user_uploads','accounts_employee_info_sheet','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,15,'Accounts Section Related Documents','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 6 · STEP 4 — Bank Details  (flat; bank fields + 3 doc uploads)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Bank Details', 'fc99-bank', 4, 'new_registration_bank_details_masters', NULL, 'bank_done', 1, 'Bank account details and supporting documents.', 'bi-bank', @now, @now);
SET @s5 = LAST_INSERT_ID();
INSERT INTO fc_form_fields (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, section_heading, css_class, file_max_kb, file_extensions, is_active, created_at, updated_at) VALUES
(@s5,'account_holder_name','Account Holder Name','text','new_registration_bank_details_masters','account_holder_name','required|string|max:200',1,1,'Bank Details','col-md-6',NULL,NULL,1,@now,@now),
(@s5,'account_no','Account Number','text','new_registration_bank_details_masters','account_no','required|string|max:50',1,2,'Bank Details','col-md-6',NULL,NULL,1,@now,@now),
(@s5,'ifsc_code','Bank IFSC Code','text','new_registration_bank_details_masters','ifsc_code','required|string|max:20|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',1,3,'Bank Details','col-md-6',NULL,NULL,1,@now,@now),
(@s5,'bank_name','Bank Name','text','new_registration_bank_details_masters','bank_name','required|string|max:200',1,4,'Bank Details','col-md-6',NULL,NULL,1,@now,@now),
(@s5,'doc_aadhar','Aadhar Card (Self Attached)','file','new_registration_bank_details_masters','doc_aadhar_path','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,5,'Account Section Related Documents','col-md-4',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s5,'doc_pan','PAN Card (Self Attached)','file','new_registration_bank_details_masters','doc_pan_path','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,6,'Account Section Related Documents','col-md-4',5120,'jpeg,jpg,png,pdf',1,@now,@now),
(@s5,'doc_cancel_cheque','Cancel Cheque','file','new_registration_bank_details_masters','doc_cancel_cheque_path','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,7,'Account Section Related Documents','col-md-4',5120,'jpeg,jpg,png,pdf',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 7 · STEP 5 — Health Risk Factors  (flat -> seconds)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Health Risk Factors', 'fc99-health', 5, 'student_master_seconds', 'health_completed', 'health_done', 1, 'Tick the vulnerabilities / risk factors that apply in your case.', 'bi-heart-pulse-fill', @now, @now);
SET @s6 = LAST_INSERT_ID();
INSERT INTO fc_form_fields (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, options_json, section_heading, css_class, is_active, created_at, updated_at) VALUES
(@s6,'health_asthma','Asthma','radio','student_master_seconds','health_asthma','required|in:No,Yes',1,1,'[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_lung_disease','Chronic Lung Disease','radio','student_master_seconds','health_lung_disease','required|in:None,COPD,IPF,Cystic Fibrosis',1,2,'[{"value":"COPD","label":"Chronic Pulmonary Lung disease (Emphysema / Chronic Bronchitis)"},{"value":"IPF","label":"Idiopathic Pulmonary Fibrosis"},{"value":"Cystic Fibrosis","label":"Cystic Fibrosis"},{"value":"None","label":"None"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_kidney_disease','Kidney Disease being treated with dialysis','radio','student_master_seconds','health_kidney_disease','required|in:No,Yes',1,3,'[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_diabetes','Diabetes – Type I / II / Gestational with diabetes related problems','radio','student_master_seconds','health_diabetes','required|in:No,Yes',1,4,'[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_blood_disorder','Blood Disorders','radio','student_master_seconds','health_blood_disorder','required|in:None,Sickle Cell,Thalassemia,Anaemia',1,5,'[{"value":"Sickle Cell","label":"Sickle Cell Disease"},{"value":"Thalassemia","label":"Thalassemia"},{"value":"Anaemia","label":"Anaemia"},{"value":"None","label":"None"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_immunocompromised','Immunocompromised Status','radio','student_master_seconds','health_immunocompromised','required|in:None,Cancer+Chemotherapy,Bone Marrow/Organ Transplant,HIV low CD4,Prolonged Corticosteroids',1,6,'[{"value":"Cancer+Chemotherapy","label":"Cancer and undergoing treatment incl. chemotherapy / radiotherapy"},{"value":"Bone Marrow/Organ Transplant","label":"Bone Marrow / Organ transplantation"},{"value":"HIV low CD4","label":"HIV with low CD4 Cell Count / Not on treatment"},{"value":"Prolonged Corticosteroids","label":"Prolonged use of corticosteroids / immune suppressing medications"},{"value":"None","label":"None"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_liver_disease','Liver Disease – Chronic Liver Disease / Liver Cirrhosis','radio','student_master_seconds','health_liver_disease','required|in:No,Yes',1,7,'[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_cardiac_condition','Serious Cardiac Conditions','radio','student_master_seconds','health_cardiac_condition','required|in:None,Coronary Artery Disease,Coronary Heart Disease,Cardiomyopathies,Pulmonary Hypertension',1,8,'[{"value":"Coronary Artery Disease","label":"Coronary Artery Disease"},{"value":"Coronary Heart Disease","label":"Coronary Heart Disease"},{"value":"Cardiomyopathies","label":"Coronary Cardiac Disease / Cardiomyopathies"},{"value":"Pulmonary Hypertension","label":"Pulmonary Hypertension"},{"value":"None","label":"None"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_pregnant_lactating','Pregnant or Lactating Mother','radio','student_master_seconds','health_pregnant_lactating','required|in:No,Yes',1,9,'[{"value":"No","label":"No"},{"value":"Yes","label":"Yes"}]','Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now),
(@s6,'health_additional_info','Any Other Additional Information','textarea','student_master_seconds','health_additional_info','nullable|string|max:1000',0,10,NULL,'Health Vulnerabilities Risk Factors','col-md-12',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 8 · STEP 6 — Special Assistant  (flat -> iosr)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Special Assistant', 'fc99-special', 6, 'student_iosr_reasonable_adjust_masters', 'special_completed', 'special_done', 1, 'Physical impairment information and reasonable adjustments.', 'bi-universal-access', @now, @now);
SET @s7 = LAST_INSERT_ID();
INSERT INTO fc_form_fields (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, section_heading, css_class, file_max_kb, file_extensions, is_active, created_at, updated_at) VALUES
(@s7,'physical_impairment_info','Relevant information relating to physical impairment','textarea','student_iosr_reasonable_adjust_masters','physical_impairment_info','nullable|string|max:1000',0,1,'Physical Impairment','col-md-12',NULL,NULL,1,@now,@now),
(@s7,'adjustment_required','Reasonable Adjustments','textarea','student_iosr_reasonable_adjust_masters','adjustment_required','nullable|string|max:1000',0,2,'Reasonable adjustments requested for by the Officer Trainee','col-md-12',NULL,NULL,1,@now,@now),
(@s7,'adjustment_type','Document Title','text','student_iosr_reasonable_adjust_masters','adjustment_type','nullable|string|max:200',0,3,'Documents attached','col-md-6',NULL,NULL,1,@now,@now),
(@s7,'doc_path','Document Uploaded','file','student_iosr_reasonable_adjust_masters','doc_path','nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',0,4,'Documents attached','col-md-6',5120,'jpeg,jpg,png,pdf',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 9 · STEP 7 — Vision Statement  (flat -> firsts)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_steps (form_id, step_name, step_slug, step_number, target_table, completion_column, tracker_column, is_active, description, icon, created_at, updated_at)
VALUES (@form_id, 'Vision Statement', 'fc99-vision', 7, 'student_master_firsts', 'vision_completed', 'vision_done', 1, 'Statement of Vision and Aspirations of an Officer Trainee.', 'bi-lightbulb-fill', @now, @now);
SET @s8 = LAST_INSERT_ID();
INSERT INTO fc_form_fields (step_id, field_name, label, field_type, target_table, target_column, validation_rules, is_required, display_order, placeholder, section_heading, css_class, is_active, created_at, updated_at) VALUES
(@s8,'vision_statement','Statement of Vision and Aspirations','textarea','student_master_firsts','vision_statement','required|string|min:50|max:1500',1,1,'Write about 100 words on your vision and aspiration as a civil servant.','Vision Statement','col-md-12',1,@now,@now);


-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 10 · VERIFY
-- ─────────────────────────────────────────────────────────────────────────────

SELECT s.step_number, s.step_name, s.step_slug,
       COUNT(DISTINCT f.id)  AS flat_fields,
       COUNT(DISTINCT g.id)  AS `groups`,
       COUNT(DISTINCT gf.id) AS group_fields
FROM fc_form_steps s
LEFT JOIN fc_form_fields f        ON f.step_id = s.id
LEFT JOIN fc_form_field_groups g  ON g.step_id = s.id
LEFT JOIN fc_form_group_fields gf ON gf.group_id = g.id
WHERE s.form_id = @form_id
GROUP BY s.id, s.step_number, s.step_name, s.step_slug
ORDER BY s.step_number;
