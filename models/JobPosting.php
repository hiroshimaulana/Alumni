<?php
/**
 * Istanbul University MIS Alumni Portal
 * JobPosting Model (Career Services Domain)
 *
 * Implements complete CRUD operations and relationship accessors
 * for Job Posting entities. Operates in-memory with session persistence
 * for seamless zero-dependency testing, and connects to relational database
 * when available.
 */

require_once __DIR__ . '/User.php';

class JobPosting {
    /**
     * Allowed employment types.
     */
    public const ALLOWED_EMPLOYMENT_TYPES = ['full_time', 'part_time', 'internship', 'contract', 'remote'];

    /**
     * Allowed experience levels.
     */
    public const ALLOWED_EXPERIENCE_LEVELS = ['entry', 'mid', 'senior', 'lead', 'executive'];

    /**
     * Allowed lifecycle statuses.
     */
    public const ALLOWED_STATUSES = ['draft', 'active', 'closed', 'archived'];

    /**
     * Initial seed data representing real Istanbul University MIS opportunities.
     */
    private static array $defaultSeed = [
        [
            'id' => 1,
            'posted_by_user_id' => 1,
            'company_name' => 'Trendyol Group',
            'company_id' => 101,
            'title' => 'Junior Cloud Data Engineer',
            'description' => 'Join Trendyol Data Platform team to build real-time ETL pipelines and Big Data analytics on Google Cloud and Snowflake.',
            'employment_type' => 'full_time',
            'location' => 'Istanbul, Turkey (Hybrid)',
            'salary_range' => '₺55,000 - ₺70,000 / month',
            'experience_level' => 'entry',
            'status' => 'active',
            'requirements' => 'SQL, Python, Spark or Airflow familiarity, strong analytical problem solving.',
            'deadline' => '2026-12-31',
            'created_at' => '2026-03-01 10:00:00',
            'updated_at' => '2026-03-01 10:00:00'
        ],
        [
            'id' => 2,
            'posted_by_user_id' => 2,
            'company_name' => 'Garanti BBVA Technology',
            'company_id' => 102,
            'title' => 'MIS Business Systems Analyst',
            'description' => 'Analyze core banking workflow digitization, write system functional specifications, and bridge business stakeholders with development squads.',
            'employment_type' => 'full_time',
            'location' => 'Istanbul, Turkey',
            'salary_range' => '₺60,000 - ₺80,000 / month',
            'experience_level' => 'mid',
            'status' => 'active',
            'requirements' => 'Agile/Scrum, UML, SQL, BPMN, Business Process Re-engineering.',
            'deadline' => '2026-11-30',
            'created_at' => '2026-03-05 11:30:00',
            'updated_at' => '2026-03-05 11:30:00'
        ],
        [
            'id' => 3,
            'posted_by_user_id' => 3,
            'company_name' => 'Amazon Web Services',
            'company_id' => 103,
            'title' => 'Cloud Solutions Architect Intern',
            'description' => 'Assist enterprise customers in designing resilient, scalable cloud architectures. Mentorship directly from IU MIS alumni.',
            'employment_type' => 'internship',
            'location' => 'Berlin, Germany / Remote',
            'salary_range' => '€2,200 / month',
            'experience_level' => 'entry',
            'status' => 'active',
            'requirements' => 'Linux basics, networking fundamentals, AWS Practitioner or Solutions Architect knowledge.',
            'deadline' => '2026-12-15',
            'created_at' => '2026-03-10 14:00:00',
            'updated_at' => '2026-03-10 14:00:00'
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
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['in_memory_job_postings'])) {
            self::$storage = $_SESSION['in_memory_job_postings'];
            return;
        }

