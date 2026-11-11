<?php
/**
 * Istanbul University MIS Alumni Portal
 * REST API User Controller
 *
 * Implements RESTful JSON CRUD endpoints for User entities
 * utilizing the in-memory User model.
 */

require_once __DIR__ . '/../models/User.php';

class ApiUserController {
    /**
     * Send structured JSON response.
     *
     * @param mixed $data Payload to encode.
     * @param int $statusCode HTTP response status code.
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
     * List all users.
     * Route: GET /api/users
     */
    public function index(): void {
        $users = User::all();
        $this->jsonResponse([
            'status' => 'success',
            'count'  => count($users),
            'data'   => $users
        ]);
    }

    /**
     * Get a specific user by ID.
     * Route: GET /api/users/{id}
     *
     * @param int $id User ID.
     */
    public function show(int $id): void {
        $user = User::findById($id);

        if ($user === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "User with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'data'   => $user
        ]);
    }

    /**
     * Create a new user.
     * Route: POST /api/users
     *
     * @param array $payload Request payload (JSON or form data).
     */
    public function store(array $payload): void {
        try {
            $user = User::create($payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'User created successfully',
                'data'    => $user
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an existing user.
     * Route: PUT /api/users/{id} or POST /api/users/{id}
     *
     * @param int $id User ID.
     * @param array $payload Fields to update.
     */
    public function update(int $id, array $payload): void {
        // Check existence first
        if (User::findById($id) === null) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "User with ID {$id} not found"
            ], 404);
        }

        try {
            $updated = User::update($id, $payload);
            $this->jsonResponse([
                'status'  => 'success',
                'message' => 'User updated successfully',
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
     * Delete an existing user.
     * Route: DELETE /api/users/{id}
     *
     * @param int $id User ID.
     */
    public function destroy(int $id): void {
        $deleted = User::delete($id);

        if (!$deleted) {
            $this->jsonResponse([
                'status'  => 'error',
                'message' => "User with ID {$id} not found"
            ], 404);
        }

        $this->jsonResponse([
            'status'  => 'success',
            'message' => "User with ID {$id} deleted successfully"
        ], 200);
    }
}
