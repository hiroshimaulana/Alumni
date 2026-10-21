<?php
/**
 * Istanbul University MIS Alumni Portal
 * JobPostingSeeder
 *
 * Populates realistic job postings and applications into the database
 * or in-memory model storage using factories.
 */

require_once __DIR__ . '/../../models/JobPosting.php';
require_once __DIR__ . '/../../models/Application.php';
require_once __DIR__ . '/../factories/JobPostingFactory.php';
require_once __DIR__ . '/../factories/ApplicationFactory.php';
require_once __DIR__ . '/../../config/db.php';

class JobPostingSeeder {
    public function run(): array {
        // Reset in-memory storages
        JobPosting::reset();
        Application::reset();

        $pdo = getDbConnection();
        $dbInsertedJobs = 0;
        $dbInsertedApps = 0;

        // Generate 6 realistic job opportunities from factory
        $jobs = JobPostingFactory::count(6);
        foreach ($jobs as $jobData) {
            // Check if already in memory
            $existing = JobPosting::findByCompany($jobData['company_name']);
            $alreadyExists = false;
            foreach ($existing as $e) {
                if ($e['title'] === $jobData['title']) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (!$alreadyExists) {
                JobPosting::create($jobData);
            }

            // If MySQL is online, populate MySQL table as well
            if ($pdo !== null) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO `job_postings` 
                        (`posted_by_user_id`, `company_name`, `company_id`, `title`, `description`, `employment_type`, `location`, `salary_range`, `experience_level`, `status`, `requirements`, `deadline`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE `title` = VALUES(`title`)
                    ");
                    $stmt->execute([
                        $jobData['posted_by_user_id'],
                        $jobData['company_name'],
                        $jobData['company_id'],
                        $jobData['title'],
                        $jobData['description'],
                        $jobData['employment_type'],
                        $jobData['location'],
                        $jobData['salary_range'],
                        $jobData['experience_level'],
                        $jobData['status'],
                        $jobData['requirements'],
                        $jobData['deadline'],
                    ]);
                    $dbInsertedJobs++;
                } catch (PDOException $e) {
                    // Silently ignore or log duplicate constraints
                }
            }
        }

        // Generate applications
        for ($i = 0; $i < 3; $i++) {
            $appData = ApplicationFactory::definition($i);
            try {
                Application::create($appData);
            } catch (InvalidArgumentException $e) {
                // Ignore duplicate applications
            }

            if ($pdo !== null) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO `applications`
                        (`job_posting_id`, `user_id`, `applicant_name`, `applicant_email`, `resume_url`, `cover_letter`, `portfolio_url`, `status`, `feedback`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE `status` = VALUES(`status`)
                    ");
                    $stmt->execute([
                        $appData['job_posting_id'],
                        $appData['user_id'],
                        $appData['applicant_name'],
                        $appData['applicant_email'],
                        $appData['resume_url'],
                        $appData['cover_letter'],
                        $appData['portfolio_url'],
                        $appData['status'],
                        $appData['feedback'],
                    ]);
                    $dbInsertedApps++;
                } catch (PDOException $e) {
                    // Silently ignore
                }
            }
        }

        return [
            'jobs_count' => JobPosting::count(),
            'apps_count' => Application::count(),
            'db_jobs'    => $dbInsertedJobs,
            'db_apps'    => $dbInsertedApps,
        ];
    }
}
