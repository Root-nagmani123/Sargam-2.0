-- Fix for discipline_memo_status table
-- Error was: COLLATE was used without a collation name (e.g. utf8mb4_unicode_ci)

-- If the table already exists and you need to recreate it, run:
-- DROP TABLE IF EXISTS `discipline_memo_status`;

CREATE TABLE `discipline_memo_status` (
  `pk` int NOT NULL AUTO_INCREMENT,
  `course_master_pk` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `discipline_master_pk` int DEFAULT NULL,
  `mark_deduction_submit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_master_pk` int DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `final_mark_deduction` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conclusion_type_pk` int DEFAULT NULL,
  `conclusion_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1- submit, 2- send_memo',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
