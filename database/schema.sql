-- Istanbul University MIS Alumni Portal
-- Database Schema & Initial Seed Data

CREATE DATABASE IF NOT EXISTS `iu_mis_alumni` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iu_mis_alumni`;

-- 1. Users Table
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

-- 2. Alumni Profiles Table
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

-- 3. Job Postings Table
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

-- 4. Applications Table
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

-- -------------------------------------------------------------
-- Seed Sample Data
-- -------------------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `is_verified`) VALUES
(1, 'alumni.mert@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Mert', 'Yılmaz', 'alumni', 1),
(2, 'alumni.zeynep@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Zeynep', 'Kaya', 'alumni', 1),
(3, 'alumni.senol@example.com', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Şenol', 'Demir', 'alumni', 1),
(4, 'student.ayse@ogr.iu.edu.tr', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Ayşe', 'Öztürk', 'student', 1),
(5, 'student.can@ogr.iu.edu.tr', '$2y$10$w6D4oU1uP1E6fS6d...sample', 'Can', 'Aydın', 'student', 1)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `alumni_profiles` (`user_id`, `graduation_year`, `degree_level`, `current_company`, `current_title`, `industry`, `location`, `is_available_for_mentoring`) VALUES
(1, 2020, 'B.Sc.', 'Trendyol Group', 'Senior Cloud Data Engineer', 'E-Commerce / Cloud', 'Istanbul, Turkey', 1),
(2, 2018, 'B.Sc.', 'Garanti BBVA Technology', 'Lead Business Analyst', 'FinTech / Banking', 'Istanbul, Turkey', 1),
(3, 2022, 'B.Sc.', 'Amazon Web Services', 'Solutions Architect', 'Cloud Computing', 'Berlin, Germany', 1)
ON DUPLICATE KEY UPDATE `current_company` = VALUES(`current_company`);

INSERT INTO `job_postings` (`id`, `posted_by_user_id`, `company_name`, `company_id`, `title`, `description`, `employment_type`, `location`, `salary_range`, `experience_level`, `status`, `requirements`, `deadline`) VALUES
(1, 1, 'Trendyol Group', 101, 'Junior Cloud Data Engineer', 'Join Trendyol Data Platform team to build real-time ETL pipelines and Big Data analytics on Google Cloud and Snowflake.', 'full_time', 'Istanbul, Turkey (Hybrid)', '₺55,000 - ₺70,000 / month', 'entry', 'active', 'SQL, Python, Spark or Airflow familiarity, strong analytical problem solving.', '2026-12-31'),
(2, 2, 'Garanti BBVA Technology', 102, 'MIS Business Systems Analyst', 'Analyze core banking workflow digitization, write system functional specifications, and bridge business stakeholders with development squads.', 'full_time', 'Istanbul, Turkey', '₺60,000 - ₺80,000 / month', 'mid', 'active', 'Agile/Scrum, UML, SQL, BPMN, Business Process Re-engineering.', '2026-11-30'),
(3, 3, 'Amazon Web Services', 103, 'Cloud Solutions Architect Intern', 'Assist enterprise customers in designing resilient, scalable cloud architectures. Mentorship directly from IU MIS alumni.', 'internship', 'Berlin, Germany / Remote', '€2,200 / month', 'entry', 'active', 'Linux basics, networking fundamentals, AWS Practitioner or Solutions Architect knowledge.', '2026-12-15')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `applications` (`id`, `job_posting_id`, `user_id`, `applicant_name`, `applicant_email`, `resume_url`, `cover_letter`, `portfolio_url`, `status`, `feedback`) VALUES
(1, 1, 4, 'Ayşe Öztürk', 'student.ayse@ogr.iu.edu.tr', '/uploads/resumes/ayse_ozturk_cv.pdf', 'I am an IU MIS senior student specialized in Data Engineering with coursework in Big Data systems.', 'https://github.com/ayseozturk', 'under_review', 'Strong academic track record in database systems and Python.'),
(2, 2, 5, 'Can Aydın', 'student.can@ogr.iu.edu.tr', '/uploads/resumes/can_aydin_cv.pdf', 'Passionate about FinTech and Enterprise ERP systems. Looking forward to contributing to Garanti BBVA Technology.', 'https://linkedin.com/in/canaydin', 'shortlisted', 'Passed technical phone screening. Scheduling interview with team lead.')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);
