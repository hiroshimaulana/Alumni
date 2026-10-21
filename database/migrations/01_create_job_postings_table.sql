-- Istanbul University MIS Alumni Portal
-- Migration: 01_create_job_postings_table.sql
-- Description: Creates the job_postings table for the Career Services module

CREATE TABLE IF NOT EXISTS `job_postings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `posted_by_user_id` INT NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `company_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `employment_type` ENUM('full_time', 'part_time', 'internship', 'contract', 'remote') NOT NULL DEFAULT 'full_time',
    `location` VARCHAR(150) NOT NULL,
    `salary_range` VARCHAR(100) NULL,
    `experience_level` ENUM('entry', 'mid', 'senior', 'lead', 'executive') NOT NULL DEFAULT 'mid',
    `status` ENUM('draft', 'active', 'closed', 'archived') NOT NULL DEFAULT 'active',
    `requirements` TEXT NULL,
    `deadline` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_job_postings_posted_by` FOREIGN KEY (`posted_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_job_postings_status` (`status`),
    INDEX `idx_job_postings_company_id` (`company_id`),
    INDEX `idx_job_postings_posted_by` (`posted_by_user_id`),
    INDEX `idx_job_postings_employment_type` (`employment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
