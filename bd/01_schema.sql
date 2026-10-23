-- -----------------------------------------------------------------------------
-- Tenders module - database structure (MySQL 8 / MariaDB 10.6+)
-- Generated from backend/database/migrations with: php bin/console schema:dump
--
-- Table / column names are in English. Equivalence with the specification:
--   tenders          -> ofertas
--   activities       -> actividades
--   tender_documents -> ofertas_documentos
-- See README.md for the field-by-field mapping.
-- -----------------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tender_sequences`;
DROP TABLE IF EXISTS `tender_documents`;
DROP TABLE IF EXISTS `tenders`;
DROP TABLE IF EXISTS `activities`;

CREATE TABLE `activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `segment_code` int unsigned NOT NULL,
  `segment` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_code` int unsigned NOT NULL,
  `family` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_code` int unsigned NOT NULL,
  `class_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_code` int unsigned NOT NULL,
  `product` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activities_product_code` (`product_code`),
  KEY `idx_activities_product` (`product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tenders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `consecutive` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget` decimal(15,2) NOT NULL,
  `activity_id` int unsigned NOT NULL,
  `start_date` date NOT NULL,
  `start_time` time NOT NULL,
  `closing_date` date NOT NULL,
  `closing_time` time NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `starts_at` datetime GENERATED ALWAYS AS (timestamp(`start_date`,`start_time`)) STORED,
  `closes_at` datetime GENERATED ALWAYS AS (timestamp(`closing_date`,`closing_time`)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tenders_consecutive` (`consecutive`),
  KEY `idx_tenders_subject` (`subject`),
  KEY `idx_tenders_created_at` (`created_at`),
  KEY `idx_tenders_starts_at` (`starts_at`),
  KEY `idx_tenders_closes_at` (`closes_at`),
  KEY `fk_tenders_activity` (`activity_id`),
  CONSTRAINT `fk_tenders_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`),
  CONSTRAINT `chk_tenders_budget` CHECK ((`budget` > 0)),
  CONSTRAINT `chk_tenders_currency` CHECK ((`currency` in (_utf8mb4'COP',_utf8mb4'USD',_utf8mb4'EUR')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tender_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tender_id` int unsigned NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tender_documents_tender` (`tender_id`),
  CONSTRAINT `fk_tender_documents_tender` FOREIGN KEY (`tender_id`) REFERENCES `tenders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tender_sequences` (
  `year` smallint unsigned NOT NULL,
  `last_number` int unsigned NOT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
