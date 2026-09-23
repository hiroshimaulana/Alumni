<?php
/**
 * Database Helper
 * Istanbul University MIS Alumni Portal
 */

function getDbConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        return null;
    }

    $config = require $configFile;
    $db = $config['db'] ?? [];

    $host     = $db['host'] ?? '127.0.0.1';
    $port     = $db['port'] ?? '3306';
    $database = $db['database'] ?? 'iu_mis_alumni';
    $username = $db['username'] ?? 'root';
    $password = $db['password'] ?? '';
    $charset  = $db['charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2, // 2 seconds timeout
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // If MySQL server is offline or database is not yet migrated, fail gracefully
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}
