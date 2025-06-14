<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Create watchlist table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS watchlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        instagram_link VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        boat_type VARCHAR(50) NOT NULL,
        city VARCHAR(100) NOT NULL,
        region VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    error_log("Watchlist table check completed");

    // Create fishing_reports table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS fishing_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        report_content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_created_at (created_at)
    )");
    error_log("Fishing reports table check completed");
} catch (PDOException $e) {
    error_log("Error creating database tables: " . $e->getMessage());
    die("Error setting up database. Please contact support.");
} 