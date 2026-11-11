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

        if (is_array($data) && ($statusCode >= 400 || ($data['status'] ?? '') === 'error')) {
            $statusMap = [
                400 => '400 Bad Request',
                401 => '401 Unauthorized',
                403 => '403 Forbidden',
                404 => '404 Not Found',
                405 => '405 Method Not Allowed',
                422 => '422 Unprocessable Entity',
                500 => '500 Internal Server Error',
            ];
            $status = $data['status'] ?? 'error';
            $error = $data['error'] ?? ($statusMap[$statusCode] ?? "{$statusCode} Error");
            $message = $data['message'] ?? 'An error occurred with status code ' . $statusCode;
            $normalized = [
                'status'  => $status,
                'error'   => $error,
                'message' => $message,
            ];
            foreach ($data as $k => $v) {
                if (!in_array($k, ['status', 'error', 'message'], true)) {
                    $normalized[$k] = $v;
                }
            }
            $data = $normalized;
        }

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
     * Validate update/create payload fields for job postings.
     *
     * Checks only the fields that are present in the payload (partial-update
     * friendly): a field that is absent is left unchanged and not validated.
     * A field that IS present must pass its rule.
     *
     * Rules enforced:
     *   - title:  non-empty string
     *   - salary: positive numeric value (> 0), accepted as number or string
     *
     * @param array $payload  Decoded request body fields.
     * @return array          Associative array of field → error message, empty on success.
     */
    private function validateUpdatePayload(array $payload): array {
        $errors = [];

        if (array_key_exists('title', $payload)) {
            $title = $payload['title'];
            if (!is_string($title) || trim($title) === '') {
                $errors['title'] = 'Title cannot be null or empty';
            }
        }

        if (array_key_exists('salary', $payload)) {
            $salary = $payload['salary'];
            if (!is_numeric($salary) || (float)$salary <= 0) {
                $errors['salary'] = 'Salary must be a positive number';
            }
        }

        return $errors;
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

        // --- Field validation (422 Unprocessable Entity) ---
        $errors = $this->validateUpdatePayload($payload);
        if (!empty($errors)) {
            $this->jsonResponse([
                'status'  => 'error',
                'error'   => '422 Unprocessable Entity',
                'message' => 'Validation failed',
                'details' => $errors,
            ], 422);
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