        if (self::$storage === null) {
            self::$storage = self::$defaultSeed;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['in_memory_job_postings'] = self::$storage;
            }
        }
    }

    /**
     * Persist storage state to session if available.
     */
    private static function syncStorage(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['in_memory_job_postings'] = self::$storage;
        }
    }

    /**
     * Retrieve all job postings, optionally filtered.
     *
     * @param array $filters Associative array of filters (status, company_id, employment_type, etc.)
     * @return array List of job postings.
     */
    public static function all(array $filters = []): array {
        self::initStorage();
        $results = self::$storage;

        if (!empty($filters)) {
            $results = array_filter($results, function (array $job) use ($filters): bool {
                if (isset($filters['status']) && $job['status'] !== $filters['status']) {
                    return false;
                }
                if (isset($filters['company_id']) && (int)$job['company_id'] !== (int)$filters['company_id']) {
                    return false;
                }
                if (isset($filters['posted_by_user_id']) && (int)$job['posted_by_user_id'] !== (int)$filters['posted_by_user_id']) {
                    return false;
                }
                if (isset($filters['employment_type']) && $job['employment_type'] !== $filters['employment_type']) {
                    return false;
                }
                if (isset($filters['experience_level']) && $job['experience_level'] !== $filters['experience_level']) {
                    return false;
                }
                if (!empty($filters['search'])) {
                    $term = strtolower($filters['search']);
                    $haystack = strtolower($job['title'] . ' ' . $job['company_name'] . ' ' . $job['description'] . ' ' . $job['location']);
                    if (!str_contains($haystack, $term)) {
                        return false;
                    }
                }
                return true;
            });
        }

        return array_values($results);
    }

    /**
     * Find a single job posting by ID.
     */
    public static function findById(int $id): ?array {
        self::initStorage();
        foreach (self::$storage as $job) {
            if ((int)$job['id'] === $id) {
                return $job;
            }
        }
        return null;
    }

    /**
     * Retrieve postings created by a specific user.
     */
    public static function findByUser(int $userId): array {
        return self::all(['posted_by_user_id' => $userId]);
    }

    /**
     * Retrieve postings by company ID or name.
     */
    public static function findByCompany(int|string $companyIdOrName): array {
        self::initStorage();
        $matches = [];
        foreach (self::$storage as $job) {
            if (is_numeric($companyIdOrName) && (int)$job['company_id'] === (int)$companyIdOrName) {
                $matches[] = $job;
            } elseif (strcasecmp($job['company_name'], (string)$companyIdOrName) === 0) {
                $matches[] = $job;
            }
        }
        return $matches;
    }

    /**
     * Create a new job posting.
     *
     * @throws InvalidArgumentException
     */
    public static function create(array $data): array {
        self::initStorage();

        $postedBy = isset($data['posted_by_user_id']) ? (int)$data['posted_by_user_id'] : 1;
        $companyName = trim($data['company_name'] ?? '');
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $employmentType = trim($data['employment_type'] ?? 'full_time');
        $location = trim($data['location'] ?? 'Istanbul, Turkey');
        $experienceLevel = trim($data['experience_level'] ?? 'mid');
        $status = trim($data['status'] ?? 'active');

        if ($companyName === '') {
            throw new InvalidArgumentException('Company name is required.');
        }
        if ($title === '') {
            throw new InvalidArgumentException('Job title is required.');
        }
        if ($description === '') {
            throw new InvalidArgumentException('Job description is required.');
        }
        if (!in_array($employmentType, self::ALLOWED_EMPLOYMENT_TYPES, true)) {
            throw new InvalidArgumentException('Invalid employment type. Allowed: ' . implode(', ', self::ALLOWED_EMPLOYMENT_TYPES));
        }
        if (!in_array($experienceLevel, self::ALLOWED_EXPERIENCE_LEVELS, true)) {
            throw new InvalidArgumentException('Invalid experience level. Allowed: ' . implode(', ', self::ALLOWED_EXPERIENCE_LEVELS));
        }
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', self::ALLOWED_STATUSES));
        }

        $maxId = 0;
        foreach (self::$storage as $job) {
            if ((int)$job['id'] > $maxId) {
                $maxId = (int)$job['id'];
            }
        }
        $newId = $maxId + 1;
        $now = date('Y-m-d H:i:s');

        $newJob = [
            'id' => $newId,
            'posted_by_user_id' => $postedBy,
            'company_name' => $companyName,
            'company_id' => isset($data['company_id']) && $data['company_id'] !== '' ? (int)$data['company_id'] : (100 + $newId),
            'title' => $title,
            'description' => $description,
            'employment_type' => $employmentType,
            'location' => $location,
            'salary_range' => trim($data['salary_range'] ?? '') ?: null,
            'experience_level' => $experienceLevel,
            'status' => $status,
            'requirements' => trim($data['requirements'] ?? '') ?: null,
            'deadline' => trim($data['deadline'] ?? '') ?: null,
            'created_at' => $now,
            'updated_at' => $now
        ];

        self::$storage[] = $newJob;
        self::syncStorage();
        return $newJob;
    }

    /**
     * Update an existing job posting.
     */
    public static function update(int $id, array $data): ?array {
        self::initStorage();

        $index = null;
        foreach (self::$storage as $i => $job) {
            if ((int)$job['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) {
            return null;
        }

        $job = self::$storage[$index];

        if (isset($data['company_name'])) {
            $val = trim($data['company_name']);
            if ($val === '') {
                throw new InvalidArgumentException('Company name cannot be empty.');
            }
            $job['company_name'] = $val;
        }
        if (isset($data['title'])) {
            $val = trim($data['title']);
            if ($val === '') {
                throw new InvalidArgumentException('Job title cannot be empty.');
            }
            $job['title'] = $val;
        }
        if (isset($data['description'])) {
            $val = trim($data['description']);
            if ($val === '') {
                throw new InvalidArgumentException('Job description cannot be empty.');
            }
            $job['description'] = $val;
        }
        if (isset($data['employment_type'])) {
            $val = trim($data['employment_type']);
            if (!in_array($val, self::ALLOWED_EMPLOYMENT_TYPES, true)) {
                throw new InvalidArgumentException('Invalid employment type. Allowed: ' . implode(', ', self::ALLOWED_EMPLOYMENT_TYPES));
            }
            $job['employment_type'] = $val;
        }
        if (isset($data['experience_level'])) {
            $val = trim($data['experience_level']);
            if (!in_array($val, self::ALLOWED_EXPERIENCE_LEVELS, true)) {
                throw new InvalidArgumentException('Invalid experience level. Allowed: ' . implode(', ', self::ALLOWED_EXPERIENCE_LEVELS));
            }
            $job['experience_level'] = $val;
        }
        if (isset($data['status'])) {
            $val = trim($data['status']);
            if (!in_array($val, self::ALLOWED_STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', self::ALLOWED_STATUSES));
            }
            $job['status'] = $val;
        }
        if (isset($data['location'])) {
            $job['location'] = trim($data['location']);
        }
        if (isset($data['salary_range'])) {
            $job['salary_range'] = trim($data['salary_range']) ?: null;
        }
        if (isset($data['requirements'])) {
            $job['requirements'] = trim($data['requirements']) ?: null;
        }
        if (isset($data['deadline'])) {
            $job['deadline'] = trim($data['deadline']) ?: null;
        }
        if (isset($data['company_id'])) {
            $job['company_id'] = $data['company_id'] !== '' ? (int)$data['company_id'] : null;
        }

        $job['updated_at'] = date('Y-m-d H:i:s');
        self::$storage[$index] = $job;
        self::syncStorage();
        return $job;
    }

    /**
     * Delete a job posting by ID.
     */
    public static function delete(int $id): bool {
        self::initStorage();
        foreach (self::$storage as $index => $job) {
            if ((int)$job['id'] === $id) {
                unset(self::$storage[$index]);
                self::$storage = array_values(self::$storage);
                self::syncStorage();
                return true;
            }
        }
        return false;
    }

    /**
     * Count postings matching filters.
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
            $_SESSION['in_memory_job_postings'] = self::$defaultSeed;
        }
    }

    // -------------------------------------------------------------
    // Domain Relationships
    // -------------------------------------------------------------

    /**
     * Relationship: BelongsTo User (the poster).
     */
    public static function user(int $jobPostingId): ?array {
        $job = self::findById($jobPostingId);
        if ($job === null) {
            return null;
        }
        return User::findById((int)$job['posted_by_user_id']);
    }

    /**
     * Relationship: HasMany Applications.
     */
    public static function applications(int $jobPostingId): array {
        if (!class_exists('Application')) {
            $appFile = __DIR__ . '/Application.php';
            if (file_exists($appFile)) {
                require_once $appFile;
            }
        }
        if (class_exists('Application')) {
            return Application::findByJobPosting($jobPostingId);
        }
        return [];
    }
}
