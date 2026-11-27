-- Guest TA Claim module: schema for MariaDB 10.6+ (same as the Laravel migration).
-- Run either this file OR `php artisan migrate`, not both.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS gtc_claims (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  claim_no              VARCHAR(32)  NOT NULL,
  course_id             VARCHAR(64)  NOT NULL,
  course_name           VARCHAR(255) NOT NULL,
  coordinator_user_id   BIGINT UNSIGNED NULL,
  faculty_id            VARCHAR(64)  NOT NULL,
  faculty_name          VARCHAR(255) NOT NULL,
  faculty_designation   VARCHAR(255) NULL,
  faculty_organisation  VARCHAR(255) NULL,
  faculty_category      VARCHAR(64)  NOT NULL,
  period_from           DATE NOT NULL,
  period_to             DATE NOT NULL,
  honorarium_rate       DECIMAL(12,2) NOT NULL DEFAULT 0,
  travel_mode           VARCHAR(32)  NOT NULL,
  travel_from           VARCHAR(120) NULL,
  travel_fare           DECIMAL(12,2) NOT NULL DEFAULT 0,
  local_conveyance      DECIMAL(12,2) NOT NULL DEFAULT 0,
  da_days               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  da_rate               DECIMAL(12,2) NOT NULL DEFAULT 0,
  sessions_taken        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  honorarium_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
  da_amount             DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  remarks               TEXT NULL,
  status                VARCHAR(24) NOT NULL DEFAULT 'draft',
  created_by            BIGINT UNSIGNED NOT NULL,
  forwarded_at          TIMESTAMP NULL,
  approved_by           BIGINT UNSIGNED NULL,
  approved_at           TIMESTAMP NULL,
  paid_amount           DECIMAL(12,2) NULL,
  paid_remark           VARCHAR(1000) NULL,
  paid_by               BIGINT UNSIGNED NULL,
  paid_at               TIMESTAMP NULL,
  report_generated_at   TIMESTAMP NULL,
  created_at            TIMESTAMP NULL,
  updated_at            TIMESTAMP NULL,
  UNIQUE KEY gtc_claims_claim_no_unique (claim_no),
  UNIQUE KEY gtc_claims_course_faculty_period_unique (course_id, faculty_id, period_from, period_to),
  KEY gtc_claims_status_index (status),
  KEY gtc_claims_coordinator_user_id_index (coordinator_user_id),
  KEY gtc_claims_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gtc_claim_sessions (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  claim_id           BIGINT UNSIGNED NOT NULL,
  source_session_id  VARCHAR(100) NULL,
  session_date       DATE NOT NULL,
  start_time         TIME NOT NULL,
  end_time           TIME NOT NULL,
  topic              VARCHAR(255) NOT NULL,
  venue              VARCHAR(120) NULL,
  status             ENUM('taken','cancelled') NOT NULL DEFAULT 'taken',
  is_edited          TINYINT(1) NOT NULL DEFAULT 0,
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  CONSTRAINT gtc_claim_sessions_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES gtc_claims (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gtc_claim_histories (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  claim_id     BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NOT NULL,
  user_name    VARCHAR(255) NOT NULL,
  action       VARCHAR(255) NOT NULL,
  from_status  VARCHAR(24) NULL,
  to_status    VARCHAR(24) NOT NULL,
  remark       VARCHAR(2000) NULL,
  created_at   TIMESTAMP NULL,
  CONSTRAINT gtc_claim_histories_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES gtc_claims (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gtc_claim_attachments (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  claim_id       BIGINT UNSIGNED NOT NULL,
  original_name  VARCHAR(255) NOT NULL,
  path           VARCHAR(255) NOT NULL,
  disk           VARCHAR(32)  NOT NULL,
  mime           VARCHAR(100) NULL,
  size           BIGINT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by    BIGINT UNSIGNED NOT NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT gtc_claim_attachments_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES gtc_claims (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gtc_user_permissions (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  permission  VARCHAR(32) NOT NULL,
  granted_by  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  UNIQUE KEY gtc_user_permissions_user_id_permission_unique (user_id, permission),
  KEY gtc_user_permissions_permission_index (permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gtc_claim_sequences (
  year         SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number  INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: give the administrator (user id 1) the permission to assign permissions.
-- INSERT IGNORE INTO gtc_user_permissions (user_id, permission, created_at, updated_at) VALUES (1, 'perm.manage', NOW(), NOW());
