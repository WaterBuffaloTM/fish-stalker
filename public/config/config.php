<?php
// Main Configuration File

// Determine if we're in development or production
$is_development = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
    strpos($_SERVER['SERVER_NAME'], '.local') !== false
);

// Load the appropriate configuration file
if ($is_development) {
    require_once __DIR__ . '/config.development.php';
} else {
    require_once __DIR__ . '/config.production.php';
}

// Additional global configuration settings
define('SITE_URL', $is_development ? 'http://localhost:8080' : 'https://fishstalkerai.com');
define('TIMEZONE', 'America/New_York');

// Set timezone
date_default_timezone_set(TIMEZONE); 