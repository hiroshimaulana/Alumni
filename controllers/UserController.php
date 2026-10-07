<?php
/**
 * Istanbul University MIS Alumni Portal
 * Web User Controller
 *
 * Handles web requests for user management: listing, creating, viewing,
 * editing and deleting users, rendered through views/users.html.
 */

require_once __DIR__ . '/../models/User.php';

class UserController {
    private const ROLES = ['alumni', 'student', 'faculty_admin', 'super_admin'];
    private const NOTICE_TYPES = ['info', 'success', 'error'];

    /**
     * READ (collection). Route: GET /users
     */
    public function index(): void {
        $this->render(User::all());
    }

    /**
     * READ (single). Route: GET /users/{id}
     *
     * @param int|string $id
     */
    public function show($id): void {
        $user = $this->findOr404($id);

        $this->render(User::all(), [
            'title'      => 'User Profile: ' . $user['first_name'] . ' ' . $user['last_name'],
            'singleUser' => $user,
        ]);
    }

    /**
     * CREATE (form view). Route: GET /users/create
     */
    public function create(): void {
        $this->render(User::all(), [
            'title'      => 'Create New User',
            'openCreate' => true,
        ]);
    }

    /**
     * CREATE (action). Route: POST /users
     *
     * @param array $postData Form POST data (falls back to $_POST).
     */
    public function store(array $postData = []): void {
        $postData = $this->input($postData);
        $this->requireCsrf($postData);

        $fields = [
            'email'       => trim((string)($postData['email'] ?? '')),
            'first_name'  => trim((string)($postData['first_name'] ?? '')),
            'last_name'   => trim((string)($postData['last_name'] ?? '')),
            'role'        => (string)($postData['role'] ?? 'alumni'),
            'is_verified' => isset($postData['is_verified']) ? 1 : 0,
        ];

        try {
            $this->assertValid($fields);
            $newUser = User::create($fields);

            $message = "User '{$newUser['first_name']} {$newUser['last_name']}' created successfully (ID: #{$newUser['id']}).";
            $this->render(User::all(), [
                'notice'     => $message,
                'noticeType' => 'success',
                'singleUser' => $newUser,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(User::all(), [
                'title'      => 'Create New User',
                'notice'     => $e->getMessage(),
                'noticeType' => 'error',
                'openCreate' => true,
                'oldInput'   => $fields,
                'status'     => 422,
            ]);
        }
    }

    /**
     * UPDATE (form view). Route: GET /users/{id}/edit
     *
     * @param int|string $id
     */
    public function edit($id): void {
        $user = $this->findOr404($id);

        $this->render(User::all(), [
            'title'    => "Edit User #{$user['id']}",
            'openEdit' => true,
            'formUser' => $user,
        ]);
    }

    /**
     * UPDATE (action). Route: POST /users/{id}/update or PUT /users/{id}
     *
     * Only fields that were actually sent are changed; missing fields keep
     * their current values.
     *
     * @param int|string $id
     * @param array      $postData Form data (falls back to $_POST / php://input).
     */
    public function update($id, array $postData = []): void {
        $existing = $this->findOr404($id);

        $postData = $this->input($postData);
        $this->requireCsrf($postData);

        $fields = [];
        foreach (['email', 'first_name', 'last_name'] as $key) {
            $fields[$key] = array_key_exists($key, $postData)
                ? trim((string)$postData[$key])
                : $existing[$key];
        }
        $fields['role'] = array_key_exists('role', $postData) ? (string)$postData['role'] : $existing['role'];

        // An unchecked checkbox is simply absent from a submitted form, so treat
        // "form submitted (first_name present) without is_verified" as unchecked.
        if (isset($postData['is_verified'])) {
            $fields['is_verified'] = 1;
        } elseif (array_key_exists('first_name', $postData)) {
            $fields['is_verified'] = 0;
        } else {
            $fields['is_verified'] = !empty($existing['is_verified']) ? 1 : 0;
        }

        try {
            $this->assertValid($fields);
            $updated = User::update((int)$existing['id'], $fields);

            if ($updated === null) {
                $this->abort(404, "User #{$existing['id']} Not Found", 'Cannot update a non-existent user.');
            }

            $message = "User '{$updated['first_name']} {$updated['last_name']}' updated successfully.";
            $this->render(User::all(), [
                'notice'     => $message,
                'noticeType' => 'success',
                'singleUser' => $updated,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->render(User::all(), [
                'title'      => "Edit User #{$existing['id']}",
                'notice'     => $e->getMessage(),
                'noticeType' => 'error',
                'openEdit'   => true,
                'formUser'   => array_merge($existing, $fields),
                'status'     => 422,
            ]);
        }
    }

    /**
     * DELETE (action). Route: POST /users/{id}/delete or DELETE /users/{id}
     *
     * @param int|string $id
     */
    public function delete($id): void {
        $this->requireCsrf($this->input([]));

        $intId = $this->toId($id);
        $deleted = $intId !== null && User::delete($intId);

        if (!$deleted) {
            $label = (string)($intId ?? $id);
            $this->render(User::all(), [
                'notice'     => "User #{$label} could not be found to delete.",
                'noticeType' => 'error',
            ]);
            return;
        }

        $this->render(User::all(), [
            'notice'     => "User #{$intId} was successfully deleted.",
            'noticeType' => 'success',
        ]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** Cast a route parameter to a positive int, or null if it is not one. */
    private function toId($id): ?int {
        if (is_int($id)) {
            return $id > 0 ? $id : null;
        }
        return (is_string($id) && ctype_digit($id) && (int)$id > 0) ? (int)$id : null;
    }

    /** Find a user, or emit a 404 page and stop. */
    private function findOr404($id): array {
        $intId = $this->toId($id);
        $user = $intId !== null ? User::findById($intId) : null;

        if ($user === null) {
            $this->abort(404, 'User #' . (string)$id . ' Not Found', 'The user you are looking for does not exist in the registry.');
        }
        return $user;
    }

    /** Server-side validation shared by store() and update(). */
    private function assertValid(array $fields): void {
        if ($fields['first_name'] === '' || $fields['last_name'] === '') {
            throw new InvalidArgumentException('First name and last name are required.');
        }
        if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please provide a valid email address.');
        }
        if (!in_array($fields['role'], self::ROLES, true)) {
            throw new InvalidArgumentException('Invalid role selected.');
        }
    }

    /**
     * Normalise request data: use what the router passed, else $_POST,
     * else (for PUT/DELETE) parse the raw body.
     */
    private function input(array $postData): array {
        if ($postData !== []) {
            return $postData;
        }
        if (!empty($_POST)) {
            return $_POST;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $raw = file_get_contents('php://input') ?: '';
            $type = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($type, 'application/json') !== false) {
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            }
            parse_str($raw, $parsed);
            return $parsed;
        }
        return [];
    }

    // ----- session & CSRF -----

    private function session(): void {
        // CLI (tests, scripts) has no browser session; $_SESSION still works as a plain array.
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function csrfToken(): string {
        $this->session();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /**
     * Browser requests must carry the token (form field _csrf or X-CSRF-Token
     * header). CLI runs are exempt: there is no browser to forge a request from.
     */
    private function requireCsrf(array $data): void {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $this->session();
        $sent = $data['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $ok = !empty($_SESSION['_csrf']) && is_string($sent) && hash_equals($_SESSION['_csrf'], $sent);

        if (!$ok) {
            $this->abort(419, 'Session Expired', 'Your form session expired. Please go back, reload the page and try again.');
        }
    }

    // ----- rendering -----

    /** Emit an error page and stop. */
    private function abort(int $status, string $title, string $message): void {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->renderErrorPage($title, $message);
        exit;
    }

    /**
     * Render views/users.html and stop.
     *
     * Options: title, notice, noticeType, openCreate, openEdit,
     *          singleUser (profile card), formUser (edit modal values),
     *          oldInput (create modal values), status (HTTP code).
     */
    private function render(array $users, array $opts = []): void {
        $title      = (string)($opts['title'] ?? 'User Directory');
        $notice     = $opts['notice'] ?? null;
        $noticeType = in_array($opts['noticeType'] ?? 'info', self::NOTICE_TYPES, true) ? $opts['noticeType'] : 'info';
        $status     = (int)($opts['status'] ?? 200);

        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');

        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
        $json  = fn($v) => json_encode($v, $flags);

        $viewFile = __DIR__ . '/../views/users.html';
        if (file_exists($viewFile)) {
            $content = file_get_contents($viewFile);

            $noticeHtml = $notice
                ? "<div class=\"alert alert-{$noticeType}\" role=\"alert\">" . htmlspecialchars((string)$notice, ENT_QUOTES, 'UTF-8') . '</div>'
                : '';

            $initScript = 'const serverUsers = ' . $json($users) . ";\n"
                . 'const csrfToken = ' . $json($this->csrfToken()) . ";\n"
                . 'const autoOpenCreate = ' . (!empty($opts['openCreate']) ? 'true' : 'false') . ";\n"
                . 'const autoOpenEdit = ' . (!empty($opts['openEdit']) ? 'true' : 'false') . ";\n"
                . 'const isSingleView = ' . (isset($opts['singleUser']) ? 'true' : 'false') . ";\n"
                . 'const currentSingleUser = ' . $json($opts['singleUser'] ?? null) . ";\n"
                . 'const formUser = ' . $json($opts['formUser'] ?? null) . ";\n"
                . 'const oldInput = ' . $json($opts['oldInput'] ?? null) . ";\n";

            $content = str_replace('<!-- PAGE_TITLE -->', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), $content);
            $content = str_replace('<!-- NOTICE_PLACEHOLDER -->', $noticeHtml, $content);
            $content = str_replace('/* USERS_JSON_PLACEHOLDER */', $initScript, $content);
            echo $content;
            exit;
        }

        // Fallback plain rendering if the template file is missing
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . $e($title) . '</title></head><body>';
        echo '<h1>' . $e($title) . '</h1>';
        if ($notice) {
            echo '<p><strong>' . $e($notice) . '</strong></p>';
        }
        echo '<ul>';
        foreach ($users as $u) {
            echo '<li>#' . $e($u['id']) . ' - ' . $e($u['first_name'] . ' ' . $u['last_name'])
               . ' (' . $e($u['email']) . ') [' . $e($u['role']) . ']</li>';
        }
        echo '</ul></body></html>';
        exit;
    }

    private function renderErrorPage(string $title, string $message): string {
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . $t . '</title>'
            . '<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#F8FAFC;color:#1E293B;}'
            . 'h1{color:#003366;}a{color:#003366;font-weight:600;text-decoration:none;}</style></head>'
            . '<body><h1>' . $t . '</h1><p>' . $m . '</p>'
            . '<p><a href="/users">&larr; Back to Users Directory</a></p></body></html>';
    }
}