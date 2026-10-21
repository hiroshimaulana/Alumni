-- Istanbul University MIS Alumni Portal
-- Migration: 02_create_applications_table.sql
-- Description: Creates the applications table linking applicants to job postings

CREATE TABLE IF NOT EXISTS `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_posting_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `applicant_name` VARCHAR(150) NOT NULL,
    `applicant_email` VARCHAR(255) NOT NULL,
    `resume_url` VARCHAR(255) NULL,
    `cover_letter` TEXT NULL,
    `portfolio_url` VARCHAR(255) NULL,
    `status` ENUM('submitted', 'under_review', 'shortlisted', 'rejected', 'accepted') NOT NULL DEFAULT 'submitted',
    `feedback` TEXT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uniq_user_job_application` (`job_posting_id`, `user_id`),
    INDEX `idx_applications_job_id` (`job_posting_id`),
    INDEX `idx_applications_user_id` (`user_id`),
    INDEX `idx_applications_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
