-- =============================================================================
-- Rollback: remove FC Template form only (slug: fc_template).
-- Does NOT touch fc-99th-fresh or any other form.
-- =============================================================================

SET @form_slug = 'fc_template';
SET SQL_SAFE_UPDATES = 0;

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

SELECT CONCAT('Removed fc_template form id=', IFNULL(@old_id, 'none')) AS result;
