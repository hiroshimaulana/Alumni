<?php
/**
 * Istanbul University MIS Alumni Portal
 * Main Entry Point & Router
 */

// Enable error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// 1. Normalize Request Method and Path
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Parse path without query parameters
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// Normalize path if deployed in a subdirectory (e.g., /Alumni or /Alumni/public)
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = dirname($scriptName);
if ($scriptDir !== '/' && $scriptDir !== '.' && strpos($path, $scriptDir) === 0) {
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

// Helper: Serve an HTML view file
function renderView(string $viewFile): void {
    $filePath = __DIR__ . '/views/' . $viewFile;
    if (file_exists($filePath)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "View not found: " . htmlspecialchars($viewFile);
    exit;
}

// Helper: Send JSON response
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper: Send Text response
function textResponse(string $text, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

// -------------------------------------------------------------
// POST Request Skeleton Handler
// -------------------------------------------------------------
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $payload = ($jsonData !== null) ? $jsonData : $_POST;

    jsonResponse([
        'status'  => 'success',
        'message' => 'POST endpoint skeleton ready for request handling',
        'route'   => $path,
        'received_payload' => $payload,
        'headers' => [
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
        ],
        'timestamp' => date('c')
    ]);
}

// -------------------------------------------------------------
// Route Group: /auto
// -------------------------------------------------------------
if ($path === '/auto' || strpos($path, '/auto/') === 0) {

    // Sub-path inside /auto
    $subPath = substr($path, strlen('/auto'));
    $subPath = '/' . ltrim($subPath, '/');

    // 1. GET /auto or GET /auto/ -> "ok"
    if ($subPath === '/') {
        textResponse('ok');
    }

    // 2. GET /auto/hello -> "Hello, World!"
    if ($subPath === '/hello') {
        textResponse('Hello, World!');
    }

    // 3. GET /auto/hello/{name} -> "Hello, {name}!"
    if (preg_match('#^/hello/([^/]+)$#', $subPath, $matches)) {
        $name = urldecode($matches[1]);
        $sanitizedName = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        textResponse("Hello, {$sanitizedName}!");
    }

    // 4. GET /auto/sum/{number1}/{number2} -> JSON {"sum": result}
    if (preg_match('#^/sum/([^/]+)/([^/]+)$#', $subPath, $matches)) {
        $n1 = $matches[1];
        $n2 = $matches[2];

        if (!is_numeric($n1) || !is_numeric($n2)) {
            jsonResponse([
                'error' => 'Invalid parameters: number1 and number2 must be valid numeric values',
                'inputs' => [
                    'number1' => $n1,
                    'number2' => $n2
                ]
            ], 400);
        }

        // Convert to int or float based on format
        $num1 = strpos($n1, '.') !== false ? (float)$n1 : (int)$n1;
        $num2 = strpos($n2, '.') !== false ? (float)$n2 : (int)$n2;
        $sum = $num1 + $num2;

        jsonResponse([
            'sum' => $sum
        ]);
    }

    // 5. GET /auto/main -> views/main.html
    if ($subPath === '/main') {
        renderView('main.html');
    }

    // 6. GET /auto/about -> views/about.html
    if ($subPath === '/about') {
        renderView('about.html');
    }

    // Unmatched route under /auto
    jsonResponse([
        'error' => 'Route not found under /auto group',
        'requested_path' => $path
    ], 404);
}

// -------------------------------------------------------------
// Top-Level Routes
// -------------------------------------------------------------

// Main page (GET /)
if ($path === '/') {
    renderView('main.html');
}

// About page (GET /about)
if ($path === '/about') {
    renderView('about.html');
}

// Alumni endpoint (GET /alumni)
if ($path === '/alumni') {
    require __DIR__ . '/alumni.php';
    exit;
}

// -------------------------------------------------------------
// Default 404 Handler
// -------------------------------------------------------------
http_response_code(404);
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($acceptHeader, 'application/json') !== false) {
    jsonResponse([
        'error' => 'Not Found',
        'requested_path' => $path
    ], 404);
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;">';
    echo '<h1>404 Not Found</h1>';
    echo '<p>The requested endpoint <code>' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</code> was not found on this server.</p>';
    echo '<p><a href="/">&larr; Return to Home</a></p>';
    echo '</body></html>';
    exit;
}
