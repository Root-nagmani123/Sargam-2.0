-- Add 'repeat' to fc_activity_master.entry_policy (MySQL) for multiple readings per vital.
-- Run if you apply schema changes manually instead of Laravel migration 2026_05_05_120000_...

ALTER TABLE fc_activity_master
  MODIFY COLUMN entry_policy ENUM('unique','upsert','repeat') NOT NULL DEFAULT 'unique';
