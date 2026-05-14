-- =============================================================================
-- FC Post-Arrival Activities — departments + staff access + activity master
-- MySQL / MariaDB: safe to run in ONE batch (mysql client, phpMyAdmin, DBeaver).
-- Re-runnable: skips CREATE/ALTER that already exist.
-- Requires: table fc_activity_master already exists.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Departments
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fc_activity_department (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL COMMENT 'Slug: medical, it, admin, …',
  name VARCHAR(150) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1 COMMENT '1=active',
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fc_act_dept_code (code),
  KEY idx_fc_act_dept_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Staff → department (NULL department_id = coordinator / full access)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fc_staff_activity_access (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_name VARCHAR(100) NOT NULL COMMENT 'Matches user_credentials.user_name',
  department_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fc_staff_act_user (user_name),
  KEY idx_fc_staff_act_dept (department_id),
  CONSTRAINT fk_fc_staff_act_dept FOREIGN KEY (department_id)
    REFERENCES fc_activity_department (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3) Seed departments (idempotent)
-- -----------------------------------------------------------------------------
INSERT INTO fc_activity_department (code, name, sort_order, status, created_at, updated_at)
SELECT v.code, v.name, v.sort_order, 1, NOW(), NOW()
FROM (
  SELECT 'admin' AS code, 'Admin' AS name, 10 AS sort_order
  UNION ALL SELECT 'security', 'Security', 20
  UNION ALL SELECT 'it', 'IT', 30
  UNION ALL SELECT 'training', 'Training', 40
  UNION ALL SELECT 'medical', 'Medical', 50
  UNION ALL SELECT 'shop', 'Shop', 60
) AS v
WHERE NOT EXISTS (SELECT 1 FROM fc_activity_department d WHERE d.code = v.code);

-- -----------------------------------------------------------------------------
-- 4) Extend fc_activity_master — only if column/index missing
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

-- department_id
SET @sql := (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'fc_activity_master' AND COLUMN_NAME = 'department_id') = 0,
    'ALTER TABLE fc_activity_master ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER id',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sort_order
SET @sql := (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'fc_activity_master' AND COLUMN_NAME = 'sort_order') = 0,
    'ALTER TABLE fc_activity_master ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER menun',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- entry_policy
SET @sql := (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'fc_activity_master' AND COLUMN_NAME = 'entry_policy') = 0,
    'ALTER TABLE fc_activity_master ADD COLUMN entry_policy ENUM(''unique'',''upsert'') NOT NULL DEFAULT ''unique'' AFTER status',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_fc_am_dept_status
SET @sql := (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'fc_activity_master' AND INDEX_NAME = 'idx_fc_am_dept_status') = 0,
    'ALTER TABLE fc_activity_master ADD INDEX idx_fc_am_dept_status (department_id, status)',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_fc_am_status_ccode
SET @sql := (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'fc_activity_master' AND INDEX_NAME = 'idx_fc_am_status_ccode') = 0,
    'ALTER TABLE fc_activity_master ADD INDEX idx_fc_am_status_ccode (status, ccode)',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional FK (uncomment after data is valid — may fail on re-run if exists):
-- ALTER TABLE fc_activity_master
--   ADD CONSTRAINT fk_fc_am_department FOREIGN KEY (department_id)
--   REFERENCES fc_activity_department(id) ON DELETE RESTRICT;

-- -----------------------------------------------------------------------------
-- 5) Back-fill department_id from known menuid → department code
-- -----------------------------------------------------------------------------
UPDATE fc_activity_master am
INNER JOIN fc_activity_department d ON d.code = (
  CASE TRIM(am.menuid)
    WHEN 'joined' THEN 'admin'
    WHEN 'idcard' THEN 'security'
    WHEN 'biometric' THEN 'it'
    WHEN 'trgind' THEN 'training'
    WHEN 'height' THEN 'medical'
    WHEN 'weight' THEN 'medical'
    WHEN 'spo2' THEN 'medical'
    WHEN 'pulse' THEN 'medical'
    WHEN 'bp' THEN 'medical'
    WHEN 'preremarks' THEN 'medical'
    WHEN 'vialtube' THEN 'medical'
    WHEN 'bloodsample' THEN 'medical'
    WHEN 'souvenir' THEN 'shop'
  END
)
SET am.department_id = d.id
WHERE am.department_id IS NULL;

UPDATE fc_activity_master am
SET am.department_id = (SELECT id FROM fc_activity_department WHERE code = 'admin' LIMIT 1)
WHERE am.department_id IS NULL;

-- -----------------------------------------------------------------------------
-- 6) Upsert policies + sort_order (idempotent updates)
-- -----------------------------------------------------------------------------
UPDATE fc_activity_master SET entry_policy = 'upsert'
WHERE menuid IN ('height','weight','spo2','pulse','bp','preremarks','vialtube','bloodsample');

UPDATE fc_activity_master SET sort_order =
  CASE TRIM(menuid)
    WHEN 'joined' THEN 10
    WHEN 'idcard' THEN 10
    WHEN 'biometric' THEN 10
    WHEN 'trgind' THEN 10
    WHEN 'souvenir' THEN 10
    WHEN 'height' THEN 20
    WHEN 'weight' THEN 30
    WHEN 'pulse' THEN 40
    WHEN 'bp' THEN 50
    WHEN 'spo2' THEN 60
    WHEN 'preremarks' THEN 70
    WHEN 'vialtube' THEN 80
    WHEN 'bloodsample' THEN 90
    ELSE 100
  END;

-- -----------------------------------------------------------------------------
-- Examples (uncomment and edit usernames):
-- Coordinator:
-- INSERT INTO fc_staff_activity_access (user_name, department_id, created_at, updated_at)
-- VALUES ('your_login', NULL, NOW(), NOW())
-- ON DUPLICATE KEY UPDATE department_id = NULL, updated_at = NOW();
--
-- Medical desk only:
-- INSERT INTO fc_staff_activity_access (user_name, department_id, created_at, updated_at)
-- SELECT 'medical_login', id, NOW(), NOW() FROM fc_activity_department WHERE code = 'medical' LIMIT 1
-- ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), updated_at = NOW();
