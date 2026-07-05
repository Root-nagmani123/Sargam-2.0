-- FC OT details: doctor-marked "consultation required" after reviewing pre-medical / reports.
-- Run on MySQL / MariaDB. Safe to run once; DROP COLUMN first only if you need to re-apply.

ALTER TABLE fc_ot_details
    ADD COLUMN consultation_required TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = doctor marked consultation needed for this trainee+course row'
        AFTER status,
    ADD COLUMN consultation_required_at TIMESTAMP NULL DEFAULT NULL
        COMMENT 'When consultation_required was last set to 1'
        AFTER consultation_required,
    ADD COLUMN consultation_marked_by VARCHAR(64) NULL DEFAULT NULL
        COMMENT 'Admin username who last toggled consultation_required'
        AFTER consultation_required_at;

-- Optional: list/filter “needs consultation” on medical screen by course
CREATE INDEX fc_ot_details_consultation_course_idx
    ON fc_ot_details (consultation_required, course);
