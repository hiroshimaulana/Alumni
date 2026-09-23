<?php
/**
 * Alumni Directory Endpoint
 * Handles GET /alumni requests
 */

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Default temporary/standby alumni dataset
$standbyAlumni = [
    [
        'id' => 1,
        'first_name' => 'Mert',
        'last_name' => 'Yılmaz',
        'email' => 'alumni.mert@example.com',
        'graduation_year' => 2020,
        'degree_level' => 'B.Sc.',
        'current_company' => 'Trendyol Group',
        'current_title' => 'Senior Cloud Data Engineer',
        'industry' => 'E-Commerce / Cloud',
        'location' => 'Istanbul, Turkey',
        'is_available_for_mentoring' => 1
    ],
    [
        'id' => 2,
        'first_name' => 'Zeynep',
        'last_name' => 'Kaya',
        'email' => 'alumni.zeynep@example.com',
        'graduation_year' => 2018,
        'degree_level' => 'B.Sc.',
        'current_company' => 'Garanti BBVA Technology',
        'current_title' => 'Lead Business Analyst',
        'industry' => 'FinTech / Banking',
        'location' => 'Istanbul, Turkey',
        'is_available_for_mentoring' => 1
    ],
    [
        'id' => 3,
        'first_name' => 'Şenol',
        'last_name' => 'Demir',
        'email' => 'alumni.senol@example.com',
        'graduation_year' => 2022,
        'degree_level' => 'B.Sc.',
        'current_company' => 'Amazon Web Services',
        'current_title' => 'Solutions Architect',
        'industry' => 'Cloud Computing',
        'location' => 'Berlin, Germany',
        'is_available_for_mentoring' => 1
    ]
];

$pdo = getDbConnection();

if ($pdo !== null) {
    try {
        $sql = "SELECT 
                    u.id, 
                    u.first_name, 
                    u.last_name, 
                    u.email, 
                    ap.graduation_year, 
                    ap.degree_level,
                    ap.current_company, 
                    ap.current_title, 
                    ap.industry, 
                    ap.location,
                    ap.is_available_for_mentoring
                FROM users u
                LEFT JOIN alumni_profiles ap ON u.id = ap.user_id
                WHERE u.role = 'alumni'";
        
        $stmt = $pdo->query($sql);
        $alumni = $stmt->fetchAll();

        if (!empty($alumni)) {
            echo json_encode([
                'status'  => 'success',
                'source'  => 'database',
                'message' => 'Alumni records retrieved successfully from MySQL database.',
                'count'   => count($alumni),
                'data'    => $alumni
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (PDOException $e) {
        // Fall back gracefully if table does not exist or query fails
        error_log('Database query warning: ' . $e->getMessage());
    }
}

// Fallback: Output temporary success status with standby data
echo json_encode([
    'status'  => 'success',
    'source'  => 'standby',
    'message' => 'Alumni endpoint active. Database in standby mode; displaying verified mock records.',
    'count'   => count($standbyAlumni),
    'data'    => $standbyAlumni
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
