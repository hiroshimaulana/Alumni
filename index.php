<?php
/**
 * Istanbul University MIS Alumni Portal
 * Main Entry Point & Front Controller Router
 *
 * Middleware pipeline (in registration order):
 *   1. ServerErrorHandler  — global 5xx guardian; file-logs errors to logs/error.log
 *   2. RequestLogger       — terminal request log: [time] [method] [path] [status] [ms]
 *   3. NotFoundHandler     — content-negotiated 404 responses (HTML or JSON)
 */

// Enable error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// -------------------------------------------------------------
// 0. Middleware Bootstrap
//    Load and activate all middleware before any application logic.
// -------------------------------------------------------------
require_once __DIR__ . '/middleware/ServerErrorHandler.php';
require_once __DIR__ . '/middleware/RequestLogger.php';
require_once __DIR__ . '/middleware/NotFoundHandler.php';

// 5xx Guardian: replaces raw set_exception_handler / set_error_handler below
ServerErrorHandler::register(__DIR__ . '/logs/error.log');

// Request Logger: replaced by the inline logger below to avoid logging every
// request twice. Uncomment this line (and remove the inline block) to switch back.
// RequestLogger::start();

// Inline request logger: starts the timer and writes the log line on shutdown
$startTime = microtime(true);

// Register a shutdown function or hook to log the request once execution finishes
register_shutdown_function(function() use ($startTime) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $time = date('H:i:s');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $route = $_SERVER['REQUEST_URI'] ?? '/';
    $status = http_response_code();

    $logEntry = "[$time] [$method] [$route] [$status] [$duration ms]\n";
    file_put_contents('php://stdout', $logEntry);
});

// -------------------------------------------------------------
// 1. Response Helpers & Standardized JSON Error Output
// -------------------------------------------------------------

/**
 * Send a standardized JSON response.
 * Normalizes any error response (status code >= 400 or status=error) to match:
 * {
 *   "status": "error",
 *   "error": "<HTTP Status Code & Name>",
 *   "message": "<Detailed description>"
 * }
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    if (is_array($data) && ($statusCode >= 400 || ($data['status'] ?? '') === 'error')) {
        $statusPhrases = [
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            422 => '422 Unprocessable Entity',
            500 => '500 Internal Server Error',
        ];

        $status = $data['status'] ?? 'error';
        $error = $data['error'] ?? ($statusPhrases[$statusCode] ?? "{$statusCode} Error");
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
 * Send a standardized JSON error response.
 *
 * Schema:
 * {
 *   "status": "error",
 *   "error": "<HTTP Status Code & Name>",
 *   "message": "<Detailed description>"
 * }
 */
function errorResponse(int $statusCode, string $message, array $extra = []): void {
    $statusPhrases = [
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        422 => '422 Unprocessable Entity',
        500 => '500 Internal Server Error',
    ];

    $errorText = $statusPhrases[$statusCode] ?? "{$statusCode} Error";

    $response = array_merge([
        'status'  => 'error',
        'error'   => $errorText,
        'message' => $message,
    ], $extra);

    jsonResponse($response, $statusCode);
}

/**
 * Serve an HTML view file with HTTP status code and template variables.
 */
function renderView(string $viewFile, int $statusCode = 200, array $vars = []): void {
    $filePath = __DIR__ . '/views/' . $viewFile;
    if (file_exists($filePath)) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        if (empty($vars)) {
            readfile($filePath);
        } else {
            $content = file_get_contents($filePath);
            foreach ($vars as $key => $value) {
                $content = str_replace('{{' . $key . '}}', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $content);
                $content = str_replace('{{ ' . $key . ' }}', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $content);
            }
            echo $content;
        }
        exit;
    }
    errorResponse(404, "View template not found: " . htmlspecialchars($viewFile, ENT_QUOTES, 'UTF-8'));
}

/**
 * Send a plain text response.
 */
