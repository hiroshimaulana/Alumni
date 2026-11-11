<?php
/**
 * Istanbul University MIS Alumni Portal
 * Middleware: 404 Not Found Guardian
 *
 * Provides a centralized, content-negotiated 404 handler that:
 *   - Returns a branded HTML 404 page (views/404.html) for browser/web requests.
 *   - Returns a standardized JSON 404 payload for API / Accept:application/json requests.
 *
 * Usage in index.php:
 *   NotFoundHandler::handle($method, $path);
 */

class NotFoundHandler
{
    /**
     * Dispatch a 404 response, choosing HTML or JSON based on the request path
     * and the Accept header sent by the client.
     *
     * API paths (/api/*) always receive a JSON response.
     * Browser paths receive the branded HTML 404 view.
     *
     * @param string $method  HTTP request method (e.g. 'GET').
     * @param string $path    Normalized request URI path (e.g. '/unknown-page').
     * @return never           This method always terminates the request via exit().
     */
    public static function handle(string $method, string $path): never
    {
        http_response_code(404);

        // Determine preferred response format
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApiPath    = str_starts_with($path, '/api/');
        $wantsJson    = $isApiPath
            || str_contains($acceptHeader, 'application/json')
            || str_contains($acceptHeader, 'application/');

        if ($wantsJson) {
            self::jsonNotFound($path);
        } else {
            self::htmlNotFound($path);
        }
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    /**
     * Emit a standardized JSON 404 payload.
     *
     * Schema:
     * {
     *   "status":  "error",
     *   "error":   "404 Not Found",
     *   "message": "The requested endpoint or resource '/path' was not found on this server.",
     *   "path":    "/path",
     *   "method":  "GET"
     * }
     */
    private static function jsonNotFound(string $path): never
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'error'   => '404 Not Found',
            'message' => "The requested endpoint or resource '{$path}' was not found on this server.",
            'path'    => $path,
            'method'  => $method,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Emit the branded HTML 404 error page.
     * Falls back to a minimal inline HTML if the view file is missing.
     */
    private static function htmlNotFound(string $path): never
    {
        header('Content-Type: text/html; charset=utf-8');

        $viewFile = __DIR__ . '/../views/404.html';
        if (file_exists($viewFile)) {
            readfile($viewFile);
            exit;
        }

        // Minimal fallback if the view template is missing
        $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>404 Not Found — MIS Alumni Portal</title>
  <style>body{font-family:sans-serif;text-align:center;padding:5rem;color:#1E293B}
  h1{font-size:4rem;color:#003366}p{color:#64748B}</style>
</head>
<body>
  <h1>404</h1>
  <p>The page <code>{$safePath}</code> could not be found.</p>
  <p><a href="/">← Return to Home</a></p>
</body>
</html>
HTML;
        exit;
    }
}
