<?php
/**
 * Istanbul University MIS Alumni Portal
 * Middleware: Request Logger
 *
 * Logs every incoming HTTP request to the terminal (STDERR / error_log) in the format:
 *   [HH:MM:SS] [METHOD] [/path] [status_code] [X ms]
 *
 * Usage — register at the very top of index.php (before route dispatch):
 *   RequestLogger::start();
 *   // ... dispatch routes ...
 *   RequestLogger::finish($httpStatusCode);  // called by shutdown function automatically
 *
 * The middleware installs a shutdown function that fires after the response is sent,
 * so the final status code (set via http_response_code()) is captured automatically.
 */

class RequestLogger
{
    /** @var float Microtime at which the request was received. */
    private static float $startTime = 0.0;

    /** @var bool Whether the logger has been initialized for this request. */
    private static bool $initialized = false;

    /**
     * Start timing the request.
     * Call once at the very beginning of the request lifecycle (top of index.php).
     */
    public static function start(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$startTime   = microtime(true);
        self::$initialized = true;

        // Register a shutdown function so the log line is always written,
        // even when the script exits via exit() / die() or an unhandled error.
        register_shutdown_function([self::class, 'writeLog']);
    }

    /**
     * Write the log line to STDERR / error_log.
     * Automatically invoked by the registered shutdown function.
     */
    public static function writeLog(): void
    {
        $duration = self::$startTime > 0.0
            ? (int) round((microtime(true) - self::$startTime) * 1000)
            : 0;

        $time   = date('H:i:s');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $status = http_response_code();

        // Sanitize URI to avoid control characters in log lines
        $uri = preg_replace('/[^\x20-\x7E]/', '?', $uri);

        $line = sprintf(
            '[%s] [%s] [%s] [%d] [%d ms]',
            $time,
            $method,
            $uri,
            $status,
            $duration
        );

        // Write to STDERR so it appears in the terminal when using PHP built-in server
        // and also goes to the system error log in production Apache/Nginx deployments.
        fwrite(STDERR, $line . PHP_EOL);
        // Mirror to error_log for environments where STDERR is not visible
        error_log($line);
    }
}