function textResponse(string $text, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

/**
 * 404 Guardian: sends the standardized JSON 404 payload for any unmatched route.
 * Uses errorResponse() so the output matches every other error in the app:
 * { "status": "error", "error": "404 Not Found", "message": "..." }
 */
function handleNotFound(): void {
    errorResponse(404, 'The requested route or resource does not exist.');
}

// -------------------------------------------------------------
// 2. Global Exception & Error Handlers (Clean 500 JSON Payload)
// -------------------------------------------------------------
// NOTE: set_error_handler is still managed by ServerErrorHandler::register()
// in the Middleware Bootstrap above. The exception handler below replaces the
// one that register() installed, and covers uncaught exceptions thrown outside
// the main try/catch in Section 8 (e.g. while loading models, controllers, routes).
set_exception_handler(function(Throwable $exception): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    // Ensure logs folder exists
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    // Append to the same log file the ServerErrorHandler middleware uses
    $logFile = $logDir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] 500 Internal Server Error: " . $exception->getMessage() . "\n" .
                  "File: " . $exception->getFile() . " on line " . $exception->getLine() . "\n" .
                  $exception->getTraceAsString() . "\n\n";

    @file_put_contents($logFile, $logMessage, FILE_APPEND);

    // Return clean JSON response to client (no internal details exposed)
    echo json_encode([
        'status'  => 'error',
        'error'   => '500 Internal Server Error',
        'message' => 'An unexpected server error occurred. Please try again later.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});

// -------------------------------------------------------------
// 3. Normalize Request Method and Path
// -------------------------------------------------------------
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Parse path without query parameters
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// Normalize path if deployed in a subdirectory (e.g., /Alumni or /Alumni/public)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = dirname($scriptName);
$scriptDir = str_replace('\\', '/', $scriptDir);
if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}

// Remove index.php prefix if present in the URL
if (strpos($path, '/index.php') === 0) {
    $path = substr($path, strlen('/index.php'));
}

// Ensure leading slash and remove trailing slash (unless root '/')
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

// -------------------------------------------------------------
// 4. Static Asset Validation and Streaming
// -------------------------------------------------------------

/**
 * Check if the requested URI path matches a valid, accessible static asset.
 *
 * @param string $path Normalized request URI path.
 * @return string|null Absolute file path if valid static asset, null otherwise.
 */
function resolveStaticAsset(string $path): ?string {
    if ($path === '/' || empty($path)) {
        return null;
    }

    $baseDir = realpath(__DIR__);
    $targetFile = realpath(__DIR__ . $path);

    // Prevent directory traversal attacks
    if ($targetFile === false || !str_starts_with($targetFile, $baseDir)) {
        return null;
    }

    // Must be a physical file, not a directory
    if (!is_file($targetFile)) {
        return null;
    }

    // Prohibit hidden files or dotfiles (.env, .htaccess, .git, etc.)
    $basename = basename($targetFile);
    if (str_starts_with($basename, '.')) {
        return null;
    }

    // Whitelist allowed static asset extensions
    $allowedStaticExtensions = [
        'css', 'js', 'json', 'yaml', 'yml', 'png', 'jpg', 'jpeg',
        'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf',
        'txt', 'pdf', 'map', 'html', 'htm'
    ];

    $extension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedStaticExtensions, true)) {
        return null;
    }

    // Protect application code directories from direct static exposure
    $relativePath = ltrim(str_replace('\\', '/', substr($targetFile, strlen($baseDir))), '/');
    $protectedPrefixes = ['config/', 'controllers/', 'models/', 'database/', 'routes/', 'tests/', 'docker/'];
    foreach ($protectedPrefixes as $prefix) {
        if (str_starts_with($relativePath, $prefix)) {
            return null;
        }
    }

    return $targetFile;
}

/**
 * Serve a valid static asset file with appropriate MIME headers.
 *
 * @param string $filePath Absolute path to the static asset.
 */
function serveStaticAsset(string $filePath): void {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'json'  => 'application/json; charset=utf-8',
        'yaml'  => 'text/yaml; charset=utf-8',
        'yml'   => 'text/yaml; charset=utf-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'html'  => 'text/html; charset=utf-8',
        'htm'   => 'text/html; charset=utf-8',
        'txt'   => 'text/plain; charset=utf-8',
        'pdf'   => 'application/pdf',
    ];

    $mime = $mimeTypes[$extension] ?? (function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream');
    http_response_code(200);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// -------------------------------------------------------------
// 5. Load Domain Models & Controllers
// -------------------------------------------------------------
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/JobPosting.php';
require_once __DIR__ . '/models/Application.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/ApiUserController.php';
require_once __DIR__ . '/controllers/JobPostingController.php';
require_once __DIR__ . '/controllers/ApiJobPostingController.php';
require_once __DIR__ . '/controllers/ApplicationController.php';
require_once __DIR__ . '/controllers/ApiApplicationController.php';
require_once __DIR__ . '/controllers/ApiAuthController.php';

// Start session if available for in-memory model state persistence across web requests
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

// -------------------------------------------------------------
// 6. Router Initialization & Route Definitions
// -------------------------------------------------------------
require_once __DIR__ . '/routes/Router.php';

$router = new Router();

// Load Web Routes (includes /users, /job-postings, /applications, etc.)
$webRoutes = require __DIR__ . '/routes/web.php';
if (is_callable($webRoutes)) {
    $webRoutes($router);
}

// Load API Routes (includes /api/users, /api/job-postings, /api/applications, /api/login, /api/register, /api/profile, /api/admin)
$apiRoutes = require __DIR__ . '/routes/api.php';
if (is_callable($apiRoutes)) {
    $apiRoutes($router);
}

// -------------------------------------------------------------
// Top-Level Application Routes
// -------------------------------------------------------------

// Main Landing Page (GET /)
$router->get('/', function(): void {
    renderView('main.html');
});

// About Department View (GET /about)
$router->get('/about', function(): void {
    renderView('about.html');
});

// Alumni Resource API (GET /alumni)
$router->get('/alumni', function(): void {
    require __DIR__ . '/alumni.php';
    exit;
});

// Interactive Swagger UI Documentation Viewer (GET /swagger or GET /docs)
$router->get('/swagger', function(): void {
    renderView('swagger.html');
});
$router->get('/docs', function(): void {
    renderView('swagger.html');
});

// OpenAPI 3.0.3 Specification JSON (GET /openapi.json or GET /api/docs/openapi.json)
$router->get('/openapi.json', function(): void {
    $specPath = __DIR__ . '/docs/openapi.json';
    if (file_exists($specPath)) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        readfile($specPath);
        exit;
    }
    errorResponse(404, 'OpenAPI JSON specification not found');
});
$router->get('/api/docs/openapi.json', function(): void {
    $specPath = __DIR__ . '/docs/openapi.json';
    if (file_exists($specPath)) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        readfile($specPath);
        exit;
    }
    errorResponse(404, 'OpenAPI JSON specification not found');
});

