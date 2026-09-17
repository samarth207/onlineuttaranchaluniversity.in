-- ============================================================
-- Blog CMS Database Schema — Online Uttaranchal University
-- Configure the database name and credentials in api/db-config.php.
-- Run this file in PHPMyAdmin BEFORE running admin/setup.php
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- =====================
-- ADMIN USERS
-- =====================
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('superadmin','admin','editor') DEFAULT 'admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- LOGIN ATTEMPTS (brute force protection)
-- =====================
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_attempted` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOG AUTHORS
-- =====================
CREATE TABLE IF NOT EXISTS `blog_authors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `bio` TEXT DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `page_url` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOG CATEGORIES
-- =====================
CREATE TABLE IF NOT EXISTS `blog_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOG TAGS
-- =====================
CREATE TABLE IF NOT EXISTS `blog_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOGS (MAIN TABLE)
-- =====================
CREATE TABLE IF NOT EXISTS `blogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT DEFAULT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `toc` JSON DEFAULT NULL,
    `feature_image` VARCHAR(500) DEFAULT NULL,
    `feature_image_alt` VARCHAR(300) DEFAULT NULL,
    `feature_image_title` VARCHAR(300) DEFAULT NULL,
    `meta_title` VARCHAR(100) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `focus_keyword` VARCHAR(200) DEFAULT NULL,
    `primary_keyword` VARCHAR(200) DEFAULT NULL,
    `author_id` INT DEFAULT NULL,
    `category_ids` JSON DEFAULT NULL,
    `status` ENUM('draft','pending','published','scheduled','deleted') DEFAULT 'draft',
    `publish_date` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `og_image` VARCHAR(500) DEFAULT NULL,
    `canonical_url` VARCHAR(500) DEFAULT NULL,
    `schema_json` LONGTEXT DEFAULT NULL,
    `views` INT DEFAULT 0,
    `read_time` INT DEFAULT NULL,
    FOREIGN KEY (`author_id`) REFERENCES `blog_authors`(`id`) ON DELETE SET NULL,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_publish_date` (`publish_date`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- BLOG TAG RELATIONS (many-to-many)
-- =====================
CREATE TABLE IF NOT EXISTS `blog_tag_relations` (
    `blog_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`blog_id`, `tag_id`),
    FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================
-- DEFAULT SEED DATA
-- =====================

-- Default Author
INSERT IGNORE INTO `blog_authors` (`name`, `bio`, `is_active`) VALUES
('Uttaranchal Online Team', 'Official content team of Online Uttaranchal University, sharing updates and guidance for online learners.', 1);

-- Default Categories
INSERT IGNORE INTO `blog_categories` (`name`, `slug`, `description`) VALUES
('Online MBA', 'online-mba', 'Articles, guides and news about Online MBA programs'),
('Online BBA', 'online-bba', 'Everything about Online BBA programs at Chandigarh University'),
('Online MCA', 'online-mca', 'Articles and guides on Online MCA programs'),
('Online BCA', 'online-bca', 'Resources and news for Online BCA programs'),
('Education News', 'education-news', 'Latest news and updates from the world of online education'),
('Career Guidance', 'career-guidance', 'Career tips, industry insights and professional development'),
('University News', 'university-news', 'Uttaranchal University updates, achievements and announcements');

-- NOTE: Admin user is inserted by admin/setup.php with a proper bcrypt hash.
-- Run admin/setup.php AFTER running this SQL file.
