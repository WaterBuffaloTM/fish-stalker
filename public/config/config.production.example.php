<?php
// Production Environment Configuration Template
// Copy this file to config.production.php and fill in your actual values

// API Configuration
define('APIFY_API_KEY', 'YOUR_APIFY_API_KEY');
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DATABASE_NAME');
define('DB_USER', 'YOUR_DATABASE_USER');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

// Application Settings
define('APP_ENV', 'production');
define('APP_DEBUG', false); 