-- ============================================================
-- Leads Table — Online Uttaranchal University
-- Run this file in PHPMyAdmin on database: u261758575_uttranchal1
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `leads` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `student_name`     VARCHAR(100)  NOT NULL,
    `student_email`    VARCHAR(150)  NOT NULL,
    `student_mobile`   VARCHAR(20)   NOT NULL,
    `student_program`  VARCHAR(100)  NOT NULL,
    `student_source`   VARCHAR(100)  DEFAULT 'Website',
    `student_ip`       VARCHAR(45)   DEFAULT NULL,
    `country_code`     VARCHAR(10)   DEFAULT '+91',
    `city`             VARCHAR(100)  DEFAULT NULL,
    `param1`           VARCHAR(200)  DEFAULT NULL COMMENT 'media / utm_source',
    `param2`           VARCHAR(200)  DEFAULT NULL COMMENT 'campaign / utm_campaign',
    `param3`           VARCHAR(200)  DEFAULT NULL COMMENT 'ltype / utm_medium',
    `page`             VARCHAR(500)  DEFAULT NULL,
    `created_at`       DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email`      (`student_email`),
    INDEX `idx_mobile`     (`student_mobile`),
    INDEX `idx_program`    (`student_program`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
