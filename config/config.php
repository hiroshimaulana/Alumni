<?php
/**
 * Application & Database Configuration
 * Istanbul University MIS Alumni Portal
 */

// Helper to load key-value pairs from .env file into environment
(function (): void {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Strip wrapping quotes
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
})();

$env = function (string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
};

return [
    'app' => [
        'name'     => $env('APP_NAME', 'Istanbul University MIS Alumni Portal'),
        'env'      => $env('APP_ENV', 'development'),
        'base_url' => $env('APP_URL', 'http://localhost:8000'),
    ],
    'db' => [
        'connection' => $env('DB_CONNECTION', 'mysql'),
        'host'       => $env('DB_HOST', '127.0.0.1'),
        'port'       => $env('DB_PORT', '3306'),
        'database'   => $env('DB_DATABASE', 'iu_mis_alumni'),
        'username'   => $env('DB_USERNAME', 'root'),
        'password'   => $env('DB_PASSWORD', ''),
        'charset'    => $env('DB_CHARSET', 'utf8mb4'),
    ],
    'institution' => [
        'university' => 'Istanbul University',
        'faculty'    => 'Faculty of Economics',
        'department' => 'Management Information Systems',
        'contact'    => 'mis-alumni@istanbul.edu.tr',
    ]
];
