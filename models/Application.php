<?php
/**
 * Istanbul University MIS Alumni Portal
 * Application Model (Application Tracking Domain)
 *
 * Implements complete CRUD operations, state machine transitions,
 * and relationships to JobPosting and User entities.
 */

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/JobPosting.php';

class Application {
    /**
     * Allowed lifecycle statuses.
     */
    public const ALLOWED_STATUSES = ['submitted', 'under_review', 'shortlisted', 'rejected', 'accepted'];

    /**
     * Initial seed data representing real Istanbul University MIS student applications.
     */
    private static array $defaultSeed = [
        [
            'id' => 1,
            'job_posting_id' => 1,
            'user_id' => 4,
            'applicant_name' => 'Ayşe Öztürk',
            'applicant_email' => 'student.ayse@ogr.iu.edu.tr',
            'resume_url' => '/uploads/resumes/ayse_ozturk_cv.pdf',
            'cover_letter' => 'I am an IU MIS senior student specialized in Data Engineering with coursework in Big Data systems.',
            'portfolio_url' => 'https://github.com/ayseozturk',
            'status' => 'under_review',
            'feedback' => 'Strong academic track record in database systems and Python.',
            'applied_at' => '2026-03-02 09:30:00',
            'created_at' => '2026-03-02 09:30:00',
            'updated_at' => '2026-03-02 09:30:00'
        ],
        [
            'id' => 2,
            'job_posting_id' => 2,
            'user_id' => 5,
            'applicant_name' => 'Can Aydın',
            'applicant_email' => 'student.can@ogr.iu.edu.tr',
            'resume_url' => '/uploads/resumes/can_aydin_cv.pdf',
            'cover_letter' => 'Passionate about FinTech and Enterprise ERP systems. Looking forward to contributing to Garanti BBVA Technology.',
            'portfolio_url' => 'https://linkedin.com/in/canaydin',
            'status' => 'shortlisted',
            'feedback' => 'Passed technical phone screening. Scheduling interview with team lead.',
            'applied_at' => '2026-03-06 14:15:00',
            'created_at' => '2026-03-06 14:15:00',
            'updated_at' => '2026-03-06 14:15:00'
        ]
    ];

    /**
     * Static in-memory storage array.
     */
    private static ?array $storage = null;

