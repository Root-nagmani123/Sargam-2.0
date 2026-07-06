-- MySQL dump 10.13  Distrib 8.0.39-30, for Linux (x86_64)
--
-- Host: localhost    Database: sargam
-- ------------------------------------------------------
-- Server version	8.0.39-30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!50717 SELECT COUNT(*) INTO @rocksdb_has_p_s_session_variables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'performance_schema' AND TABLE_NAME = 'session_variables' */;
/*!50717 SET @rocksdb_get_is_supported = IF (@rocksdb_has_p_s_session_variables, 'SELECT COUNT(*) INTO @rocksdb_is_supported FROM performance_schema.session_variables WHERE VARIABLE_NAME=\'rocksdb_bulk_load\'', 'SELECT 0') */;
/*!50717 PREPARE s FROM @rocksdb_get_is_supported */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;
/*!50717 SET @rocksdb_enable_bulk_load = IF (@rocksdb_is_supported, 'SET SESSION rocksdb_bulk_load = 1', 'SET @rocksdb_dummy_bulk_load = 0') */;
/*!50717 PREPARE s FROM @rocksdb_enable_bulk_load */;
/*!50717 EXECUTE s */;
/*!50717 DEALLOCATE PREPARE s */;

--
-- Table structure for table `fc_joining_documents_user_uploads`
--

