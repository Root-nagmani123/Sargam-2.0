-- =============================================================================
-- ROLLBACK for build_fc99_fresh_form.sql
-- Deletes the fresh "99th Foundation Course Registration" form AND drops the
-- 29 columns that build script added — restoring the DB to its previous state.
--
-- SAFE TO RE-RUN: deletes only if present, drops a column only if it exists.
--
-- ⚠️  WARNING: dropping the columns permanently deletes any data stored in them.
--    Only run this if the fresh form was never filled in (or you don't need
--    that data). If unsure, comment out SECTION 2 and drop only the form.
-- =============================================================================

SET @form_slug = 'fc-99th-fresh';          -- must match the build script's @form_slug
SET SQL_SAFE_UPDATES = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 1 · Delete the form and all its children (group fields → … → form)
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
-- SECTION 2 · Drop the columns the build script added (only if they exist)
-- ─────────────────────────────────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS fc99_drop_col;
DELIMITER $$
CREATE PROCEDURE fc99_drop_col(IN p_tbl VARCHAR(64), IN p_col VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_tbl AND COLUMN_NAME = p_col
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_tbl, '` DROP COLUMN `', p_col, '`');
        PREPARE st FROM @ddl; EXECUTE st; DEALLOCATE PREPARE st;
    END IF;
END$$
DELIMITER ;

CALL fc99_drop_col('student_master_firsts','background');

CALL fc99_drop_col('student_master_seconds','birth_area_type');
CALL fc99_drop_col('student_master_seconds','guardian_or_spouse');
CALL fc99_drop_col('student_master_seconds','guardian_first_name');
CALL fc99_drop_col('student_master_seconds','guardian_middle_name');
CALL fc99_drop_col('student_master_seconds','guardian_last_name');
CALL fc99_drop_col('student_master_seconds','guardian_contact_no');
CALL fc99_drop_col('student_master_seconds','guardian_email');
CALL fc99_drop_col('student_master_seconds','perm_district');
CALL fc99_drop_col('student_master_seconds','perm_city_name');
CALL fc99_drop_col('student_master_seconds','pres_district');
CALL fc99_drop_col('student_master_seconds','pres_city_name');
CALL fc99_drop_col('student_master_seconds','highest_stream_id');
CALL fc99_drop_col('student_master_seconds','matric_state_id');
CALL fc99_drop_col('student_master_seconds','matric_district');
CALL fc99_drop_col('student_master_seconds','matric_city');
CALL fc99_drop_col('student_master_seconds','matric_city_name');
CALL fc99_drop_col('student_master_seconds','cse_attempts');
CALL fc99_drop_col('student_master_seconds','previous_service_id');

CALL fc99_drop_col('student_master_qualification_details','institution_type');
CALL fc99_drop_col('student_master_qualification_details','to_year');
CALL fc99_drop_col('student_master_qualification_details','division');

CALL fc99_drop_col('student_cloth_size_master_details','track_suit_size');

CALL fc99_drop_col('student_master_spouse_masters','spouse_in_cse');

CALL fc99_drop_col('new_registration_bank_details_masters','doc_aadhar_path');
CALL fc99_drop_col('new_registration_bank_details_masters','doc_pan_path');
CALL fc99_drop_col('new_registration_bank_details_masters','doc_cancel_cheque_path');

DROP PROCEDURE IF EXISTS fc99_drop_col;

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 3 · VERIFY — should return 0 rows (form gone) and 0 leftover columns
-- ─────────────────────────────────────────────────────────────────────────────

SELECT COUNT(*) AS form_rows_left FROM fc_forms WHERE form_slug = @form_slug;

SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
      (TABLE_NAME = 'student_master_firsts'                 AND COLUMN_NAME = 'background') OR
      (TABLE_NAME = 'student_master_seconds'                AND COLUMN_NAME IN ('birth_area_type','guardian_or_spouse','guardian_first_name','guardian_middle_name','guardian_last_name','guardian_contact_no','guardian_email','perm_district','perm_city_name','pres_district','pres_city_name','highest_stream_id','matric_state_id','matric_district','matric_city','matric_city_name','cse_attempts','previous_service_id')) OR
      (TABLE_NAME = 'student_master_qualification_details'   AND COLUMN_NAME IN ('institution_type','to_year','division')) OR
      (TABLE_NAME = 'student_cloth_size_master_details'      AND COLUMN_NAME = 'track_suit_size') OR
      (TABLE_NAME = 'student_master_spouse_masters'          AND COLUMN_NAME = 'spouse_in_cse') OR
      (TABLE_NAME = 'new_registration_bank_details_masters'  AND COLUMN_NAME IN ('doc_aadhar_path','doc_pan_path','doc_cancel_cheque_path'))
  );
