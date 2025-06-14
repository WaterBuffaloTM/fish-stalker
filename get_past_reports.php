<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    error_log("get_past_reports.php called.");

    if (!isset($_GET['email']) || empty($_GET['email'])) {
        error_log("Email parameter is missing or empty.");
        throw new Exception('Email parameter is required');
    }

    $email = $_GET['email'];
    error_log("Fetching past reports for email: " . $email);

    // Get the last 10 reports for the user, ordered by most recent first
    $stmt = $pdo->prepare("
        SELECT id, report_content, created_at 
        FROM fishing_reports 
        WHERE email = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$email]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($reports) . " past reports for email: " . $email);

    // Format the dates for display
    foreach ($reports as &$report) {
        $report['created_at'] = date('F j, Y, g:i a', strtotime($report['created_at']));
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
} catch (Exception $e) {
    error_log("Error in get_past_reports.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 