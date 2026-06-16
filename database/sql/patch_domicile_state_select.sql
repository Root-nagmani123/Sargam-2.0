-- Patch live forms: Domicile State → state_master select; Domicile District cascades from it.
-- Safe to re-run (idempotent column add). Run in phpMyAdmin / mysql.

SET @now = NOW();

DROP PROCEDURE IF EXISTS fc_patch_add_col;
DELIMITER $$
CREATE PROCEDURE fc_patch_add_col(IN p_tbl VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
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

CALL fc_patch_add_col('student_master_seconds', 'domicile_state_id', '`domicile_state_id` BIGINT UNSIGNED NULL');
DROP PROCEDURE IF EXISTS fc_patch_add_col;

UPDATE fc_form_fields
SET
    field_name            = 'domicile_state_id',
    field_type            = 'select',
    target_column         = 'domicile_state_id',
    validation_rules      = 'required|exists:state_master,pk',
    lookup_table          = 'state_master',
    lookup_value_column   = 'pk',
    lookup_label_column   = 'state_name',
    updated_at            = @now
WHERE field_name = 'domicile_state'
   OR target_column = 'domicile_state';

SELECT field_name, field_type, target_column, lookup_table
FROM fc_form_fields
WHERE field_name IN ('domicile_state_id', 'domicile_district')
ORDER BY display_order;
