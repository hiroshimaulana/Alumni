<?php
/**
 * Istanbul University MIS Alumni Portal
 * REST API Application Controller
 *
 * Implements RESTful JSON CRUD endpoints and nested route handlers
 * for Application entities.
 */

require_once __DIR__ . '/../models/Application.php';
require_once __DIR__ . '/../models/JobPosting.php';

class ApiApplicationController {
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
     * List all applications (supports query filters).
     * Route: GET /api/applications
     */
    public function index(): void {
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }
        if (!empty($_GET['job_posting_id'])) {
            $filters['job_posting_id'] = (int)$_GET['job_posting_id'];
        }
        if (!empty($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }

        $apps = Application::all($filters);
        $this->jsonResponse([
            'status' => 'success',
            'count'  => count($apps),
            'data'   => $apps
        ]);
    }

    /**
     * Get a specific application by ID.
     * Route: GET /api/applications/{id}
     */
    public function show(int $id): void {
        $app = Application::findById($id);

        if ($app === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Application with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'data'   => $app
        ]);
    }

    /**
     * Create a new application.
     * Route: POST /api/applications
     */
    public function store(array $payload): void {
        try {
            $app = Application::create($payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Application submitted successfully',
                'data'    => $app
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an existing application.
     * Route: PUT /api/applications/{id}
     */
    public function update(int $id, array $payload): void {
        if (Application::findById($id) === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Application with ID {$id} not found"
            ], 404);
        }

        try {
            $updated = Application::update($id, $payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Application updated successfully',
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
     * Delete an existing application.
     * Route: DELETE /api/applications/{id}
     */
    public function destroy(int $id): void {
        $deleted = Application::delete($id);

        if (!$deleted) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Application with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status'  => 'success',
            'message' => "Application with ID {$id} deleted successfully"
        ], 200);
    }

    // -------------------------------------------------------------
    // Nested Route Endpoints
    // -------------------------------------------------------------

    /**
     * Get all applications for a specific job posting.
     * Route: GET /api/job-postings/{id}/applications
     */
    public function byJobPosting(int $id): void {
        $job = JobPosting::findById($id);
        if ($job === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "Job posting with ID {$id} not found"
            ], 404);
        }

        $apps = Application::findByJobPosting($id);
        $this->jsonResponse([
            'status'         => 'success',
            'job_posting_id' => $id,
            'job_title'      => $job['title'],
            'count'          => count($apps),
            'data'           => $apps
        ]);
    }

    /**
     * Submit an application directly for a specific job posting.
     * Route: POST /api/job-postings/{id}/applications
     */
    public function apply(int $id, array $payload): void {
        $payload['job_posting_id'] = $id;
        $this->store($payload);
    }

    /**
     * Get all applications submitted by a specific user.
     * Route: GET /api/users/{id}/applications
     */
    public function byUser(int $id): void {
        $apps = Application::findByUser($id);
        $this->jsonResponse([
            'status'  => 'success',
            'user_id' => $id,
            'count'   => count($apps),
            'data'    => $apps
        ]);
    }
}
