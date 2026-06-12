-- =============================================================================
-- Fix: Step 3 fields exist in DB but admin edit shows groups with count 0
--
-- Common cause: fc_form_group_fields.group_id points to groups on another step
-- (e.g. template step) while the editor shows groups on fc-101 / copy step.
--
-- SET @step_id      to your "Other Details" step (e.g. 99 on production).
-- SET @source_step_id to a step that ALREADY has the field set you want copied.
--   • The ORIGINAL fc-registration step3 (id=3) is the legacy layout — it also
--     carries fields newer forms dropped (e.g. qualifications.degree_name,
--     employment.is_current). Use it only if you want that original layout.
--   • For new-style forms (fc100 / fc101 / *-copy), prefer one of THOSE step-3
--     ids as the master so you don't re-inject legacy fields.
--   Run query B first to see what each candidate source actually holds.
-- Safe to re-run.
-- =============================================================================

SET @step_id        = 99;   -- <<< CHANGE: Other Details step id (target) from fc_form_steps
SET @source_step_id = 3;    -- <<< master step that holds the correct group fields

-- Multi-table UPDATE/DELETE below have no key in their WHERE; Workbench / some
-- phpMyAdmin setups block those under safe-update mode. Disable for this session.
SET @old_safe_updates = @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1) DIAGNOSE — run this first
-- ─────────────────────────────────────────────────────────────────────────────

SELECT 'A) Groups on THIS step (what editor shows)' AS report;
SELECT fg.id AS group_id, fg.group_name, fg.group_label, COUNT(gf.id) AS field_count
FROM fc_form_field_groups fg
LEFT JOIN fc_form_group_fields gf ON gf.group_id = fg.id
WHERE fg.step_id = @step_id
GROUP BY fg.id, fg.group_name, fg.group_label
ORDER BY fg.display_order;

SELECT 'B) Fields sitting on OTHER steps but same group_name (orphaned)' AS report;
SELECT src_g.step_id AS wrong_step_id, src_g.id AS wrong_group_id,
       src_g.group_name, COUNT(gf.id) AS orphan_fields
FROM fc_form_group_fields gf
INNER JOIN fc_form_field_groups src_g ON src_g.id = gf.group_id
WHERE src_g.step_id <> @step_id
  AND src_g.group_name IN (
      SELECT group_name FROM fc_form_field_groups WHERE step_id = @step_id
  )
GROUP BY src_g.step_id, src_g.id, src_g.group_name;

SELECT 'C) Duplicate group_name on same step (empty copy + filled copy)' AS report;
SELECT group_name, COUNT(*) AS group_rows,
       SUM((SELECT COUNT(*) FROM fc_form_group_fields gf WHERE gf.group_id = fg.id)) AS total_fields
FROM fc_form_field_groups fg
WHERE step_id = @step_id
GROUP BY group_name
HAVING COUNT(*) > 1;


-- ─────────────────────────────────────────────────────────────────────────────
-- 2) FIX A — Merge duplicate groups on same step FIRST (move fields → lowest id)
--    Run when report C shows group_rows > 1.
--    (Done before the copy below so each group_name has exactly one target group.)
-- ─────────────────────────────────────────────────────────────────────────────

UPDATE fc_form_group_fields gf
INNER JOIN fc_form_field_groups g ON g.id = gf.group_id AND g.step_id = @step_id
INNER JOIN (
    SELECT group_name, MIN(id) AS keep_id
    FROM fc_form_field_groups
    WHERE step_id = @step_id
    GROUP BY group_name
) canon ON canon.group_name = g.group_name AND g.id <> canon.keep_id
LEFT JOIN fc_form_group_fields clash
    ON clash.group_id = canon.keep_id AND clash.field_name = gf.field_name
SET gf.group_id = canon.keep_id
WHERE clash.id IS NULL;

-- Remove duplicate empty groups (no fields left on them)
DELETE g FROM fc_form_field_groups g
WHERE g.step_id = @step_id
  AND NOT EXISTS (SELECT 1 FROM fc_form_group_fields gf WHERE gf.group_id = g.id)
  AND EXISTS (
      SELECT 1 FROM fc_form_field_groups g2
      WHERE g2.step_id = @step_id
        AND g2.group_name = g.group_name
        AND g2.id <> g.id
  );


-- ─────────────────────────────────────────────────────────────────────────────
-- 3) FIX B — Copy missing fields from the MASTER source step (by group_name)
--    Run when groups on @step_id are missing fields.
--
--    Pulls from a single, known-good @source_step_id (not "any other step"),
--    so a field defined on several forms can never be inserted more than once.
--    Targets the canonical (lowest-id) group per group_name after the merge above.
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO fc_form_group_fields (
    group_id, field_name, label, field_type, target_column,
    validation_rules, is_required, display_order, placeholder,
    options_json, lookup_table, lookup_value_column, lookup_label_column,
    css_class, is_active, created_at, updated_at
)
SELECT
    tgt.keep_id,
    sgf.field_name, sgf.label, sgf.field_type, sgf.target_column,
    sgf.validation_rules, sgf.is_required, sgf.display_order, sgf.placeholder,
    sgf.options_json, sgf.lookup_table, sgf.lookup_value_column, sgf.lookup_label_column,
    sgf.css_class, sgf.is_active, NOW(), NOW()
FROM fc_form_group_fields sgf
INNER JOIN fc_form_field_groups src_g
    ON src_g.id = sgf.group_id AND src_g.step_id = @source_step_id
INNER JOIN (
    -- one target group per group_name on this step (lowest id wins)
    SELECT group_name, MIN(id) AS keep_id
    FROM fc_form_field_groups
    WHERE step_id = @step_id
    GROUP BY group_name
) tgt ON tgt.group_name = src_g.group_name
LEFT JOIN fc_form_group_fields existing
    ON existing.group_id = tgt.keep_id AND existing.field_name = sgf.field_name
WHERE existing.id IS NULL;


-- ─────────────────────────────────────────────────────────────────────────────
-- 4) VERIFY — each group on @step_id should show field_count > 0
-- ─────────────────────────────────────────────────────────────────────────────

SELECT 'AFTER FIX' AS report;
SELECT fg.id AS group_id, fg.group_name, fg.group_label, COUNT(gf.id) AS field_count
FROM fc_form_field_groups fg
LEFT JOIN fc_form_group_fields gf ON gf.group_id = fg.id
WHERE fg.step_id = @step_id
GROUP BY fg.id, fg.group_name, fg.group_label
ORDER BY fg.display_order;

SELECT CONCAT('TOTAL fields on step ', @step_id, ': ',
       (SELECT COUNT(gf.id)
        FROM fc_form_group_fields gf
        INNER JOIN fc_form_field_groups fg ON fg.id = gf.group_id
        WHERE fg.step_id = @step_id)) AS summary;

-- Restore previous safe-update setting
SET SQL_SAFE_UPDATES = @old_safe_updates;
