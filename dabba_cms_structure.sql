/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: dabba_cms
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(120) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'note',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `occurred_at` datetime DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `activity_logs_subject_type_subject_id_occurred_at_index` (`subject_type`,`subject_id`,`occurred_at`),
  KEY `activity_logs_subject_type_subject_id_created_at_index` (`subject_type`,`subject_id`,`created_at`),
  KEY `activity_logs_type_index` (`type`),
  KEY `activity_logs_updated_by_user_id_index` (`updated_by_user_id`),
  KEY `activity_logs_customer_notes_sort` (`subject_type`,`subject_id`,`is_pinned`,`created_at`,`id`),
  KEY `activity_logs_customer_notes_author` (`subject_type`,`subject_id`,`created_by_user_id`,`deleted_at`),
  KEY `idx_activity_logs_subject_type_id_type` (`subject_type`,`subject_id`,`type`,`deleted_at`,`is_pinned`,`created_at`),
  KEY `idx_activity_subject` (`subject_type`,`subject_id`,`deleted_at`),
  KEY `idx_voided_status` (`voided_at`),
  KEY `idx_logs_subject` (`subject_type`,`subject_id`,`type`,`deleted_at`),
  KEY `idx_activity_logs_purchasing` (`subject_type`,`subject_id`,`type`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1437 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line1` varchar(191) NOT NULL,
  `line2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `region` varchar(191) DEFAULT NULL,
  `postcode` varchar(32) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_country_id_index` (`country_id`),
  KEY `addresses_is_active_index` (`is_active`),
  KEY `addresses_postcode_index` (`postcode`),
  KEY `addresses_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `addresses_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `arrival_packages`
--

DROP TABLE IF EXISTS `arrival_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `arrival_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `package_ref` varchar(120) DEFAULT NULL,
  `tracking_number` varchar(191) DEFAULT NULL,
  `carrier` varchar(120) DEFAULT NULL,
  `supplier_label_ref` varchar(191) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'unprocessed',
  `is_working_session` tinyint(1) NOT NULL DEFAULT 0,
  `arrived_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `closed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arrival_packages_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `arrival_packages_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `arrival_packages_status_arrived_at_index` (`status`,`arrived_at`),
  KEY `arrival_packages_package_ref_index` (`package_ref`),
  KEY `arrival_packages_tracking_number_index` (`tracking_number`),
  KEY `arrival_packages_carrier_index` (`carrier`),
  KEY `arrival_packages_supplier_label_ref_index` (`supplier_label_ref`),
  KEY `arrival_packages_status_index` (`status`),
  KEY `arrival_packages_arrived_at_index` (`arrived_at`),
  KEY `arrival_packages_working_session_idx` (`created_by_user_id`,`is_working_session`,`closed_at`),
  CONSTRAINT `arrival_packages_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `arrival_packages_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `iso2` char(2) DEFAULT NULL,
  `iso3` char(3) DEFAULT NULL,
  `phone_code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_name_unique` (`name`),
  UNIQUE KEY `countries_iso2_unique` (`iso2`),
  UNIQUE KEY `countries_iso3_unique` (`iso3`),
  KEY `countries_is_active_index` (`is_active`),
  KEY `countries_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `countries_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_applications`
--

DROP TABLE IF EXISTS `credit_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_credit_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `invoice_version_id` bigint(20) unsigned DEFAULT NULL,
  `amount_applied` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `applied_at` timestamp NULL DEFAULT NULL,
  `applied_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_applications_applied_by_user_id_foreign` (`applied_by_user_id`),
  KEY `credit_applications_order_id_index` (`order_id`),
  KEY `credit_applications_customer_credit_id_index` (`customer_credit_id`),
  KEY `credit_applications_invoice_version_id_index` (`invoice_version_id`),
  CONSTRAINT `credit_applications_applied_by_user_id_foreign` FOREIGN KEY (`applied_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `credit_applications_customer_credit_id_foreign` FOREIGN KEY (`customer_credit_id`) REFERENCES `customer_credits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `credit_applications_invoice_version_id_foreign` FOREIGN KEY (`invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `credit_applications_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_note_items`
--

DROP TABLE IF EXISTS `credit_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `line_type` varchar(30) NOT NULL DEFAULT 'other',
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_items_credit_note_id_sort_order_index` (`credit_note_id`,`sort_order`),
  KEY `credit_note_items_order_item_id_index` (`order_item_id`),
  CONSTRAINT `credit_note_items_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `credit_note_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `credit_note_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'issued',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `reason` varchar(255) DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_notes_credit_note_number_unique` (`credit_note_number`),
  KEY `credit_notes_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `credit_notes_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `credit_notes_customer_id_status_index` (`customer_id`,`status`),
  KEY `credit_notes_order_id_index` (`order_id`),
  CONSTRAINT `credit_notes_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `credit_notes_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `credit_notes_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_addresses`
--

DROP TABLE IF EXISTS `customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `address_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `label` varchar(64) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_addresses_customer_id_address_id_unique` (`customer_id`,`address_id`),
  KEY `customer_addresses_customer_id_is_primary_index` (`customer_id`,`is_primary`),
  KEY `customer_addresses_address_id_index` (`address_id`),
  KEY `customer_addresses_is_active_index` (`is_active`),
  KEY `customer_addresses_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customer_addresses_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1170 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_credits`
--

DROP TABLE IF EXISTS `customer_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_credits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(30) NOT NULL DEFAULT 'credit_note',
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_invoice_version_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `remaining_amount` decimal(12,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_credit_source` (`source_type`,`source_id`),
  KEY `customer_credits_customer_id_status_index` (`customer_id`,`status`),
  KEY `customer_credits_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `customer_credits_order_id_index` (`order_id`),
  KEY `customer_credits_source_invoice_version_id_index` (`source_invoice_version_id`),
  KEY `customer_credits_created_by_user_id_index` (`created_by_user_id`),
  CONSTRAINT `customer_credits_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_credits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `customer_credits_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_credits_source_invoice_version_id_foreign` FOREIGN KEY (`source_invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_emails`
--

DROP TABLE IF EXISTS `customer_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `email_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_emails_customer_id_email_id_unique` (`customer_id`,`email_id`),
  KEY `customer_emails_customer_id_is_primary_index` (`customer_id`,`is_primary`),
  KEY `customer_emails_email_id_index` (`email_id`),
  KEY `customer_emails_is_active_index` (`is_active`),
  KEY `customer_emails_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customer_emails_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1058 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_ledger_allocations`
--

DROP TABLE IF EXISTS `customer_ledger_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_ledger_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_ledger_entry_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `allocation_type` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cla_order_type_idx` (`order_id`,`allocation_type`),
  KEY `cla_entry_idx` (`customer_ledger_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_ledger_entries`
--

DROP TABLE IF EXISTS `customer_ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `payment_type_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(190) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_invoice_version_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'recorded',
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cle_customer_type_idx` (`customer_id`,`type`),
  KEY `cle_customer_status_idx` (`customer_id`,`status`),
  KEY `cle_payment_type_idx` (`payment_type_id`),
  KEY `cle_source_idx` (`source_type`,`source_id`),
  KEY `idx_customer_ledger_customer` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=694 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_phones`
--

DROP TABLE IF EXISTS `customer_phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_phones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `phone_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_phones_customer_id_phone_id_unique` (`customer_id`,`phone_id`),
  KEY `customer_phones_customer_id_is_primary_index` (`customer_id`,`is_primary`),
  KEY `customer_phones_phone_id_index` (`phone_id`),
  KEY `customer_phones_is_active_index` (`is_active`),
  KEY `customer_phones_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customer_phones_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1052 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_release_notification_items`
--

DROP TABLE IF EXISTS `customer_release_notification_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_release_notification_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_release_notification_id` bigint(20) unsigned NOT NULL,
  `purchase_arrival_assignment_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crn_items_notification_assignment_unique` (`customer_release_notification_id`,`purchase_arrival_assignment_id`),
  KEY `crn_items_assignment_idx` (`purchase_arrival_assignment_id`),
  CONSTRAINT `crn_items_assignment_fk` FOREIGN KEY (`purchase_arrival_assignment_id`) REFERENCES `purchase_arrival_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crn_items_notification_fk` FOREIGN KEY (`customer_release_notification_id`) REFERENCES `customer_release_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1755 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_release_notifications`
--

DROP TABLE IF EXISTS `customer_release_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_release_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `to_email` varchar(191) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template_key` varchar(50) NOT NULL DEFAULT 'collection_ready',
  `body_html` longtext DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crn_order_sent_idx` (`order_id`,`sent_at`),
  KEY `crn_order_failed_idx` (`order_id`,`failed_at`),
  KEY `crn_template_idx` (`template_key`),
  KEY `crn_sent_at_idx` (`sent_at`),
  KEY `crn_failed_at_idx` (`failed_at`),
  KEY `crn_created_by_idx` (`created_by_user_id`),
  KEY `crn_updated_by_idx` (`updated_by_user_id`),
  CONSTRAINT `crn_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=637 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_wallet_ledger`
--

DROP TABLE IF EXISTS `customer_wallet_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_wallet_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_wallet_ledger_order_id_foreign` (`order_id`),
  KEY `customer_wallet_ledger_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customer_wallet_ledger_customer_id_created_at_index` (`customer_id`,`created_at`),
  KEY `customer_wallet_ledger_type_index` (`type`),
  CONSTRAINT `customer_wallet_ledger_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_wallet_ledger_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_wallet_ledger_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `company_name` varchar(191) DEFAULT NULL,
  `customer_type` enum('individual','company') NOT NULL DEFAULT 'individual',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `reference` varchar(191) DEFAULT NULL,
  `dabba_fee_level` enum('global','vip_min_percent','vip_percent_only') NOT NULL DEFAULT 'global',
  `dabba_fee_rate` decimal(6,2) DEFAULT NULL,
  `dabba_fee_min` decimal(10,2) DEFAULT NULL,
  `dabba_fee_is_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_customer_type_index` (`customer_type`),
  KEY `customers_is_active_index` (`is_active`),
  KEY `customers_last_name_index` (`last_name`),
  KEY `customers_company_name_index` (`company_name`),
  KEY `customers_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `customers_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `draft_order_items`
--

DROP TABLE IF EXISTS `draft_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `draft_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_order_id` bigint(20) unsigned NOT NULL,
  `source_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_id` bigint(20) unsigned NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `url` text DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_retailer_delivery_fee` decimal(10,2) DEFAULT NULL,
  `item_delivery_fee` decimal(10,2) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `needs_attention_at` timestamp NULL DEFAULT NULL,
  `needs_attention_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `needs_attention_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `draft_order_items_retailer_id_foreign` (`retailer_id`),
  KEY `draft_order_items_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `draft_order_items_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `draft_order_items_draft_order_id_retailer_id_index` (`draft_order_id`,`retailer_id`),
  KEY `draft_order_items_sort_order_index` (`sort_order`),
  KEY `draft_order_items_draft_order_id_sort_order_index` (`draft_order_id`,`sort_order`),
  KEY `draft_order_items_sku_index` (`sku`),
  KEY `doi_source_order_item_id_idx` (`source_order_item_id`),
  KEY `doi_draft_order_id_index` (`draft_order_id`),
  KEY `doi_retailer_id_index` (`retailer_id`),
  KEY `doi_url_index` (`url`(255)),
  KEY `idx_doi_url_prefix` (`url`(191)),
  KEY `idx_doi_draft_retailer` (`draft_order_id`,`retailer_id`,`id`),
  KEY `draft_order_items_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  CONSTRAINT `draft_order_items_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2422 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`dabba_user`@`localhost`*/ /*!50003 TRIGGER trim_product_code_before_insert
BEFORE INSERT ON draft_order_items
FOR EACH ROW
BEGIN
    SET NEW.product_code = LEFT(NEW.product_code, 50);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`dabba_user`@`localhost`*/ /*!50003 TRIGGER trim_product_code_before_update
BEFORE UPDATE ON draft_order_items
FOR EACH ROW
BEGIN
    SET NEW.product_code = LEFT(NEW.product_code, 50);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `draft_order_retailers`
--

DROP TABLE IF EXISTS `draft_order_retailers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `draft_order_retailers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_order_id` bigint(20) unsigned NOT NULL,
  `retailer_id` bigint(20) unsigned NOT NULL,
  `retailer_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_fee_total` decimal(10,2) DEFAULT 0.00,
  `dabba_fee_rate` decimal(8,2) DEFAULT 20.00,
  `dabba_fee_min` decimal(8,2) DEFAULT 10.00,
  `dabba_fee_is_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `dabba_fee_reason` varchar(255) DEFAULT NULL,
  `dabba_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `draft_order_retailers_draft_order_id_retailer_id_unique` (`draft_order_id`,`retailer_id`),
  KEY `draft_order_retailers_retailer_id_foreign` (`retailer_id`),
  KEY `draft_order_retailers_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `draft_order_retailers_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `dor_draft_order_id_index` (`draft_order_id`),
  KEY `dor_retailer_id_index` (`retailer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1994 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `draft_orders`
--

DROP TABLE IF EXISTS `draft_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `draft_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_request_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `parent_order_id` bigint(20) unsigned DEFAULT NULL,
  `draft_number` varchar(50) DEFAULT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'normal',
  `state` varchar(30) NOT NULL DEFAULT 'draft',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `purchase_mode` varchar(40) NOT NULL DEFAULT 'standard',
  `home_delivery_requested` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `dabba_fee_level` varchar(255) DEFAULT NULL,
  `dabba_fee_rate` decimal(6,4) DEFAULT NULL,
  `dabba_fee_min` decimal(10,2) DEFAULT NULL,
  `fee_mode` varchar(20) NOT NULL DEFAULT 'standard',
  `items_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `finalized_order_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `draft_orders_order_request_id_unique` (`order_request_id`),
  KEY `draft_orders_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `draft_orders_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `draft_orders_draft_number_index` (`draft_number`),
  KEY `draft_orders_state_index` (`state`),
  KEY `draft_orders_customer_id_status_index` (`customer_id`,`status`),
  KEY `draft_orders_parent_order_id_index` (`parent_order_id`),
  KEY `draft_orders_finalized_order_id_index` (`finalized_order_id`),
  KEY `draft_orders_order_request_id_index` (`order_request_id`),
  KEY `draft_orders_purchase_mode_index` (`purchase_mode`),
  CONSTRAINT `draft_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `draft_orders_finalized_order_id_foreign` FOREIGN KEY (`finalized_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `draft_orders_order_request_id_foreign` FOREIGN KEY (`order_request_id`) REFERENCES `order_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `draft_orders_parent_order_id_foreign` FOREIGN KEY (`parent_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=689 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emails_email_unique` (`email`),
  KEY `emails_is_active_index` (`is_active`),
  KEY `emails_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `emails_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3453 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `engine_test`
--

DROP TABLE IF EXISTS `engine_test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `engine_test` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `global_fees`
--

DROP TABLE IF EXISTS `global_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_fees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dabba_fee_rate` decimal(5,4) NOT NULL,
  `dabba_fee_min` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `global_fees_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `helpdesk_communications`
--

DROP TABLE IF EXISTS `helpdesk_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_communications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `direction` enum('inbound','outbound','internal') NOT NULL DEFAULT 'internal',
  `channel` enum('email','phone','whatsapp','retailer','internal') NOT NULL DEFAULT 'internal',
  `party_type` enum('customer','retailer','team') NOT NULL DEFAULT 'team',
  `party_label` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `follow_up_at` datetime DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_order_item_id` (`order_item_id`),
  KEY `idx_created_by` (`created_by_user_id`),
  KEY `idx_follow_up` (`follow_up_at`),
  CONSTRAINT `fk_helpdesk_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_helpdesk_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_helpdesk_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_reminders`
--

DROP TABLE IF EXISTS `invoice_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `invoice_version_id` bigint(20) unsigned NOT NULL,
  `reminder_type` varchar(30) NOT NULL DEFAULT 'payment_reminder',
  `sequence_no` int(10) unsigned NOT NULL DEFAULT 1,
  `due_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_reminders_unique` (`invoice_version_id`,`reminder_type`,`sequence_no`),
  KEY `inv_reminders_due_idx` (`due_at`,`sent_at`),
  KEY `inv_reminders_invoice_idx` (`invoice_id`),
  KEY `invoice_reminders_created_by_user_id_foreign` (`created_by_user_id`),
  CONSTRAINT `invoice_reminders_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_reminders_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_reminders_invoice_version_id_foreign` FOREIGN KEY (`invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_version_items`
--

DROP TABLE IF EXISTS `invoice_version_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_version_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_version_id` bigint(20) unsigned NOT NULL,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `url` text DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_delivery_fee` decimal(10,2) DEFAULT NULL,
  `item_retailer_delivery_fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_version_items_invoice_version_id_sort_order_index` (`invoice_version_id`,`sort_order`),
  KEY `invoice_version_items_retailer_id_index` (`retailer_id`),
  KEY `invoice_version_items_sku_index` (`sku`),
  CONSTRAINT `invoice_version_items_invoice_version_id_foreign` FOREIGN KEY (`invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_version_items_retailer_id_foreign` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2586 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_version_retailers`
--

DROP TABLE IF EXISTS `invoice_version_retailers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_version_retailers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_version_id` bigint(20) unsigned NOT NULL,
  `retailer_id` bigint(20) unsigned NOT NULL,
  `retailer_name` varchar(255) DEFAULT NULL,
  `retailer_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_delivery_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_rate` decimal(8,4) DEFAULT NULL,
  `dabba_fee_min` decimal(10,2) DEFAULT NULL,
  `dabba_fee_is_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `dabba_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_min_applied` tinyint(1) NOT NULL DEFAULT 0,
  `retailer_grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_version_retailers_invoice_version_id_retailer_id_unique` (`invoice_version_id`,`retailer_id`),
  KEY `invoice_version_retailers_retailer_id_index` (`retailer_id`),
  CONSTRAINT `invoice_version_retailers_invoice_version_id_foreign` FOREIGN KEY (`invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_version_retailers_retailer_id_foreign` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=940 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_versions`
--

DROP TABLE IF EXISTS `invoice_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ISSUED',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `approval_channel` varchar(30) DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `items_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `issued_at` timestamp NULL DEFAULT NULL,
  `issued_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_versions_order_id_version_unique` (`order_id`,`version`),
  KEY `invoice_versions_approved_by_user_id_foreign` (`approved_by_user_id`),
  KEY `invoice_versions_issued_by_user_id_foreign` (`issued_by_user_id`),
  KEY `invoice_versions_order_id_status_index` (`order_id`,`status`),
  KEY `invoice_versions_status_index` (`status`),
  KEY `invoice_versions_approved_at_index` (`approved_at`),
  KEY `invoice_versions_issued_at_index` (`issued_at`),
  CONSTRAINT `invoice_versions_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_versions_issued_by_user_id_foreign` FOREIGN KEY (`issued_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoice_versions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=726 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `pdf_path` text DEFAULT NULL,
  `sent_to_customer_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_order_id_unique` (`order_id`),
  KEY `invoices_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `invoices_invoice_number_index` (`invoice_number`),
  KEY `invoices_sent_to_customer_at_index` (`sent_to_customer_at`),
  KEY `idx_inv_pdf_prefix` (`pdf_path`(191))
) ENGINE=InnoDB AUTO_INCREMENT=726 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`(250))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_amendments`
--

DROP TABLE IF EXISTS `order_amendments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_amendments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `related_order_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `oa_order_idx` (`order_id`),
  KEY `oa_order_item_idx` (`order_item_id`),
  KEY `oa_related_order_idx` (`related_order_id`),
  KEY `oa_type_idx` (`type`),
  KEY `oa_order_type_idx` (`order_id`,`type`),
  KEY `oa_created_by_fk` (`created_by_user_id`),
  CONSTRAINT `oa_created_by_fk` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oa_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oa_order_item_fk` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `oa_related_order_fk` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_documents`
--

DROP TABLE IF EXISTS `order_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `doc_type` varchar(30) NOT NULL DEFAULT 'invoice_pdf',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `storage_disk` varchar(50) NOT NULL DEFAULT 'public',
  `storage_path` text DEFAULT NULL,
  `content_hash` varchar(64) NOT NULL,
  `byte_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `items_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_documents_order_type_version_unique` (`order_id`,`doc_type`,`version`),
  KEY `order_documents_generated_by_user_id_foreign` (`generated_by_user_id`),
  KEY `order_documents_order_id_doc_type_index` (`order_id`,`doc_type`),
  KEY `idx_od_storage_prefix` (`storage_path`(191)),
  CONSTRAINT `order_documents_generated_by_user_id_foreign` FOREIGN KEY (`generated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_documents_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_events`
--

DROP TABLE IF EXISTS `order_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_events_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `order_events_entity_type_index` (`entity_type`),
  KEY `order_events_entity_id_index` (`entity_id`),
  KEY `order_events_event_type_index` (`event_type`),
  KEY `order_events_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  CONSTRAINT `order_events_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_item_purchases`
--

DROP TABLE IF EXISTS `order_item_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `root_item_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_retailer_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'requested',
  `reversal_of_purchase_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_unit_price` decimal(12,2) DEFAULT NULL,
  `purchase_line_total` decimal(12,2) DEFAULT NULL,
  `estimated_retailer_delivery_date` date DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `marketplace_seller` varchar(255) DEFAULT NULL,
  `retailer_order_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `problem_code` varchar(50) DEFAULT NULL,
  `resolution_action` varchar(50) DEFAULT NULL,
  `problem_notes` text DEFAULT NULL,
  `ordered_at` datetime DEFAULT NULL,
  `expected_dispatch_at` datetime DEFAULT NULL,
  `expected_uk_hub_at` datetime DEFAULT NULL,
  `requires_marking_attention` tinyint(1) NOT NULL DEFAULT 0,
  `expected_gibraltar_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resolution_status` varchar(32) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `order_item_purchases_order_item_id_index` (`order_item_id`),
  KEY `order_item_purchases_order_id_index` (`order_id`),
  KEY `order_item_purchases_order_retailer_id_index` (`order_retailer_id`),
  KEY `order_item_purchases_retailer_id_index` (`retailer_id`),
  KEY `order_item_purchases_status_index` (`status`),
  KEY `order_item_purchases_order_item_id_status_index` (`order_item_id`,`status`),
  KEY `order_item_purchases_order_id_status_index` (`order_id`,`status`),
  KEY `order_item_purchases_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `order_item_purchases_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `oip_status_ordered_at_idx` (`status`,`ordered_at`),
  KEY `oip_item_status_idx` (`order_item_id`,`status`),
  KEY `oip_reversal_of_purchase_id_idx` (`reversal_of_purchase_id`),
  KEY `idx_order_item_purchases_root_item_id` (`root_item_id`),
  KEY `idx_oip_root_status` (`root_item_id`,`status`),
  KEY `idx_purchase_root_status` (`root_item_id`,`status`),
  KEY `idx_oip_status_cancelled` (`status`,`cancelled_at`),
  KEY `idx_oip_order` (`order_id`),
  KEY `idx_oip_order_item` (`order_item_id`),
  KEY `idx_oip_root` (`root_item_id`),
  KEY `idx_oip_status_created` (`status`,`created_at`),
  KEY `idx_oip_item_lookup` (`order_item_id`,`status`,`qty`),
  KEY `idx_oip_root_lookup` (`root_item_id`,`status`,`qty`),
  KEY `idx_oip_dates` (`ordered_at`,`expected_uk_hub_at`),
  KEY `idx_oip_retailer_ref` (`retailer_order_reference`),
  KEY `idx_oip_status_ordered` (`status`,`ordered_at`),
  CONSTRAINT `order_item_purchases_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_item_purchases_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_item_purchases_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_item_purchases_order_retailer_id_foreign` FOREIGN KEY (`order_retailer_id`) REFERENCES `order_retailers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_item_purchases_retailer_id_foreign` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_item_purchases_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_retailer_id` bigint(20) unsigned DEFAULT NULL,
  `source_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `root_item_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `product_code` varchar(191) DEFAULT NULL,
  `product_url` text DEFAULT NULL,
  `marketplace_seller` varchar(500) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `line_subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `item_retailer_delivery_fee` decimal(12,2) DEFAULT NULL,
  `retailer_delivery_allocated` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `purchase_currency` varchar(3) DEFAULT 'GBP',
  `purchased_at` datetime DEFAULT NULL,
  `purchase_problem_reason` varchar(64) DEFAULT NULL,
  `purchase_problem_note` text DEFAULT NULL,
  `last_status_changed_at` datetime DEFAULT NULL,
  `service_fee_overridden` tinyint(1) NOT NULL DEFAULT 0,
  `service_fee_override_amount` decimal(12,2) DEFAULT NULL,
  `dabba_fee_allocated` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(64) NOT NULL DEFAULT 'requested',
  `requires_inspection` tinyint(1) NOT NULL DEFAULT 0,
  `inspection_note` text DEFAULT NULL,
  `retailer_order_reference` varchar(500) DEFAULT NULL,
  `tracking_reference` varchar(500) DEFAULT NULL,
  `customs_value` decimal(12,2) DEFAULT NULL,
  `ordered_at` date DEFAULT NULL,
  `arrived_at` date DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_index` (`order_id`),
  KEY `order_items_order_retailer_id_index` (`order_retailer_id`),
  KEY `order_items_status_index` (`status`),
  KEY `order_items_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `order_items_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `idx_order_items_order_sort` (`order_id`,`sort_order`),
  KEY `idx_order_items_requires_inspection` (`requires_inspection`),
  KEY `order_items_source_item_idx` (`source_order_item_id`),
  KEY `order_items_last_status_changed_at_idx` (`last_status_changed_at`),
  KEY `idx_order_items_root_item_id` (`root_item_id`),
  KEY `idx_oi_product_code` (`product_code`),
  KEY `idx_order_items_main` (`order_id`,`order_retailer_id`),
  KEY `idx_order_items_product` (`product_code`),
  KEY `idx_oi_product_url_prefix` (`product_url`(191)),
  KEY `idx_oi_order_retailer_ref` (`order_id`,`retailer_order_reference`),
  CONSTRAINT `fk_order_items_root` FOREIGN KEY (`root_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `order_items_source_order_item_id_foreign` FOREIGN KEY (`source_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2818 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_ref_counter`
--

DROP TABLE IF EXISTS `order_ref_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_ref_counter` (
  `id` tinyint(3) unsigned NOT NULL,
  `next_value` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_request_attachments`
--

DROP TABLE IF EXISTS `order_request_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_request_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_request_id` bigint(20) unsigned NOT NULL,
  `path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_request_attachments_order_request_id_foreign` (`order_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_request_items`
--

DROP TABLE IF EXISTS `order_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_request_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_request_id` bigint(20) unsigned NOT NULL,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_name` varchar(190) DEFAULT NULL,
  `retailer_url` text DEFAULT NULL,
  `product_code` varchar(120) DEFAULT NULL,
  `description` text NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `line_total` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_request_items_order_request_id_sort_order_index` (`order_request_id`,`sort_order`),
  KEY `order_request_items_retailer_id_index` (`retailer_id`),
  KEY `idx_ori_retailer_url_prefix` (`retailer_url`(191)),
  KEY `idx_ori_retailer_code` (`retailer_id`,`product_code`),
  CONSTRAINT `order_request_items_order_request_id_foreign` FOREIGN KEY (`order_request_id`) REFERENCES `order_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_request_items_retailer_id_foreign` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1598 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_requests`
--

DROP TABLE IF EXISTS `order_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_ref` varchar(20) DEFAULT NULL,
  `source` varchar(80) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `customer_first_name` varchar(100) DEFAULT NULL,
  `customer_last_name` varchar(100) DEFAULT NULL,
  `customer_company_name` varchar(150) DEFAULT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `customer_phone_country_id` bigint(20) unsigned DEFAULT NULL,
  `customer_phone_digits` varchar(40) DEFAULT NULL,
  `customer_address_line1` varchar(190) DEFAULT NULL,
  `customer_address_postcode` varchar(40) DEFAULT NULL,
  `customer_address_country_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `purchase_mode` varchar(40) NOT NULL DEFAULT 'standard',
  `estimated_total` decimal(12,2) DEFAULT NULL,
  `disclaimer_accepted_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `submitted_ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `converted_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `converted_draft_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `submission_uuid` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_requests_request_ref_unique` (`request_ref`),
  UNIQUE KEY `uniq_submission_uuid` (`submission_uuid`),
  KEY `order_requests_customer_phone_country_id_foreign` (`customer_phone_country_id`),
  KEY `order_requests_customer_address_country_id_foreign` (`customer_address_country_id`),
  KEY `order_requests_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `order_requests_source_index` (`source`),
  KEY `order_requests_reference_number_index` (`reference_number`),
  KEY `order_requests_customer_email_index` (`customer_email`),
  KEY `order_requests_status_index` (`status`),
  KEY `order_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `order_requests_converted_by_user_id_foreign` (`converted_by_user_id`),
  KEY `order_requests_converted_draft_order_id_foreign` (`converted_draft_order_id`),
  KEY `idx_order_requests_latest` (`id` DESC),
  KEY `idx_order_requests_submitted` (`submitted_at`),
  KEY `idx_order_requests_converted` (`converted_at`),
  KEY `idx_order_requests_source` (`source`),
  KEY `order_requests_purchase_mode_index` (`purchase_mode`),
  CONSTRAINT `order_requests_converted_by_user_id_foreign` FOREIGN KEY (`converted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_requests_converted_draft_order_id_foreign` FOREIGN KEY (`converted_draft_order_id`) REFERENCES `draft_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=724 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_retailers`
--

DROP TABLE IF EXISTS `order_retailers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_retailers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `retailer_id` bigint(20) unsigned NOT NULL,
  `retailer_name` varchar(191) DEFAULT NULL,
  `retailer_base_url` varchar(255) DEFAULT NULL,
  `retailer_items_subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_fee_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_rate` decimal(8,2) DEFAULT NULL,
  `dabba_fee_min` decimal(8,2) DEFAULT NULL,
  `dabba_fee_is_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `dabba_fee_reason` varchar(255) DEFAULT NULL,
  `dabba_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `service_fee_overridden` tinyint(1) NOT NULL DEFAULT 0,
  `service_fee_override_amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_retailers_order_id_retailer_id_unique` (`order_id`,`retailer_id`),
  KEY `order_retailers_order_id_index` (`order_id`),
  KEY `order_retailers_retailer_id_index` (`retailer_id`),
  KEY `order_retailers_status_index` (`status`),
  KEY `order_retailers_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `order_retailers_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `order_retailers_retailer_base_url_index` (`retailer_base_url`),
  KEY `order_retailers_name_idx` (`retailer_name`),
  KEY `idx_or_order_retailer_status` (`order_id`,`retailer_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1025 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_statuses`
--

DROP TABLE IF EXISTS `order_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_statuses_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_transactions`
--

DROP TABLE IF EXISTS `order_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `invoice_version_id` bigint(20) unsigned DEFAULT NULL,
  `payment_type_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `status` varchar(20) NOT NULL DEFAULT 'recorded',
  `received_at` timestamp NULL DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `channel` varchar(30) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_transactions_order_id_type_index` (`order_id`,`type`),
  KEY `order_transactions_order_id_index` (`order_id`),
  KEY `order_transactions_method_channel_index` (`method`,`channel`),
  KEY `order_transactions_received_at_index` (`received_at`),
  KEY `order_transactions_invoice_version_id_index` (`invoice_version_id`),
  KEY `order_transactions_payment_type_id_index` (`payment_type_id`),
  KEY `idx_order_transactions_lookup` (`order_id`,`type`,`amount`),
  CONSTRAINT `order_transactions_invoice_version_id_foreign` FOREIGN KEY (`invoice_version_id`) REFERENCES `invoice_versions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_transactions_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=689 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_order_id` bigint(20) unsigned NOT NULL,
  `source_draft_order_id` bigint(20) unsigned DEFAULT NULL,
  `parent_order_id` bigint(20) unsigned DEFAULT NULL,
  `order_type` varchar(20) NOT NULL DEFAULT 'invoice',
  `order_number` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'created',
  `purchase_mode` varchar(40) NOT NULL DEFAULT 'standard',
  `dabba_fee_level` varchar(30) NOT NULL,
  `dabba_fee_rate` decimal(5,4) NOT NULL,
  `dabba_fee_min` decimal(10,2) NOT NULL,
  `fee_mode` varchar(20) NOT NULL DEFAULT 'standard',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retailer_delivery_fee_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dabba_fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bill_to_name` varchar(120) DEFAULT NULL,
  `bill_to_company` varchar(120) DEFAULT NULL,
  `bill_to_email` varchar(190) DEFAULT NULL,
  `bill_to_phone` varchar(40) DEFAULT NULL,
  `bill_to_address_line1` varchar(190) DEFAULT NULL,
  `bill_to_postcode` varchar(30) DEFAULT NULL,
  `bill_to_country_id` bigint(20) unsigned DEFAULT NULL,
  `invoiced_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `needs_resend` tinyint(1) NOT NULL DEFAULT 0,
  `last_sent_document_id` bigint(20) unsigned DEFAULT NULL,
  `last_sent_to` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `purchased_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_cancelled_by_user_id_foreign` (`cancelled_by_user_id`),
  KEY `orders_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `orders_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `orders_order_number_index` (`order_number`),
  KEY `orders_status_index` (`status`),
  KEY `orders_invoiced_at_index` (`invoiced_at`),
  KEY `orders_cancelled_at_index` (`cancelled_at`),
  KEY `orders_sent_at_index` (`sent_at`),
  KEY `orders_paid_at_index` (`paid_at`),
  KEY `orders_shipped_at_index` (`shipped_at`),
  KEY `orders_completed_at_index` (`completed_at`),
  KEY `orders_bill_to_email_index` (`bill_to_email`),
  KEY `orders_bill_to_country_id_index` (`bill_to_country_id`),
  KEY `orders_source_draft_order_id_index` (`source_draft_order_id`),
  KEY `orders_parent_order_id_index` (`parent_order_id`),
  KEY `orders_status_paid_at_index` (`status`,`paid_at`),
  KEY `orders_last_sent_document_id_fk` (`last_sent_document_id`),
  KEY `orders_purchased_at_index` (`purchased_at`),
  KEY `orders_active_flags_idx` (`completed_at`,`cancelled_at`),
  KEY `orders_parent_idx` (`parent_order_id`),
  KEY `orders_parent_order_idx` (`parent_order_id`),
  KEY `orders_order_number_id_idx` (`order_number`,`id`),
  KEY `idx_orders_status_cancelled` (`status`,`cancelled_at`),
  KEY `idx_orders_draft` (`draft_order_id`),
  KEY `idx_orders_status_lookup` (`cancelled_at`,`completed_at`,`id`),
  KEY `orders_draft_order_id_index` (`draft_order_id`),
  KEY `orders_purchase_mode_index` (`purchase_mode`),
  CONSTRAINT `orders_bill_to_country_id_foreign` FOREIGN KEY (`bill_to_country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `orders_last_sent_document_id_fk` FOREIGN KEY (`last_sent_document_id`) REFERENCES `order_documents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `orders_parent_order_id_foreign` FOREIGN KEY (`parent_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `orders_source_draft_order_id_foreign` FOREIGN KEY (`source_draft_order_id`) REFERENCES `draft_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=786 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_types`
--

DROP TABLE IF EXISTS `payment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_types_name_unique` (`name`),
  KEY `payment_types_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `method` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(191) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `recorded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  KEY `payments_order_id_received_at_index` (`order_id`,`received_at`),
  KEY `payments_method_index` (`method`),
  KEY `payments_received_at_index` (`received_at`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_recorded_by_user_id_foreign` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phones`
--

DROP TABLE IF EXISTS `phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `phones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(64) NOT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phones_phone_country_unique` (`phone`,`country_id`),
  KEY `phones_is_active_index` (`is_active`),
  KEY `phones_country_id_index` (`country_id`),
  KEY `phones_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `phones_updated_by_user_id_foreign` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=980 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_arrival_assignments`
--

DROP TABLE IF EXISTS `purchase_arrival_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_arrival_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `arrival_package_id` bigint(20) unsigned NOT NULL,
  `order_item_purchase_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `root_item_id` bigint(20) unsigned DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `matched_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'arrived',
  `status_updated_at` timestamp NULL DEFAULT NULL,
  `status_updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `matched_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `undone_at` timestamp NULL DEFAULT NULL,
  `undone_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_arrival_assignments_order_item_id_foreign` (`order_item_id`),
  KEY `purchase_arrival_assignments_matched_by_user_id_foreign` (`matched_by_user_id`),
  KEY `purchase_arrival_assignments_undone_by_user_id_foreign` (`undone_by_user_id`),
  KEY `paa_purchase_undone_idx` (`order_item_purchase_id`,`undone_at`),
  KEY `paa_package_undone_idx` (`arrival_package_id`,`undone_at`),
  KEY `paa_order_item_idx` (`order_id`,`order_item_id`),
  KEY `purchase_arrival_assignments_root_item_id_index` (`root_item_id`),
  KEY `purchase_arrival_assignments_matched_at_index` (`matched_at`),
  KEY `purchase_arrival_assignments_undone_at_index` (`undone_at`),
  KEY `paa_status_undone_idx` (`status`,`undone_at`),
  KEY `paa_order_status_undone_idx` (`order_id`,`status`,`undone_at`),
  KEY `paa_matched_undone_idx` (`matched_at`,`undone_at`),
  KEY `idx_arrival_purchase` (`order_item_purchase_id`,`undone_at`),
  CONSTRAINT `purchase_arrival_assignments_arrival_package_id_foreign` FOREIGN KEY (`arrival_package_id`) REFERENCES `arrival_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_arrival_assignments_matched_by_user_id_foreign` FOREIGN KEY (`matched_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_arrival_assignments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_arrival_assignments_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_arrival_assignments_order_item_purchase_id_foreign` FOREIGN KEY (`order_item_purchase_id`) REFERENCES `order_item_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_arrival_assignments_undone_by_user_id_foreign` FOREIGN KEY (`undone_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1992 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `retailers`
--

DROP TABLE IF EXISTS `retailers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retailers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `base_url` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `logo_path` varchar(191) DEFAULT NULL,
  `internal_note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retailers_base_url_unique` (`base_url`),
  KEY `retailers_is_active_index` (`is_active`),
  KEY `retailers_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `retailers_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `retailers_base_url_hash_index` (`base_url`(150))
) ENGINE=InnoDB AUTO_INCREMENT=400 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipment_items`
--

DROP TABLE IF EXISTS `shipment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `tracking_number` varchar(512) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipment_items_shipment_id_order_item_id_unique` (`shipment_id`,`order_item_id`),
  KEY `shipment_items_shipment_id_index` (`shipment_id`),
  KEY `shipment_items_order_item_id_index` (`order_item_id`),
  KEY `shipment_items_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `shipment_items_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `idx_shipment_items_order_track` (`order_item_id`,`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_order_reference` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(512) DEFAULT NULL,
  `tracking_url` text DEFAULT NULL,
  `shipped_by` enum('retailer','forwarding_agent','unknown') NOT NULL DEFAULT 'unknown',
  `shipped_at` date DEFAULT NULL,
  `arrived_uk_hub_at` date DEFAULT NULL,
  `arrived_gibraltar_at` date DEFAULT NULL,
  `status` enum('expected','in_transit','arrived_uk','in_transit_to_gibraltar','arrived_gibraltar','identified') NOT NULL DEFAULT 'expected',
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipments_retailer_id_foreign` (`retailer_id`),
  KEY `shipments_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `shipments_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `idx_ship_tracking_url_prefix` (`tracking_url`(191)),
  KEY `idx_ship_retailer_status` (`retailer_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `disabled_at` timestamp NULL DEFAULT NULL,
  `disabled_reason` varchar(191) DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_is_disabled_index` (`is_disabled`),
  KEY `users_created_by_user_id_index` (`created_by_user_id`),
  KEY `users_updated_by_user_id_index` (`updated_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claim_activity_logs`
--

DROP TABLE IF EXISTS `vat_claim_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claim_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vat_claim_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'note',
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vat_claim_activity_logs_vat_claim_id_index` (`vat_claim_id`),
  KEY `vat_claim_activity_logs_type_index` (`type`),
  KEY `vat_claim_activity_logs_created_by_user_id_index` (`created_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claim_batch_items`
--

DROP TABLE IF EXISTS `vat_claim_batch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claim_batch_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vat_claim_batch_id` bigint(20) unsigned NOT NULL,
  `vat_claim_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vat_claim_batch_items_unique` (`vat_claim_batch_id`,`vat_claim_id`),
  KEY `vat_claim_batch_items_vat_claim_batch_id_index` (`vat_claim_batch_id`),
  KEY `vat_claim_batch_items_vat_claim_id_index` (`vat_claim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claim_batches`
--

DROP TABLE IF EXISTS `vat_claim_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claim_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'draft',
  `title` varchar(191) DEFAULT NULL,
  `exported_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `accountant_response_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vat_claim_batches_period_start_index` (`period_start`),
  KEY `vat_claim_batches_period_end_index` (`period_end`),
  KEY `vat_claim_batches_status_index` (`status`),
  KEY `vat_claim_batches_created_by_user_id_index` (`created_by_user_id`),
  KEY `vat_claim_batches_updated_by_user_id_index` (`updated_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claim_documents`
--

DROP TABLE IF EXISTS `vat_claim_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claim_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vat_claim_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL DEFAULT 'other',
  `storage_disk` varchar(50) NOT NULL DEFAULT 'public',
  `storage_path` text NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `byte_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `uploaded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vat_claim_documents_vat_claim_id_index` (`vat_claim_id`),
  KEY `vat_claim_documents_document_type_index` (`document_type`),
  KEY `vat_claim_documents_uploaded_by_user_id_index` (`uploaded_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claim_purchase_links`
--

DROP TABLE IF EXISTS `vat_claim_purchase_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claim_purchase_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vat_claim_id` bigint(20) unsigned NOT NULL,
  `order_item_purchase_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL DEFAULT 1,
  `purchase_line_total` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vat_claim_purchase_links_oip_unique` (`order_item_purchase_id`),
  KEY `vat_claim_purchase_links_claim_oip_idx` (`vat_claim_id`,`order_item_purchase_id`),
  KEY `vat_claim_purchase_links_vat_claim_id_index` (`vat_claim_id`),
  KEY `vat_claim_purchase_links_order_item_purchase_id_index` (`order_item_purchase_id`),
  KEY `vat_claim_purchase_links_order_item_id_index` (`order_item_id`),
  KEY `vat_claim_purchase_links_order_id_index` (`order_id`),
  KEY `vat_claim_purchase_links_retailer_id_index` (`retailer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vat_claims`
--

DROP TABLE IF EXISTS `vat_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `retailer_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `retailer_name` varchar(191) DEFAULT NULL,
  `retailer_order_reference` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(120) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `gross_amount` decimal(12,2) DEFAULT NULL,
  `net_amount` decimal(12,2) DEFAULT NULL,
  `vat_amount` decimal(12,2) DEFAULT NULL,
  `final_claimable_vat_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'GBP',
  `vat_rate_type` varchar(30) NOT NULL DEFAULT 'unknown',
  `vat_amount_source` varchar(30) NOT NULL DEFAULT 'unknown',
  `evidence_status` varchar(40) NOT NULL DEFAULT 'not_requested',
  `claim_status` varchar(40) NOT NULL DEFAULT 'draft',
  `requested_at` timestamp NULL DEFAULT NULL,
  `last_chased_at` timestamp NULL DEFAULT NULL,
  `chase_count` int(10) unsigned NOT NULL DEFAULT 0,
  `received_at` timestamp NULL DEFAULT NULL,
  `refused_at` timestamp NULL DEFAULT NULL,
  `sent_to_accountant_at` timestamp NULL DEFAULT NULL,
  `accountant_reviewed_at` timestamp NULL DEFAULT NULL,
  `retailer_response_notes` text DEFAULT NULL,
  `accountant_notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `accountant_reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vat_claims_group_idx` (`order_id`,`retailer_id`,`retailer_order_reference`),
  KEY `vat_claims_order_id_index` (`order_id`),
  KEY `vat_claims_retailer_id_index` (`retailer_id`),
  KEY `vat_claims_order_number_index` (`order_number`),
  KEY `vat_claims_retailer_order_reference_index` (`retailer_order_reference`),
  KEY `vat_claims_invoice_number_index` (`invoice_number`),
  KEY `vat_claims_invoice_date_index` (`invoice_date`),
  KEY `vat_claims_purchase_date_index` (`purchase_date`),
  KEY `vat_claims_evidence_status_index` (`evidence_status`),
  KEY `vat_claims_claim_status_index` (`claim_status`),
  KEY `vat_claims_requested_at_index` (`requested_at`),
  KEY `vat_claims_last_chased_at_index` (`last_chased_at`),
  KEY `vat_claims_received_at_index` (`received_at`),
  KEY `vat_claims_refused_at_index` (`refused_at`),
  KEY `vat_claims_sent_to_accountant_at_index` (`sent_to_accountant_at`),
  KEY `vat_claims_accountant_reviewed_at_index` (`accountant_reviewed_at`),
  KEY `vat_claims_created_by_user_id_index` (`created_by_user_id`),
  KEY `vat_claims_updated_by_user_id_index` (`updated_by_user_id`),
  KEY `vat_claims_accountant_reviewed_by_user_id_index` (`accountant_reviewed_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-10 18:58:25
