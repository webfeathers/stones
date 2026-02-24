<?php
/**
 * Application Configuration
 *
 * Copy this file to config.php and update the values for your environment.
 * config.php is excluded from git to protect credentials.
 */

return [
    // Database
    'db' => [
        'host'     => 'mysql.stones.webfeathers.com',
        'port'     => 3306,
        'name'     => 'stones',
        'user'     => 'amberlys_stones',
        'password' => 'your_database_password',  // ← Set your password here
        'charset'  => 'utf8mb4',
    ],

    // Site
    'site' => [
        'name'     => 'Gem & Mineral Collection',
        'url'      => 'https://stones.webfeathers.com',
        'timezone' => 'America/Los_Angeles',
    ],

    // Uploads
    'uploads' => [
        'max_file_size'   => 10 * 1024 * 1024, // 10MB
        'allowed_types'   => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'thumbnail_width' => 400,
        'thumbnail_height'=> 400,
        'max_width'       => 2048,
        'originals_dir'   => __DIR__ . '/../public/uploads/originals',
        'thumbs_dir'      => __DIR__ . '/../public/uploads/thumbs',
    ],

    // Pagination
    'per_page' => 24,

    // Session
    'session' => [
        'name'     => 'gem_tracker_session',
        'lifetime' => 86400,
    ],

    // Security
    'security' => [
        'csrf_token_name' => '_csrf_token',
    ],
];
