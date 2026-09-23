<?php
/**
 * Application & Database Configuration
 * Istanbul University MIS Alumni Portal
 */

return [
    'app' => [
        'name' => 'Istanbul University MIS Alumni Portal',
        'env'  => 'development',
        'base_url' => 'http://localhost:8000',
    ],
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'database' => 'iu_mis_alumni',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'institution' => [
        'university' => 'Istanbul University',
        'faculty'    => 'Faculty of Economics',
        'department' => 'Management Information Systems',
        'contact'    => 'mis-alumni@istanbul.edu.tr',
    ]
];
