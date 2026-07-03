-- Migrate existing fc_activity_department_user.username → user_credentials_pk
-- Run once if the table was created with a `username` column (user_credentials.user_name).
-- If index names differ on your server, adjust DROP lines (see information_schema.statistics).

ALTER TABLE fc_activity_department_user
    ADD COLUMN user_credentials_pk BIGINT UNSIGNED NULL AFTER fc_activity_department_id;

UPDATE fc_activity_department_user AS du
INNER JOIN user_credentials AS uc
    ON uc.user_name COLLATE utf8mb4_unicode_ci = du.username COLLATE utf8mb4_unicode_ci
SET du.user_credentials_pk = uc.pk
WHERE du.username IS NOT NULL AND TRIM(du.username) != '';

DELETE FROM fc_activity_department_user WHERE user_credentials_pk IS NULL;

ALTER TABLE fc_activity_department_user
    DROP INDEX fc_activity_department_user_unique,
    DROP INDEX fc_activity_department_user_username_idx,
    DROP COLUMN username;

ALTER TABLE fc_activity_department_user
    MODIFY user_credentials_pk BIGINT UNSIGNED NOT NULL;

ALTER TABLE fc_activity_department_user
    ADD UNIQUE KEY fc_act_dept_user_dept_uc_unique (fc_activity_department_id, user_credentials_pk),
    ADD KEY fc_act_dept_user_uc_pk_idx (user_credentials_pk);

ALTER TABLE fc_activity_department_user
    ADD CONSTRAINT fc_act_dept_user_uc_fk
        FOREIGN KEY (user_credentials_pk) REFERENCES user_credentials (pk)
        ON DELETE CASCADE ON UPDATE CASCADE;
