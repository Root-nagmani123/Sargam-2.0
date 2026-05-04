-- FC activity department ↔ staff (user_credentials.pk). Run on MySQL / MariaDB.
-- UI still shows user_name + name; DB stores pk for a stable key.

CREATE TABLE IF NOT EXISTS fc_activity_department_user (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fc_activity_department_id BIGINT UNSIGNED NOT NULL,
    user_credentials_pk BIGINT UNSIGNED NOT NULL COMMENT 'user_credentials.pk',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY fc_act_dept_user_dept_uc_unique (fc_activity_department_id, user_credentials_pk),
    KEY fc_act_dept_user_uc_pk_idx (user_credentials_pk),
    CONSTRAINT fc_activity_department_user_dept_fk
        FOREIGN KEY (fc_activity_department_id) REFERENCES fc_activity_department (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fc_act_dept_user_uc_fk
        FOREIGN KEY (user_credentials_pk) REFERENCES user_credentials (pk)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
