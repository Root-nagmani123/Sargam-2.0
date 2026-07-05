-- Fix Special Assistance step: adjustment_required is TINYINT (0/1), not free text.
-- Run once on environments that already imported 99th_fc_registration_form.sql with textarea mapping.

UPDATE fc_form_fields
SET
    field_type = 'radio',
    label = 'Reasonable Adjustments Requested',
    validation_rules = 'nullable|in:0,1',
    options_json = '[{"value":"1","label":"Yes"},{"value":"0","label":"No"}]',
    placeholder = NULL,
    help_text = NULL,
    css_class = 'col-md-12'
WHERE field_name = 'adjustment_required'
  AND target_column = 'adjustment_required'
  AND target_table = 'student_iosr_reasonable_adjust_masters';

UPDATE fc_form_fields
SET
    field_type = 'textarea',
    label = 'Details of Adjustments Required',
    validation_rules = 'nullable|string|max:300',
    placeholder = 'Describe the adjustments required (if Yes above)',
    css_class = 'col-md-12'
WHERE field_name = 'adjustment_type'
  AND target_column = 'adjustment_type'
  AND target_table = 'student_iosr_reasonable_adjust_masters'
  AND field_type = 'text';
