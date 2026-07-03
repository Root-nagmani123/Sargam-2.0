-- =============================================================================
-- Repair: Step 3 (Other Details) — missing fc_form_group_fields
--
-- Use when form builder shows 11 groups but every tab count is 0.
-- Safe to re-run (skips fields that already exist).
--
-- RUN ON PRODUCTION (phpMyAdmin or mysql CLI):
--   1) Fix form-template first
--   2) Then copy fields into fc-101 (or any course form)
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- PART A · Insert group fields into ONE form (existing groups, step_number = 3)
-- Change @form_slug before running.
-- ─────────────────────────────────────────────────────────────────────────────

SET @form_slug = 'form-template';   -- e.g. form-template | fc-101 | fc-registration-copy

-- Detect the Other Details step by slug (…step3), NOT step_number: some forms
-- (e.g. phase2) have an unrelated flat step at step_number = 3.
SELECT id INTO @form_id FROM fc_forms WHERE form_slug = @form_slug LIMIT 1;
SELECT id INTO @s3 FROM fc_form_steps
WHERE form_id = @form_id AND step_slug LIKE '%step3'
ORDER BY step_number LIMIT 1;

-- Resolve group IDs (groups must already exist from your template SQL / UI copy)
SELECT id INTO @g1  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'language_knowledge'    LIMIT 1;
SELECT id INTO @g2  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'languages'             LIMIT 1;
SELECT id INTO @g3  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'qualifications'        LIMIT 1;
SELECT id INTO @g4  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'higher_education'     LIMIT 1;
SELECT id INTO @g5  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'employment'           LIMIT 1;
SELECT id INTO @g6  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'distinctions'          LIMIT 1;
SELECT id INTO @g7  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'hobbies'                 LIMIT 1;
SELECT id INTO @g8  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'sports_played'         LIMIT 1;
SELECT id INTO @g9  FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'spouse'                 LIMIT 1;
SELECT id INTO @g10 FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'dress_code'             LIMIT 1;
SELECT id INTO @g11 FROM fc_form_field_groups WHERE step_id = @s3 AND group_name = 'pre_medical_history'    LIMIT 1;

-- GROUP 1: Language Knowledge
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g1,'mother_tongue','Mother Tongue','text','mother_tongue','nullable|string|max:100',0,1,NULL,NULL,NULL,NULL,NULL,'col-md-6',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='mother_tongue');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g1,'medium_12th','Medium of Study in Class 12','text','medium_12th','nullable|string|max:100',0,2,'e.g. Hindi, English',NULL,NULL,NULL,NULL,'col-md-6',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='medium_12th');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g1,'medium_graduation','Medium of Study in Graduation','text','medium_graduation','nullable|string|max:100',0,3,'e.g. Hindi, English',NULL,NULL,NULL,NULL,'col-md-6',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='medium_graduation');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g1,'medium_civil_service','Medium in Civil Service Exam','text','medium_civil_service','nullable|string|max:100',0,4,'e.g. Hindi, English',NULL,NULL,NULL,NULL,'col-md-6',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='medium_civil_service');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g1,'viva_language','Language of CSE Viva / Interview','text','viva_language','nullable|string|max:100',0,5,'e.g. Hindi, English',NULL,NULL,NULL,NULL,'col-md-6',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='viva_language');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, css_class, is_active, created_at, updated_at)
SELECT @g1,'passed_matric_hindi','Passed Matriculation with Hindi?','select','passed_matric_hindi','nullable|in:Yes,No',0,6,NULL,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-4',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='passed_matric_hindi');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, css_class, is_active, created_at, updated_at)
SELECT @g1,'selected_cse_hindi','Selected in CSE 2023 with Hindi?','select','selected_cse_hindi','nullable|in:Yes,No',0,7,NULL,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-4',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='selected_cse_hindi');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, placeholder, options_json, css_class, is_active, created_at, updated_at)
SELECT @g1,'hindi_mother_tongue','Is Hindi your Mother Tongue?','select','hindi_mother_tongue','nullable|in:Yes,No',0,8,NULL,'[{"value":"Yes","label":"Yes"},{"value":"No","label":"No"}]','col-md-4',1,NOW(),NOW() FROM DUAL WHERE @g1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g1 AND field_name='hindi_mother_tongue');