DROP TABLE IF EXISTS `fc_joining_documents_user_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fc_joining_documents_user_uploads` (
  `pk` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `admin_family_details_form` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_close_relation_declaration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_dowry_declaration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_marital_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_home_town_declaration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_property_immovable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_property_movable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_property_liabilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_bond_ias_ips_ifos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_bond_other_services` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_other_documents` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_oath_affirmation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_certificate_of_charge` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_police_verification` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accounts_nomination_form` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accounts_nps_registration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accounts_employee_info_sheet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hardep` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_nps_subscription` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fc_joining_documents_user_uploads`
--

LOCK TABLES `fc_joining_documents_user_uploads` WRITE;
/*!40000 ALTER TABLE `fc_joining_documents_user_uploads` DISABLE KEYS */;
INSERT INTO `fc_joining_documents_user_uploads` VALUES (1,1,'fc_joining_documents/1/bNqp40pqSknrDHqhJHDy9jBhXhlnYIzkv16gN5Fe.docx','fc_joining_documents/1/cAbekYZFhmdxfydgUD3T7fb4cqXqBlfPE79Az89C.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'fc_joining_documents/1/56pz8sL6IFzmIR5FTdQ0T30Jijb1gmeZlunWjFS8.pdf',NULL,'fc_joining_documents/1/d6q1Kbj6e6ZX4s4TNQ8bcwLDPDrPz1ci8yQvPDwU.pdf','fc_joining_documents/1/j3YVTXTwVai9zIyBLJJviCjrTAdY1b3zdKTFJKUZ.pdf',NULL,NULL,'fc_joining_documents/1/DzYnM6WqzNAsOzmlgZMuVQk1Fz5IUloLGwZlEMBt.pdf','fc_joining_documents/1/g4JPpLQa7K900Oa2yGBD5JhlXrueyFGZKkz3ADnc.pdf',NULL,'2026-04-15 08:22:16','2026-04-15 08:22:16',NULL,NULL),(2,40761,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'good',NULL,'2025-07-10 10:48:33',NULL,NULL),(3,40149,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-10 11:02:54',NULL,NULL),(4,40582,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Demo',NULL,'2025-07-10 11:53:27',NULL,NULL),(5,40142,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Demo text',NULL,'2025-07-10 11:45:08',NULL,NULL),(6,40229,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Demo text 1',NULL,'2025-07-10 11:45:43',NULL,NULL),(7,31,'fc_joining_documents/31/Xvt3ZffvxS2BTjvXtIXcLg0OC1VYyytjTrew47S6.pdf','fc_joining_documents/31/CdKhBXv2BN5DflbFI6iw8pNktnvYmwfT4buEfZRh.pdf','fc_joining_documents/31/ui3ZY1ADIHQ7cYNzNj11iMxXAl7egVnEaFb5BDm7.pdf','fc_joining_documents/31/c26DUK9DEF6zkn2p5TI4RAAv8YWSvGqqmdMKvaVh.pdf','fc_joining_documents/31/o30RlKPFZ3QPoqUAXkjPjj4NrT9UJwaqttAIA9xi.pdf','fc_joining_documents/31/IiRRj2pxmQl5fF2sLLTSfHV5iv5zw1xHoh6hYzWR.pdf','fc_joining_documents/31/8aaTwyHhd9wwXDBWBYUplwSm09MZMHMssHRk2zlq.pdf','fc_joining_documents/31/LtAlc5JMsU5oxnFUGB9Z5D7xK4P2ubXm7MfYgiP9.pdf','fc_joining_documents/31/ODyeXAgw0nPbYbEtH4X5Y4HPZ5yzx0Zin1Et3KPe.pdf','fc_joining_documents/31/KH49ys9LNVnL5Qndp86wCJK3HsKVVycpZqmoq4Sk.pdf','fc_joining_documents/31/FcPHnvwBYb9hRjfRsLlPUXQx8XhUImbxfKJ42rCf.pdf','fc_joining_documents/31/ZR13ceHGSTJlvOllAurkXJBf6E3ii9tdY4g5lZWy.pdf','fc_joining_documents/31/TRXx9F0id4u7Q9QT4Y0buLFalDJmYvHXdvTNTKn8.pdf',NULL,'fc_joining_documents/31/ZTrmjkhRNby2rDwGn0sEGK5t4HfuO1GlTpPz7UD8.pdf','fc_joining_documents/31/QjTMXS40iqteXVWDWGOi8T22iWdlrGgX3Qd5YZ0q.pdf','fc_joining_documents/31/anSrXcXbbJNnuo3T1yxumsh0CzXn3Fm6gomx89DQ.pdf','updated staus','2025-07-14 09:56:49','2025-07-14 10:01:27',NULL,NULL),(8,20,NULL,'fc_joining_documents/20/Q5KwbkmNvQpR3OcxWsXH15s8HyimTn75vX6V4x1C.pdf',NULL,NULL,'fc_joining_documents/20/wEXgI8whUyaxhDvNs3gMFCbzGQzjhVIR9w0SGG44.pdf','fc_joining_documents/20/FHOufMstFNGxHtXdbb7gju49aHKgQ78smyGO2eeB.pdf',NULL,'fc_joining_documents/20/PGIcUuO622g76cOgRaFenGqOwf1TSmqwhF2zdPct.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-15 10:45:01','2025-07-15 10:45:01',NULL,NULL),(9,10,'fc_joining_documents/10/lqrRxkYk9eCj4emGEfJ62gzFtEUyhtHLBo46ch9B.pdf','fc_joining_documents/10/TAehrAiqMJGKoBRy545FzffEIDWcsGWnptS6LF0S.pdf','fc_joining_documents/10/SdZyZYJ2qgISlAtfiZyEnfeAvzS9BB3Dzct21Cm2.pdf','fc_joining_documents/10/9X0pSHvmkR4sQbb0zI1YXTZco92rytQjcFG6gu8x.pdf','fc_joining_documents/10/rf8gz3zEe5DtswqZgNjPIV4Y29mCmnlAkZb9KS4q.pdf','fc_joining_documents/10/otUvDa1gZypwcLULw1TjqFm9i31JirKvOKkFLpNz.pdf','fc_joining_documents/10/TLuANqPJwHaKFpFVXvFw9C7YqyeXSVcXU21scVhj.pdf','fc_joining_documents/10/RroGvA6wLUFvLD8dF1N4SDqaOorU0DK72jL26ai8.pdf','fc_joining_documents/10/kfNvWDgEAfOdnLur045O596dGEDWP1pNHF2YSr84.pdf','fc_joining_documents/10/IhEr7NKJ0MF7mKLOxxPdMvRWFMOGP7JCWSFnq48p.pdf',NULL,'fc_joining_documents/10/MGo4X30Rb5DnnJiIMaBoR598vM6048SiwEtVXMWA.pdf','fc_joining_documents/10/0JffL4hb8wxtJfqO2iDxHct3Di0rRADL3l1k6jft.pdf',NULL,'fc_joining_documents/10/dFmMe75HNPMtipTtMVDZ45p0zdiVPuRBeyxPw7SC.pdf','fc_joining_documents/10/grSqDrWHkRVNBlPkGOtFNRV04c06b7VdFwB0audz.pdf','fc_joining_documents/10/J5DhQ4kwfTEpKCxHv9hUWdX5B6IdDhYlbG5cMByt.pdf',NULL,'2025-07-23 10:20:49','2025-07-23 10:20:49',NULL,NULL),(10,2,'fc_joining_documents/2/cGRVPyo44zquaJceWqtA8nvflGFfjbzxij77FJOY.pdf','fc_joining_documents/2/vKrZnXIajGpAty87svpakOGpdbxrTxly2dcLLUSJ.pdf','fc_joining_documents/2/403UWRXcWhi1Jg9khZTkvPEZvGNMb1oDyql3xfkW.pdf',NULL,'fc_joining_documents/2/HnWnCfxOb3JSFA0nJciUWuo3ud01QN2bupx2mIc5.pdf','fc_joining_documents/2/Mh0zjMR2BnGfD2dXlS84Tkc3iXD3K1GPr8cBuaB4.pdf','fc_joining_documents/2/wIEP8VkMXTt7NOyab8XRnhJO2tFvTbqS3vzSOoE1.pdf','fc_joining_documents/2/r0hrGFHHy0EmnIMyimPKxyHQa2F6ocjs9yTbzbjB.pdf','fc_joining_documents/2/eZnAo4IQJNXTmmWY7AgWfOc7ddb5sB6C3UbInI0r.pdf','fc_joining_documents/2/WCtGAcJNPW9CBXE6cFpF1BTaZnDDhep8UHSYBESS.pdf',NULL,'fc_joining_documents/2/pejWS76kCdPmuv3fvI9LYVEXS8YOke50pB78vLUo.pdf','fc_joining_documents/2/1ARQLrp7bnHO6d8NPMM1cvu985qofbtl4NOhEhq1.pdf',NULL,'fc_joining_documents/2/8slMYnJn9IeouPtf7Lvd5dAT5kC3Gfzd82ltZf1x.pdf','fc_joining_documents/2/G6bN9RoAEwxme8dbCAoNMUjaISkXEUpeNKOy90yu.pdf','fc_joining_documents/2/boX5FVyErcDbF3M5rwzgUkbk6rb4wNrSEejtZ23M.pdf','ok','2025-08-03 13:41:53','2025-08-03 13:41:53',NULL,NULL),(11,3183,NULL,NULL,NULL,NULL,'fc_joining_documents/3183/yNqkpdPHyS2qGAKTesJTT5oc5yveES1l3UpCGMEd.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-16 10:13:01','2026-04-16 10:13:01',NULL,NULL),(12,41102,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-15 07:03:58',NULL,NULL),(13,41090,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-15 07:51:44',NULL,NULL),(14,41103,NULL,'uploads/41103/documents/doc_close_relation_1781511970.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-15 08:26:10','2026-06-15 08:26:17',NULL,NULL),(15,3201,'uploads/41103/documents/doc_family_details_1781640737.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uploads/41103/documents/doc_group_insurance_1781642628.pdf',NULL,NULL,NULL,'2026-06-16 20:10:49','2026-06-16 20:43:48',NULL,NULL),(16,41104,'uploads/41104/documents/doc_family_details_1781855217.pdf',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-19 07:46:57','2026-06-19 07:47:32','uploads/41104/documents/hardep_1781855248.png',NULL);
/*!40000 ALTER TABLE `fc_joining_documents_user_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_iosr_reasonable_adjust_masters`
--

DROP TABLE IF EXISTS `student_iosr_reasonable_adjust_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_iosr_reasonable_adjust_masters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `adjustment_required` tinyint NOT NULL DEFAULT '0',
  `adjustment_type` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `physical_impairment_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_completed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_iosr_reasonable_adjust_masters_user_id_unique` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_iosr_reasonable_adjust_masters`
--

LOCK TABLES `student_iosr_reasonable_adjust_masters` WRITE;
/*!40000 ALTER TABLE `student_iosr_reasonable_adjust_masters` DISABLE KEYS */;
INSERT INTO `student_iosr_reasonable_adjust_masters` VALUES (1,3189,0,'No Document Title','uploads/demouser3/doc_path_1779445750.png','2026-05-22 10:29:10','2026-05-24 11:11:09','NO physical Impairment Impairmentsss',1),(2,41049,1,'ssssssssssssssssssssssssssssss','uploads/41049/doc_path_1779954081.png','2026-05-28 07:41:21','2026-05-28 07:41:21','ssssssssssssssssssssss',1),(3,3198,0,NULL,NULL,'2026-05-29 11:21:21','2026-05-29 11:21:21',NULL,1),(4,41102,0,'llllllllllllllllllllllllllllllllll','uploads/41102/doc_path_1781465349.png','2026-06-14 19:29:09','2026-06-15 07:26:01','jjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj',1),(5,41090,0,'cdcdcdc','uploads/41090/doc_path_1781510006.png','2026-06-15 07:53:26','2026-06-15 07:53:26','cdcdcd',1),(6,41103,0,'dddddddddd','uploads/41103/doc_path_1781512029.png','2026-06-15 08:27:09','2026-06-15 08:27:09','ddddddddddddddddddd',1),(7,41104,0,'gegeg','uploads/41104/doc_path_1781855313.png','2026-06-19 07:48:33','2026-06-19 07:48:33','ggergeg',1);
/*!40000 ALTER TABLE `student_iosr_reasonable_adjust_masters` ENABLE KEYS */;
UNLOCK TABLES;
/*!50112 SET @disable_bulk_load = IF (@is_rocksdb_supported, 'SET SESSION rocksdb_bulk_load = @old_rocksdb_bulk_load', 'SET @dummy_rocksdb_bulk_load = 0') */;
/*!50112 PREPARE s FROM @disable_bulk_load */;
/*!50112 EXECUTE s */;
/*!50112 DEALLOCATE PREPARE s */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-19 13:42:12
