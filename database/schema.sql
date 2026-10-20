-- Order Status Tracker — Order Tracking
-- Run against the existing `order-tracking` database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL DEFAULT NULL,
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- statuses (reusable predefined status library / templates)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `statuses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `expected_duration_value` DECIMAL(6,2) NULL DEFAULT NULL,
    `expected_duration_unit` ENUM('instant','days','weeks','months') NOT NULL DEFAULT 'instant',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_statuses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- orders
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `advanced_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `public_token` CHAR(43) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_order_number` (`order_number`),
    UNIQUE KEY `uq_orders_public_token` (`public_token`),
    KEY `idx_orders_user_id` (`user_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- order_statuses (the actual built timeline for a specific order —
-- a snapshot copy from `statuses`, not a live dependency)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_statuses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `status_id` INT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `expected_duration_value` DECIMAL(6,2) NULL DEFAULT NULL,
    `expected_duration_unit` ENUM('instant','days','weeks','months') NOT NULL DEFAULT 'instant',
    `sort_order` INT NOT NULL DEFAULT 0,
    `expected_date` DATE NULL DEFAULT NULL,
    `actual_date` DATE NULL DEFAULT NULL,
    `custom_message` VARCHAR(255) NULL DEFAULT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_statuses_order_id` (`order_id`),
    CONSTRAINT `fk_order_statuses_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_statuses_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- settings (small global key/value store — e.g. the typical overall
-- order duration shown as a reference note, not used in calculations)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` VARCHAR(255) NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