-- GROUP 2: Languages Known
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, lookup_table, lookup_value_column, lookup_label_column, css_class, is_active, created_at, updated_at)
SELECT @g2,'language_id','Language','select','language_id','required|exists:language_master,pk',1,1,'language_master','pk','language_name','col-md-3',1,NOW(),NOW() FROM DUAL WHERE @g2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g2 AND field_name='language_id');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at)
SELECT @g2,'can_read','Can Read','checkbox','can_read','nullable',0,2,'col-md-2',1,NOW(),NOW() FROM DUAL WHERE @g2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g2 AND field_name='can_read');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at)
SELECT @g2,'can_write','Can Write','checkbox','can_write','nullable',0,3,'col-md-2',1,NOW(),NOW() FROM DUAL WHERE @g2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g2 AND field_name='can_write');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, css_class, is_active, created_at, updated_at)
SELECT @g2,'can_speak','Can Speak','checkbox','can_speak','nullable',0,4,'col-md-2',1,NOW(),NOW() FROM DUAL WHERE @g2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g2 AND field_name='can_speak');
INSERT INTO fc_form_group_fields (group_id, field_name, label, field_type, target_column, validation_rules, is_required, display_order, options_json, css_class, is_active, created_at, updated_at)
SELECT @g2,'proficiency','Proficiency','select','proficiency','nullable|in:Basic,Intermediate,Advanced,Native',0,5,'[{"value":"Basic","label":"Basic"},{"value":"Intermediate","label":"Intermediate"},{"value":"Advanced","label":"Advanced"},{"value":"Native","label":"Native"}]','col-md-3',1,NOW(),NOW() FROM DUAL WHERE @g2 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields WHERE group_id=@g2 AND field_name='proficiency');

-- GROUP 3–11: use PART B copy if source form already has fields, or run full template SQL SECTION 10 once.


-- ─────────────────────────────────────────────────────────────────────────────
-- PART B · Copy group fields from source form → target form (step 3 only)
-- Run AFTER form-template has fields. Change slugs below.
-- ─────────────────────────────────────────────────────────────────────────────

SET @source_form_slug = 'form-template';
SET @target_form_slug   = 'fc-101';

INSERT INTO fc_form_group_fields (
    group_id, field_name, label, field_type, target_column,
    validation_rules, is_required, display_order, placeholder,
    options_json, lookup_table, lookup_value_column, lookup_label_column,
    css_class, is_active, created_at, updated_at
)
SELECT
    tg.id,
    sgf.field_name, sgf.label, sgf.field_type, sgf.target_column,
    sgf.validation_rules, sgf.is_required, sgf.display_order, sgf.placeholder,
    sgf.options_json, sgf.lookup_table, sgf.lookup_value_column, sgf.lookup_label_column,
    sgf.css_class, sgf.is_active, NOW(), NOW()
FROM fc_form_group_fields sgf
INNER JOIN fc_form_field_groups sg ON sg.id = sgf.group_id
INNER JOIN fc_form_steps ss ON ss.id = sg.step_id AND ss.step_slug LIKE '%step3'
INNER JOIN fc_forms sf ON sf.id = ss.form_id AND sf.form_slug = @source_form_slug
INNER JOIN fc_forms tf ON tf.form_slug = @target_form_slug
INNER JOIN fc_form_steps ts ON ts.form_id = tf.id AND ts.step_slug LIKE '%step3'
INNER JOIN fc_form_field_groups tg ON tg.step_id = ts.id AND tg.group_name = sg.group_name
LEFT JOIN fc_form_group_fields existing
    ON existing.group_id = tg.id AND existing.field_name = sgf.field_name
WHERE existing.id IS NULL;


-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFY
-- ─────────────────────────────────────────────────────────────────────────────

SELECT f.form_slug, s.step_name, s.id AS step_id,
       COUNT(DISTINCT fg.id) AS groups,
       COUNT(gf.id) AS group_fields
FROM fc_forms f
JOIN fc_form_steps s ON s.form_id = f.id AND s.step_slug LIKE '%step3'
LEFT JOIN fc_form_field_groups fg ON fg.step_id = s.id
LEFT JOIN fc_form_group_fields gf ON gf.group_id = fg.id
WHERE f.form_slug IN (@form_slug, @source_form_slug, @target_form_slug)
GROUP BY f.form_slug, s.step_name, s.id
ORDER BY f.form_slug;
