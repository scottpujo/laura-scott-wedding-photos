<?php
declare(strict_types=1);

/*
 * Copy this file to ../WeddingPhotoPrivate/config.php on the server.
 * Never commit the real file containing the database password.
 */

return [
    'app' => [
        'name' => 'Laura & Scott Wedding Photos',
        'event_name' => 'Laura & Scott Wedding',
        'event_slug' => 'laura-scott-wedding',
        'event_date' => '2026-09-26',
        'base_url' => 'https://photos.lauraandscottforever.com',
        'timezone' => 'America/Chicago',
        'debug' => false,
    ],

    'database' => [
        'host' => 'YOUR_DATABASE_HOST',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'username' => 'YOUR_DATABASE_USERNAME',
        'password' => 'PUT_YOUR_DATABASE_PASSWORD_HERE',
        'charset' => 'utf8mb4',
    ],

    'uploads' => [
        // Keep below the host's 20M POST limit to leave room for multipart overhead.
        'max_file_size' => 18 * 1024 * 1024,
        'max_files_per_batch' => 20,
        'original_path' => __DIR__ . '/uploads/originals',
        'allowed_mime_types' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
        ],
    ],
];
