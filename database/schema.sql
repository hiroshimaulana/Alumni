-- Istanbul University MIS Alumni Portal
-- Database Schema & Initial Seed Data

CREATE DATABASE IF NOT EXISTS `iu_mis_alumni` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iu_mis_alumni`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `role` ENUM('super_admin', 'faculty_admin', 'alumni', 'student') NOT NULL DEFAULT 'alumni',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alumni Profiles Table
CREATE TABLE IF NOT EXISTS `alumni_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `student_id` VARCHAR(50) NULL,
    `graduation_year` INT NOT NULL,
    `degree_level` ENUM('B.Sc.', 'M.Sc.', 'Ph.D.') DEFAULT 'B.Sc.',
    `current_company` VARCHAR(255) NULL,
    `current_title` VARCHAR(255) NULL,
    `industry` VARCHAR(100) NULL,
    `location` VARCHAR(100) NULL,
    `is_available_for_mentoring` TINYINT(1) DEFAULT 0,
    `bio` TEXT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Sample Alumni Data
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_verified`) VALUES
(1, 'alumni.mert@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Mert', 'Yılmaz', 'alumni', 1),
(2, 'alumni.zeynep@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Zeynep', 'Kaya', 'alumni', 1),
(3, 'alumni.senol@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Şenol', 'Demir', 'alumni', 1)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `alumni_profiles` (`user_id`, `graduation_year`, `degree_level`, `current_company`, `current_title`, `industry`, `location`, `is_available_for_mentoring`) VALUES
(1, 2020, 'B.Sc.', 'Trendyol Group', 'Senior Cloud Data Engineer', 'E-Commerce / Cloud', 'Istanbul, Turkey', 1),
(2, 2018, 'B.Sc.', 'Garanti BBVA Technology', 'Lead Business Analyst', 'FinTech / Banking', 'Istanbul, Turkey', 1),
(3, 2022, 'B.Sc.', 'Amazon Web Services', 'Solutions Architect', 'Cloud Computing', 'Berlin, Germany', 1)
ON DUPLICATE KEY UPDATE `current_company` = VALUES(`current_company`);
