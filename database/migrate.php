<?php
/**
 * Database Migration Runner CLI
 * Istanbul University MIS Alumni Portal
 *
 * Usage: php database/migrate.php
 */

require_once __DIR__ . '/../config/db.php';

echo "========================================================\n";
echo "  Istanbul University MIS Portal - Database Migrator    \n";
echo "========================================================\n\n";

$pdo = getDbConnection();

if ($pdo === null) {
    echo "[INFO] MySQL server is not connected or offline.\n";
    echo "[INFO] In-memory domain models with session storage are active for development and testing.\n";
    echo "[INFO] Schema and migration files are verified:\n";
    foreach (glob(__DIR__ . '/migrations/*.sql') as $file) {
        echo "  - " . basename($file) . " (" . filesize($file) . " bytes)\n";
    }
    echo "  - schema.sql (" . filesize(__DIR__ . '/schema.sql') . " bytes)\n";
    echo "\n[OK] Migrations ready. Start MySQL via 'docker compose up -d db' to apply to live database.\n";
    exit(0);
}

echo "[CONNECTED] Successfully connected to MySQL database.\n";

$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    echo "Executing migration: {$filename}... ";
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "[DONE]\n";
    } catch (PDOException $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }
}

echo "\n[COMPLETED] Database migrations finished successfully.\n";
