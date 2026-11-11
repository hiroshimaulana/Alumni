<?php
/**
 * Istanbul University MIS Alumni Portal
 * Middleware: 5xx Server Error Guardian
 *
 * Catches unhandled exceptions and fatal errors, writes structured details
 * to logs/error.log, and returns a clean, standardized JSON 500 response
 * without exposing raw stack traces or system internals in production.
 *
 * Usage — install once at the top of index.php:
 *   ServerErrorHandler::register();
 */

class ServerErrorHandler
{
    /** @var string Absolute path to the error log file. */
    private static string $logFile = '';

    /**
     * Register the global exception and error handlers.
     * Must be called before any route dispatch logic.
     *
     * @param string|null $logFile  Absolute path to the log file.
     *                              Defaults to <project-root>/logs/error.log.
     */
    public static function register(?string $logFile = null): void
    {
        self::$logFile = $logFile ?? (dirname(__DIR__) . '/logs/error.log');
        self::ensureLogDirectory();

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    // -----------------------------------------------------------------
    // Public handlers (called by PHP internals — must be public)
    // -----------------------------------------------------------------

    /**
     * Global exception handler.
     * Intercepts any Throwable not caught by the application code.
     */
    public static function handleException(Throwable $e): void
    {
        self::logError($e);
        self::sendJsonError(500, 'An unexpected internal server error occurred. Please try again later.');
    }

    /**
     * Global error handler.
     *
     * Non-fatal warnings/notices are logged and execution continues.
     * Fatal errors (E_USER_ERROR, E_RECOVERABLE_ERROR) are escalated to exceptions.
     *
     * @throws ErrorException For fatal PHP errors.
     */
    public static function handleError(
        int    $errno,
        string $errstr,
        string $errfile,
        int    $errline
    ): bool {
        // Respect the error_reporting() bitmask (e.g. @ operator suppression)
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $nonFatal = [
            E_NOTICE, E_USER_NOTICE,
            E_DEPRECATED, E_USER_DEPRECATED,
            E_WARNING, E_USER_WARNING,
        ];

        if (in_array($errno, $nonFatal, true)) {
            // Log silently, let execution continue
            $entry = sprintf(
                '[%s] [NOTICE/WARNING] [errno:%d] %s in %s:%d',
                date('Y-m-d H:i:s'),
                $errno,
                $errstr,
                $errfile,
                $errline
            );
            self::appendToLog($entry);
            error_log("[MIS-Portal Notice/Warning] {$errstr} in {$errfile}:{$errline}");
            return true;
        }

        // Escalate fatal-level errors to exceptions so the exception handler
        // can produce a clean JSON 500 response.
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    /**
     * Append a structured error entry (with stack trace) to logs/error.log.
     */
    private static function logError(Throwable $e): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $method    = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri       = $_SERVER['REQUEST_URI']    ?? 'N/A';

        $entry = implode(PHP_EOL, [
            '================================================================================',
            "[{$timestamp}] [500] [{$method} {$uri}]",
            'Exception : ' . get_class($e),
            'Message   : ' . $e->getMessage(),
            'File      : ' . $e->getFile() . ':' . $e->getLine(),
            'Stack Trace:',
            $e->getTraceAsString(),
            '================================================================================',
            '',
        ]);

        self::appendToLog($entry);

        // Also mirror to PHP's native error_log for syslog / server log integration
        error_log(
            '[MIS-Portal Exception] ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine()
        );
    }

    /**
     * Append text to the error log file.
     */
    private static function appendToLog(string $text): void
    {
        self::ensureLogDirectory();

        // Use FILE_APPEND | LOCK_EX to prevent interleaved writes under concurrency
        @file_put_contents(self::$logFile, $text . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Ensure the logs/ directory exists, creating it (recursively) if necessary.
     */
    private static function ensureLogDirectory(): void
    {
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    /**
     * Emit a standardized JSON error payload and terminate the request.
     *
     * Response schema:
     * {
     *   "status":  "error",
     *   "error":   "500 Internal Server Error",
     *   "message": "<safe, non-revealing message>"
     * }
     */
    private static function sendJsonError(int $code, string $message): never
    {
        // Guard: avoid sending headers if output has already started
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }

        $statusPhrases = [
            500 => '500 Internal Server Error',
            502 => '502 Bad Gateway',
            503 => '503 Service Unavailable',
        ];

        echo json_encode([
            'status'  => 'error',
            'error'   => $statusPhrases[$code] ?? "{$code} Server Error",
            'message' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        exit;
    }
}