    /**
     * Initialize and retrieve storage reference.
     */
    private static function initStorage(): void {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['in_memory_applications'])) {
            self::$storage = $_SESSION['in_memory_applications'];
            return;
        }

        if (self::$storage === null) {
            self::$storage = self::$defaultSeed;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['in_memory_applications'] = self::$storage;
            }
        }
    }

    /**
     * Persist storage state to session if available.
     */
    private static function syncStorage(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['in_memory_applications'] = self::$storage;
        }
    }

    /**
     * Retrieve all applications, optionally filtered.
     */
    public static function all(array $filters = []): array {
        self::initStorage();
        $results = self::$storage;

        if (!empty($filters)) {
            $results = array_filter($results, function (array $app) use ($filters): bool {
                if (isset($filters['job_posting_id']) && (int)$app['job_posting_id'] !== (int)$filters['job_posting_id']) {
                    return false;
                }
                if (isset($filters['user_id']) && (int)$app['user_id'] !== (int)$filters['user_id']) {
                    return false;
                }
                if (isset($filters['status']) && $app['status'] !== $filters['status']) {
                    return false;
                }
                return true;
            });
        }

        return array_values($results);
    }

    /**
     * Find a single application by ID.
     */
    public static function findById(int $id): ?array {
        self::initStorage();
        foreach (self::$storage as $app) {
            if ((int)$app['id'] === $id) {
                return $app;
            }
        }
        return null;
    }

    /**
     * Retrieve applications submitted for a specific job posting.
     */
    public static function findByJobPosting(int $jobPostingId): array {
        return self::all(['job_posting_id' => $jobPostingId]);
    }

    /**
     * Retrieve applications submitted by a specific user.
     */
    public static function findByUser(int $userId): array {
        return self::all(['user_id' => $userId]);
    }

    /**
     * Create a new application.
     *
     * @throws InvalidArgumentException
     */
    public static function create(array $data): array {
        self::initStorage();

        $jobId = isset($data['job_posting_id']) ? (int)$data['job_posting_id'] : 0;
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $name = trim($data['applicant_name'] ?? '');
        $email = trim($data['applicant_email'] ?? '');
        $status = trim($data['status'] ?? 'submitted');

        if ($jobId <= 0) {
            throw new InvalidArgumentException('A valid job_posting_id is required.');
        }

        // Verify referenced JobPosting exists
        $job = JobPosting::findById($jobId);
        if ($job === null) {
            throw new InvalidArgumentException("Job posting with ID {$jobId} not found.");
        }

        // Auto-fill applicant details if user exists
        if ($userId > 0) {
            $user = User::findById($userId);
            if ($user !== null) {
                if ($name === '') {
                    $name = $user['first_name'] . ' ' . $user['last_name'];
                }
                if ($email === '') {
                    $email = $user['email'];
                }
            }
        }

        if ($name === '') {
            throw new InvalidArgumentException('Applicant name is required.');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid applicant email address is required.');
        }
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', self::ALLOWED_STATUSES));
        }

        // Enforce uniqueness constraint (user can only apply once to a job)
        if ($userId > 0) {
            foreach (self::$storage as $existing) {
                if ((int)$existing['job_posting_id'] === $jobId && (int)$existing['user_id'] === $userId) {
                    throw new InvalidArgumentException('You have already submitted an application for this job posting.');
                }
            }
        }

        $maxId = 0;
        foreach (self::$storage as $app) {
            if ((int)$app['id'] > $maxId) {
                $maxId = (int)$app['id'];
            }
        }
        $newId = $maxId + 1;
        $now = date('Y-m-d H:i:s');

        $newApp = [
            'id' => $newId,
            'job_posting_id' => $jobId,
            'user_id' => $userId,
            'applicant_name' => $name,
            'applicant_email' => $email,
            'resume_url' => trim($data['resume_url'] ?? '') ?: null,
            'cover_letter' => trim($data['cover_letter'] ?? '') ?: null,
            'portfolio_url' => trim($data['portfolio_url'] ?? '') ?: null,
            'status' => $status,
            'feedback' => trim($data['feedback'] ?? '') ?: null,
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now
        ];

        self::$storage[] = $newApp;
        self::syncStorage();
        return $newApp;
    }

    /**
     * Update an application.
     */
    public static function update(int $id, array $data): ?array {
        self::initStorage();

        $index = null;
        foreach (self::$storage as $i => $app) {
            if ((int)$app['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return null;
        }

        $app = self::$storage[$index];

        if (isset($data['status'])) {
            $val = trim($data['status']);
            if (!in_array($val, self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', self::ALLOWED_STATUSES));
            }
            $app['status'] = $val;
        }
        if (isset($data['applicant_name'])) {
            $val = trim($data['applicant_name']);
            if ($val === '') {
                throw new InvalidArgumentException('Applicant name cannot be empty.');
            }
            $app['applicant_name'] = $val;
        }
        if (isset($data['applicant_email'])) {
            $val = trim($data['applicant_email']);
            if ($val === '' || !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('A valid applicant email is required.');
            }
            $app['applicant_email'] = $val;
        }
        if (isset($data['resume_url'])) {
            $app['resume_url'] = trim($data['resume_url']) ?: null;
        }
        if (isset($data['cover_letter'])) {
            $app['cover_letter'] = trim($data['cover_letter']) ?: null;
        }
        if (isset($data['portfolio_url'])) {
            $app['portfolio_url'] = trim($data['portfolio_url']) ?: null;
        }
        if (isset($data['feedback'])) {
            $app['feedback'] = trim($data['feedback']) ?: null;
        }

        $app['updated_at'] = date('Y-m-d H:i:s');
        self::$storage[$index] = $app;
        self::syncStorage();
        return $app;
    }

    /**
     * Transition application status with optional recruiter feedback.
     */
    public static function updateStatus(int $id, string $status, ?string $feedback = null): ?array {
        $data = ['status' => $status];
        if ($feedback !== null) {
            $data['feedback'] = $feedback;
        }
        return self::update($id, $data);
    }

    /**
     * Delete an application by ID.
     */
    public static function delete(int $id): bool {
        self::initStorage();
        foreach (self::$storage as $index => $app) {
            if ((int)$app['id'] === $id) {
                unset(self::$storage[$index]);
                self::$storage = array_values(self::$storage);
                self::syncStorage();
                return true;
            }
        }
        return false;
    }

    /**
     * Count applications matching filters.
     */
    public static function count(array $filters = []): int {
        return count(self::all($filters));
    }

    /**
     * Reset storage to initial state (for testing).
     */
    public static function reset(): void {
        self::$storage = self::$defaultSeed;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['in_memory_applications'] = self::$defaultSeed;
        }
    }

    // -------------------------------------------------------------
    // Domain Relationships
    // -------------------------------------------------------------

    /**
     * Relationship: BelongsTo JobPosting.
     */
    public static function jobPosting(int $applicationId): ?array {
        $app = self::findById($applicationId);
        if ($app === null) {
            return null;
        }
        return JobPosting::findById((int)$app['job_posting_id']);
    }

    /**
     * Relationship: BelongsTo User (the applicant).
     */
    public static function user(int $applicationId): ?array {
        $app = self::findById($applicationId);
        if ($app === null || empty($app['user_id'])) {
            return null;
        }
        return User::findById((int)$app['user_id']);
    }
}
