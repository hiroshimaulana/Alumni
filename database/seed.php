<?php
/**
 * Database Seed CLI Runner
 * Istanbul University MIS Alumni Portal
 *
 * Usage: php database/seed.php
 */

require_once __DIR__ . '/seeders/JobPostingSeeder.php';

echo "========================================================\n";
echo "  Istanbul University MIS Portal - Database Seeder      \n";
echo "========================================================\n\n";

$seeder = new JobPostingSeeder();
$results = $seeder->run();

echo "[SUCCESS] Seeding completed:\n";
echo "  - Total Job Postings in storage: {$results['jobs_count']}\n";
echo "  - Total Applications in storage: {$results['apps_count']}\n";
if ($results['db_jobs'] > 0 || $results['db_apps'] > 0) {
    echo "  - Live MySQL records inserted: {$results['db_jobs']} jobs, {$results['db_apps']} applications\n";
} else {
    echo "  - Storage: In-memory domain models with session sync ready.\n";
}

echo "\n[OK] Database seeding finished successfully.\n";
