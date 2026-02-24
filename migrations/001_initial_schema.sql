-- Gem & Mineral Collection Tracker
-- Initial Schema Migration
-- Run this via phpMyAdmin or MySQL CLI

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Users (admin authentication)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(255) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Specimens (core item record - kept lean)
-- ============================================
CREATE TABLE IF NOT EXISTS `specimens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_published` (`is_published`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Custom Fields (dynamic field definitions)
-- ============================================
CREATE TABLE IF NOT EXISTS `custom_fields` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `field_name` VARCHAR(100) NOT NULL UNIQUE,
    `label` VARCHAR(150) NOT NULL,
    `field_type` ENUM('text', 'textarea', 'number', 'select', 'multi_select', 'date', 'url', 'color') NOT NULL DEFAULT 'text',
    `options_json` JSON DEFAULT NULL COMMENT 'For select/multi_select field types',
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `is_filterable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_visible_public` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Soft-delete: retire fields without losing data',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sort` (`sort_order`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Specimen Field Values (EAV data storage)
-- ============================================
CREATE TABLE IF NOT EXISTS `specimen_field_values` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `specimen_id` INT UNSIGNED NOT NULL,
    `field_id` INT UNSIGNED NOT NULL,
    `value` TEXT DEFAULT NULL,
    UNIQUE KEY `uk_specimen_field` (`specimen_id`, `field_id`),
    CONSTRAINT `fk_sfv_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `specimens`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sfv_field` FOREIGN KEY (`field_id`) REFERENCES `custom_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Photos (multiple per specimen)
-- ============================================
CREATE TABLE IF NOT EXISTS `photos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `specimen_id` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `caption` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `file_size` INT UNSIGNED DEFAULT NULL COMMENT 'In bytes',
    `width` INT UNSIGNED DEFAULT NULL,
    `height` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_specimen` (`specimen_id`),
    INDEX `idx_primary` (`specimen_id`, `is_primary`),
    CONSTRAINT `fk_photos_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `specimens`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Categories (Phase 2 - created now for FK readiness)
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `specimen_categories` (
    `specimen_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`specimen_id`, `category_id`),
    CONSTRAINT `fk_sc_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `specimens`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sc_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Settings (key-value for site configuration)
-- ============================================
CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Seed: Default custom fields (from user's field list)
-- ============================================
INSERT INTO `custom_fields` (`field_name`, `label`, `field_type`, `options_json`, `is_required`, `is_filterable`, `is_visible_public`, `sort_order`) VALUES
-- Core identification
('identifier_key', 'Identifier Key', 'text', NULL, 0, 0, 1, 1),
('type', 'Type', 'select', '["Mineral","Fossil","Meteorite","Beach Rock"]', 1, 1, 1, 2),
('specimen_form', 'Specimen Form', 'select', '["Raw","Slab Cut Raw","Slab Cut Polished","Tumbled","Crystal Point","Crystal Cluster"]', 0, 1, 1, 3),
('location', 'Location', 'text', NULL, 0, 1, 1, 4),
('display_options', 'Display Options', 'select', '["Collection Case","Small Square Box","Small Circle Box","Stand","Glass","Specimen Case (in Collection Case)","House","Outside"]', 0, 1, 0, 5),
('aka', 'AKA', 'text', NULL, 0, 0, 1, 6),
('safe_to_handle', 'Safe to Handle', 'select', '["Y","N"]', 0, 1, 1, 7),
('minerals_in_specimen', 'Minerals In Specimen', 'text', NULL, 0, 0, 1, 8),

-- Fluorescence
('fluoresces', 'Fluoresces', 'select', '["Y","N"]', 0, 1, 1, 9),
('fluorescent_long_wave', 'Fluorescent (Long Wave)', 'text', NULL, 0, 0, 1, 10),
('fluorescent_mid_wave', 'Fluorescent (Mid Wave)', 'text', NULL, 0, 0, 1, 11),
('fluorescent_short_wave', 'Fluorescent (Short Wave)', 'text', NULL, 0, 0, 1, 12),

-- Special properties
('radioactive', 'Radioactive', 'select', '["Y","N"]', 0, 1, 1, 13),
('meteorite_type', 'Meteorite Type', 'text', NULL, 0, 1, 1, 14),
('meteorite_name', 'Meteorite Name', 'text', NULL, 0, 0, 1, 15),

-- Mindat reference
('mindat_mineral_id', 'Mindat Mineral ID', 'text', NULL, 0, 0, 1, 16),
('mindat_mineral_link', 'Mindat Mineral Link', 'url', NULL, 0, 0, 1, 17),

-- Dimensions
('height', 'Height', 'number', NULL, 0, 0, 1, 18),
('width', 'Width', 'number', NULL, 0, 0, 1, 19),
('depth', 'Depth', 'number', NULL, 0, 0, 1, 20),
('dimension_unit', 'Dimension Unit', 'select', '["cm","mm","in"]', 0, 0, 1, 21),
('weight', 'Weight', 'number', NULL, 0, 0, 1, 22),
('weight_unit', 'Weight Unit', 'select', '["g","kg","ct","oz","lb"]', 0, 0, 1, 23),

-- Description
('description_field', 'Description', 'textarea', NULL, 0, 0, 1, 24),

-- Specimen characteristics (yes/no flags)
('pseudomorph', 'Pseudomorph', 'select', '["Y","N"]', 0, 1, 1, 25),
('epimorph', 'Epimorph', 'select', '["Y","N"]', 0, 1, 1, 26),
('partial', 'Partial', 'select', '["Y","N"]', 0, 0, 1, 27),
('irradiated', 'Irradiated', 'select', '["Y","N"]', 0, 0, 1, 28),
('heated', 'Heated', 'select', '["Y","N"]', 0, 0, 1, 29),
('repaired', 'Repaired', 'select', '["Y","N"]', 0, 0, 1, 30),

-- Provenance & acquisition
('collected_by', 'Collected By', 'text', NULL, 0, 0, 0, 31),
('date_collected', 'Date Collected', 'date', NULL, 0, 0, 0, 32),
('acquisition_notes', 'Acquisition Notes', 'textarea', NULL, 0, 0, 0, 33),
('added_to_collection_date', 'Added To Collection Date', 'date', NULL, 0, 0, 0, 34),

-- Quality & value (private by default)
('quality', 'Quality', 'text', NULL, 0, 0, 0, 35),
('quality_notes', 'Quality Notes', 'textarea', NULL, 0, 0, 0, 36),
('amount_paid', 'Amount Paid', 'text', NULL, 0, 0, 0, 37),
('perceived_value', 'Perceived Value', 'text', NULL, 0, 0, 0, 38);

-- ============================================
-- Seed: Default settings
-- ============================================
INSERT INTO `settings` (`key`, `value`) VALUES
('site_title', 'Gem & Mineral Collection'),
('site_description', 'A curated collection of gems, minerals, and geological specimens'),
('items_per_page', '24'),
('thumbnail_width', '400'),
('thumbnail_height', '400'),
('max_upload_width', '2048'),
('admin_email', '');
