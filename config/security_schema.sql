-- Security Tables Schema
-- Run these SQL commands in your database to enable security features

-- Audit Logging Table
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` LONGTEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `severity` ENUM('INFO', 'WARNING', 'CRITICAL') DEFAULT 'INFO',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_severity` (`severity`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Breaches Table
CREATE TABLE IF NOT EXISTS `security_breaches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `breach_type` VARCHAR(100) NOT NULL,
  `details` LONGTEXT,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `status` ENUM('OPEN', 'INVESTIGATING', 'RESOLVED', 'FALSE_ALARM') DEFAULT 'OPEN',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL,
  `admin_notes` TEXT,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Access Log Table
CREATE TABLE IF NOT EXISTS `api_access_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `db_id` INT,
  `ip_address` VARCHAR(45),
  `status_code` INT,
  `response_time_ms` INT,
  `request_size` INT,
  `response_size` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_endpoint` (`endpoint`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Failed Login Attempts Table
CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `reason` VARCHAR(100),
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_ip_address` (`ip_address`),
  INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IP Whitelist/Blacklist Table
CREATE TABLE IF NOT EXISTS `ip_restrictions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `ip_address` VARCHAR(45) NOT NULL,
  `restriction_type` ENUM('WHITELIST', 'BLACKLIST') DEFAULT 'WHITELIST',
  `reason` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_ip_address` (`ip_address`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User API Keys Table (for token management)
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `token_hash` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255),
  `last_used` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_token` (`token_hash`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add security fields to users table (if not already present)
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login_ip` VARCHAR(45);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login_at` TIMESTAMP NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `login_attempts` INT DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `account_locked_until` TIMESTAMP NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `two_factor_enabled` BOOLEAN DEFAULT FALSE;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `two_factor_secret` VARCHAR(255);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `security_questions` JSON;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `backup_codes` JSON;

-- Create database access audit table
CREATE TABLE IF NOT EXISTS `database_access_audit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `db_id` INT NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `data_size` INT,
  `rows_affected` INT,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_db_id` (`db_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`db_id`) REFERENCES `databases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
