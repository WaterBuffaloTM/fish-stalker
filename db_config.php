<?php
// Database configuration
$host = 'localhost';     // Hostinger database host
$dbname = 'u523883027_everythingoryx';  // Your new database name
$username = 'u523883027_everythingoryx';  // Your new database username
$password = 'CapBuff1999!';  // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 