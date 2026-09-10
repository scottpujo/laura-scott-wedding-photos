<?php
declare(strict_types=1);

$configPath = getenv('WEDDING_PHOTO_CONFIG');

if (!$configPath) {
    $configPath = dirname(__DIR__, 2) . '/WeddingPhotoPrivate/config.php';
}

if (!is_file($configPath)) {
    throw new RuntimeException('Wedding photo application configuration was not found.');
}

$config = require $configPath;

if (!is_array($config)) {
    throw new RuntimeException('Wedding photo application configuration is invalid.');
}

date_default_timezone_set($config['app']['timezone'] ?? 'America/Chicago');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/http.php';
