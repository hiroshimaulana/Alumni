<?php
/**
 * Istanbul University MIS Alumni Portal
 * REST API Job Posting Controller
 *
 * Implements RESTful JSON CRUD endpoints and nested route handlers
 * for JobPosting entities.
 */

require_once __DIR__ . '/../models/JobPosting.php';

class ApiJobPostingController {
    /**
     * Send structured JSON response.
     */
    private function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * List all job postings (supports query filters).
     * Route: GET /api/job-postings
     */
    public function index(): void {
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }
        if (!empty($_GET['company_id'])) {
            $filters['company_id'] = (int)$_GET['company_id'];
        }
        if (!empty($_GET['employment_type'])) {
            $filters['employment_type'] = trim($_GET['employment_type']);
        }
        if (!empty($_GET['experience_level'])) {
            $filters['experience_level'] = trim($_GET['experience_level']);
        }
        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }

        $jobs = JobPosting::all($filters);
        $this->jsonResponse([
            'status' => 'success',
            'count'  => count($jobs),
            'data'   => $jobs
        ]);
    }

    /**
     * Get a specific job posting by ID.
     * Route: GET /api/job-postings/{id}
     */
    public function show(int $id): void {
        $job = JobPosting::findById($id);

        if ($job === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Job posting with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'data'   => $job
        ]);
    }

    /**
     * Create a new job posting.
     * Route: POST /api/job-postings
     */
    public function store(array $payload): void {
        try {
            $job = JobPosting::create($payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Job posting created successfully',
                'data'    => $job
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an existing job posting.
     * Route: PUT /api/job-postings/{id}
     */
    public function update(int $id, array $payload): void {
        if (JobPosting::findById($id) === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Job posting with ID {$id} not found"
            ], 404);
        }

        try {
            $updated = JobPosting::update($id, $payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Job posting updated successfully',
                'data'    => $updated
            ], 200);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an existing job posting.
     * Route: DELETE /api/job-postings/{id}
     */
    public function destroy(int $id): void {
        $deleted = JobPosting::delete($id);

        if (!$deleted) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Job posting with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status'  => 'success',
            'message' => "Job posting with ID {$id} deleted successfully"
        ], 200);
    }

    // -------------------------------------------------------------
    // Nested Route Endpoints
    // -------------------------------------------------------------

    /**
     * Get all job postings for a specific company.
     * Route: GET /api/companies/{id}/job-postings
     */
    public function byCompany(int $id): void {
        $jobs = JobPosting::findByCompany($id);
        $this->jsonResponse([
            'status'     => 'success',
            'company_id' => $id,
            'count'      => count($jobs),
            'data'       => $jobs
        ]);
    }

    /**
     * Get all job postings created by a specific user.
     * Route: GET /api/users/{id}/job-postings
     */
    public function byUser(int $id): void {
        $jobs = JobPosting::findByUser($id);
        $this->jsonResponse([
            'status'  => 'success',
            'user_id' => $id,
            'count'   => count($jobs),
            'data'    => $jobs
        ]);
    }
}