// OpenAPI 3.0.3 Specification YAML (GET /openapi.yaml or GET /api/docs/openapi.yaml)
$router->get('/openapi.yaml', function(): void {
    $specPath = __DIR__ . '/docs/openapi.yaml';
    if (file_exists($specPath)) {
        header('Content-Type: text/yaml; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        readfile($specPath);
        exit;
    }
    errorResponse(404, 'OpenAPI YAML specification not found');
});
$router->get('/api/docs/openapi.yaml', function(): void {
    $specPath = __DIR__ . '/docs/openapi.yaml';
    if (file_exists($specPath)) {
        header('Content-Type: text/yaml; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        readfile($specPath);
        exit;
    }
    errorResponse(404, 'OpenAPI YAML specification not found');
});

// -------------------------------------------------------------
// Route Group: /auto
// -------------------------------------------------------------
$router->get('/auto', function(): void {
    textResponse('ok');
});

$router->get('/auto/hello', function(): void {
    textResponse('Hello, World!');
});

$router->get('/auto/hello/{name}', function(array $params): void {
    $name = urldecode($params['name'] ?? '');
    $sanitizedName = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    textResponse("Hello, {$sanitizedName}!");
});

$router->get('/auto/sum/{number1}/{number2}', function(array $params): void {
    $n1 = $params['number1'] ?? '';
    $n2 = $params['number2'] ?? '';

    if (!is_numeric($n1) || !is_numeric($n2)) {
        errorResponse(400, 'Invalid parameters: number1 and number2 must be valid numeric values', [
            'inputs' => [
                'number1' => $n1,
                'number2' => $n2
            ]
        ]);
    }

    $num1 = strpos($n1, '.') !== false ? (float)$n1 : (int)$n1;
    $num2 = strpos($n2, '.') !== false ? (float)$n2 : (int)$n2;
    $sum = $num1 + $num2;

    jsonResponse(['sum' => $sum]);
});

$router->get('/auto/main', function(): void {
    renderView('main.html');
});

$router->get('/auto/about', function(): void {
    renderView('about.html');
});

$router->post('/auto', function(array $params, array $payload): void {
    jsonResponse([
        'status'  => 'success',
        'message' => 'POST endpoint skeleton ready for request handling',
        'route'   => '/auto',
        'received_payload' => $payload,
        'headers' => [
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ],
        'timestamp' => date('c')
    ]);
});

// -------------------------------------------------------------
// 7. Request Payload Ingestion
// -------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$jsonInput = null;
if ($rawInput !== false && trim($rawInput) !== '') {
    $jsonInput = json_decode($rawInput, true);
}
parse_str($rawInput ?: '', $parsedForm);
$payload = ($jsonInput !== null) ? $jsonInput : (!empty($_POST) ? $_POST : (is_array($parsedForm) ? $parsedForm : []));

// -------------------------------------------------------------
// 8. Execution, Route Dispatch & Error Handling
// -------------------------------------------------------------
try {
    // Static Asset Routing Check
    $staticAsset = resolveStaticAsset($path);
    if ($staticAsset !== null) {
        if (php_sapi_name() === 'cli-server') {
            return false;
        }
        serveStaticAsset($staticAsset);
    }

    // Explicit Route Matching Validation
    if ($router->hasRoute($method, $path)) {
        $router->dispatch($method, $path, $payload);
        exit;
    }

    // Check if the path exists for a different HTTP method (405 Method Not Allowed)
    if ($router->hasPath($path)) {
        $allowedMethods = $router->getAllowedMethods($path);
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        errorResponse(405, "HTTP method {$method} is not supported for {$path}. Supported methods: " . implode(', ', $allowedMethods));
    }

    // Unmatched / Undefined Endpoint → 404 Guardian
    // (To restore HTML/JSON content negotiation, call
    //  NotFoundHandler::handle($method, $path); here instead.)
    handleNotFound();

} catch (Throwable $e) {
    // 5xx Guardian Middleware handles all unhandled exceptions:
    // logs to logs/error.log and returns a clean JSON 500 payload.
    ServerErrorHandler::handleException($e);
}