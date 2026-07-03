-- FC pre-medical history (FC registration Step 3 tab + post-arrival medical / consultation flag).
-- Run in MySQL/MariaDB. Creates `fc_pre_history` only if it does not exist.

SET @db := DATABASE();

SET @exists := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = @db AND table_name = 'fc_pre_history'
);

SET @sql := IF(@exists = 0,
    'CREATE TABLE `fc_pre_history` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `userid` varchar(50) NOT NULL,
        `allergy_illness` text NULL,
        `prolonged_medication` text NULL,
        `hospital_history` text NULL,
        `altitude_illness` text NULL,
        `additional_info` text NULL,
        `doc_path` varchar(255) NULL,
        `course` varchar(120) NULL,
        `status` tinyint NOT NULL DEFAULT 1,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `fc_pre_history_userid_course_unique` (`userid`, `course`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
