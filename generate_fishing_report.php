<?php
// Set error logging
ini_set('display_errors', 0); // Don't display errors to the client
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Set maximum execution time to 5 minutes
set_time_limit(300);
ini_set('max_execution_time', 300);

require 'vendor/autoload.php';
require 'config.php';

// --- Database Connection ---
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    error_log("Database connection successful.");
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit; // Stop execution on connection error
}
// --- End Database Connection ---

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');

try {
    error_log("--- generate_fishing_report.php script started ---");

    // Get the email from the POST request body
    $data = json_decode(file_get_contents('php://input'), true);
    $userEmail = isset($data['email']) ? $data['email'] : null;

    if (!$userEmail) {
        error_log("Email not provided in POST data.");
        echo json_encode([
            'success' => false,
            'message' => 'User email is required to generate reports.'
        ]);
        exit; // Stop execution
    }

    error_log("Generating consolidated report for email: " . $userEmail);

    // Get captains in watchlist for the specific user
    $stmt = $pdo->prepare("SELECT name, instagram_link FROM watchlist WHERE email = :email");
    $stmt->execute(['email' => $userEmail]);
    $captains = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Found " . count($captains) . " captains in watchlist for email: " . $userEmail);

    if (empty($captains)) {
        error_log("No captains found in watchlist for the specified email. Exiting script.");
        echo json_encode([
            'success' => true,
            'message' => 'No captains found in your watchlist to generate a report.',
            'report' => null // Return null report for consistency
        ]);
        exit; // Stop execution
    }

    // Create a map of Instagram URL to Captain Name for easier lookup
    $urlToNameMap = [];
    $instagram_urls = [];
    foreach ($captains as $captain) {
        $url = trim($captain['instagram_link']);
        if (!empty($url)) {
             $urlToNameMap[$url] = $captain['name'];
             $instagram_urls[] = $url;
        }
    }

    // Remove duplicate URLs just in case, although DB query should prevent this if links are unique
    $instagram_urls = array_unique($instagram_urls);
    $instagram_urls = array_filter($instagram_urls); // Remove empty entries

    if (empty($instagram_urls)) {
         error_log("No valid Instagram URLs found after processing watchlist.");
         echo json_encode([
             'success' => true,
             'message' => 'No valid Instagram URLs found in your watchlist.',
             'report' => null
         ]);
         exit;
    }

    error_log("Processing unique Instagram URLs for Apify: " . implode(", ", $instagram_urls));

    // Initialize Apify client with increased timeout settings
    error_log("Initializing Apify client...");
    $client = new Client([
        'base_uri' => 'https://api.apify.com/v2/',
        'headers' => [
            'Authorization' => 'Bearer ' . APIFY_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 180, // Increased timeout as we process multiple URLs
        'connect_timeout' => 90
    ]);
    error_log("Apify client initialized.");

    // Start single Apify actor run for all URLs
    try {
        error_log("Sending request to Apify actor for " . count($instagram_urls) . " URLs...");
        $response = $client->post('actor-tasks/oldschoolmatt~fishing-report/run-sync-get-dataset-items', [
            'json' => [
                'directUrls' => array_values($instagram_urls), // Ensure values are re-indexed
                'resultsLimit' => 15 // Limit posts per account, slightly increased
            ]
        ]);
        error_log("Received response from Apify actor.");
    } catch (Exception $e) {
        error_log("Apify API Request Error: " . $e->getMessage());
        if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            error_log("Apify Error Response Body: " . $responseBody);
            $errorData = json_decode($responseBody, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($errorData['error']['message'])) {
                 throw new Exception('Apify API Error: ' . $errorData['error']['message']);
            } else {
                 throw new Exception('Error accessing Apify API: ' . $e->getMessage());
            }
        }
        throw new Exception('Error accessing Apify API: ' . $e->getMessage());
    }

    $results = json_decode($response->getBody(), true);
    error_log("Apify Results decoded. Number of individual post results: " . count($results));

    if (empty($results)) {
         error_log("No posts found for any Instagram accounts via Apify.");
         echo json_encode([
            'success' => true,
            'message' => 'No recent fishing posts found for your watchlist captains.',
            'report' => null
        ]);
        exit; // Stop execution
    }

    // Consolidate all relevant posts from all captains
    $allRelevantPosts = [];
    foreach ($results as $post) {
        $url = isset($post['inputUrl']) ? trim($post['inputUrl']) : null;
        // Only include posts from URLs that are in the user's watchlist
        if ($url && isset($urlToNameMap[$url])) {
            // Attach captain's name to the post data for context in prompt
            $post['captainName'] = $urlToNameMap[$url];
            $allRelevantPosts[] = $post;
        }
    }

    error_log("Consolidated " . count($allRelevantPosts) . " relevant posts from all captains.");

    if (empty($allRelevantPosts)) {
        error_log("No relevant posts found after filtering Apify results.");
        echo json_encode([
           'success' => true,
           'message' => 'No relevant fishing posts found from your watchlist captains.',
           'report' => null
       ]);
       exit;
    }

    // Prepare consolidated message data for ChatGPT
    $messages = [
        [
            'role' => 'system',
            'content' => 'You are a fishing report expert. Analyze the provided Instagram posts from various fishing captains. Based on their recent activity, create a single, concise fishing report summarizing:
1. What is biting (species frequently caught)
2. Recommended fishing areas (based on post locations)
3. Which captain appears to be having the best success (mention captain name and why, e.g., most fish, largest fish, most recent consistent activity).'
        ]
    ];

    foreach ($allRelevantPosts as $post) {
         // Format each post concisely for the prompt
         $captainName = $post['captainName'] ?? 'Unknown Captain';
         $postTimestamp = isset($post['timestamp']) ? date('Y-m-d H:i', strtotime($post['timestamp'])) : 'N/A';
         $postCaption = isset($post['caption']) ? substr($post['caption'], 0, 150) . (strlen($post['caption']) > 150 ? '...' : '') : '';
         $postLocation = isset($post['locationName']) ? $post['locationName'] : 'N/A';
         $postLikes = isset($post['likes']) ? $post['likes'] : 0;

         $messageContent = [
             [
                 'type' => 'text',
                 'text' => "Captain: {$captainName}\nTimestamp: {$postTimestamp}\nLocation: {$postLocation}\nLikes: {$postLikes}\nCaption Snippet: {$postCaption}"
             ]
         ];

         // Add image if available (use image_url type with base64 data URL)
         $imageUrl = isset($post['displayUrl']) ? $post['displayUrl'] : (isset($post['mediaUrl']) ? $post['mediaUrl'] : null);

         if ($imageUrl) {
              // Attempt to fetch image content with a reasonable timeout
              // Note: Fetching images in a loop for potentially many posts can be slow and resource-intensive.
              // Consider if sending just URLs or descriptions is sufficient, or optimize fetching.
              try {
                  $imageResponse = (new Client())->get($imageUrl, ['timeout' => 5, 'connect_timeout' => 3]); // Shorter timeout for images
                  if ($imageResponse->getStatusCode() === 200) {
                      $imageContent = $imageResponse->getBody()->getContents();
                      $imageMimeType = $imageResponse->getHeaderLine('Content-Type');
                      if ($imageContent !== false) {
                          $imageBase64 = base64_encode($imageContent);
                          $messageContent[] = [
                              'type' => 'image_url',
                              'image_url' => [
                                  'url' => 'data:' . ($imageMimeType ?: 'image/jpeg') . ';base64,' . $imageBase64
                              ]
                          ];
                           // error_log("Image fetched and encoded for " . $imageUrl); // Too noisy
                      } // else error logged inside fetch block
                  } // else error logged inside fetch block
              } catch (RequestException $e) {
                   error_log("Guzzle Error fetching image " . $imageUrl . ": " . $e->getMessage());
                   // Optionally log response body if $e->hasResponse()
              } catch (Exception $e) {
                   error_log("General Error fetching image " . $imageUrl . ": " . $e->getMessage());
              }
         }

         $messages[] = [
             'role' => 'user',
             'content' => $messageContent
         ];
    }

    // Add the final instruction for ChatGPT
    $messages[] = [
        'role' => 'user',
        'content' => "Generate the consolidated fishing report based on the format requested in the system prompt. Be concise." // Simplified final prompt
    ];

    error_log("Sending consolidated request to ChatGPT with " . count($messages) . " messages...");
    $chatgptClient = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 90 // Increased timeout for consolidated report
    ]);

    try {
        $chatgptResponse = $chatgptClient->post('chat/completions', [
            'json' => [
                'model' => 'gpt-4o', // Using gpt-4o for vision capabilities
                'messages' => $messages,
                'max_tokens' => 1500, // Allow more tokens for consolidated report
                'temperature' => 0.7
            ]
        ]);

        $chatgptData = json_decode($chatgptResponse->getBody(), true);
        error_log("ChatGPT Response received. Success status: " . (isset($chatgptData['choices'][0]['finish_reason']) ? $chatgptData['choices'][0]['finish_reason'] : 'N/A'));

        if (!isset($chatgptData['choices'][0]['message']['content'])) {
            error_log("ChatGPT API Error: Unexpected response structure.");
            error_log("Full ChatGPT API Error Response: " . json_encode($chatgptData));
            throw new Exception('Invalid response structure from ChatGPT API');
        }

        $consolidatedReportContent = $chatgptData['choices'][0]['message']['content'];
        error_log("Generated consolidated report: " . substr($consolidatedReportContent, 0, 300) . "..."); // Log snippet

        // Save the report to the database
        try {
            $stmt = $pdo->prepare("INSERT INTO fishing_reports (email, report_content) VALUES (:email, :report_content)");
            $result = $stmt->execute([
                'email' => $userEmail,
                'report_content' => $consolidatedReportContent
            ]);
            
            if (!$result) {
                error_log("Failed to save fishing report to database. PDO error info: " . print_r($stmt->errorInfo(), true));
                throw new PDOException("Failed to save report to database");
            }
            
            $reportId = $pdo->lastInsertId();
            error_log("Successfully saved fishing report to database. Report ID: " . $reportId);
        } catch (PDOException $e) {
            error_log("Error saving fishing report to database: " . $e->getMessage());
            throw new Exception("Failed to save report to database: " . $e->getMessage());
        }

        // Prepare the final response structure with a single report
        $response = [
            'success' => true,
            'message' => 'Consolidated report generated successfully.',
            'report' => $consolidatedReportContent // Return the single report content
        ];

    } catch (RequestException $e) {
         error_log("Guzzle HTTP Request Error calling ChatGPT: " . $e->getMessage());
         if ($e->hasResponse()) {
             $responseBody = $e->getResponse()->getBody()->getContents();
             error_log("ChatGPT Error Response Body: " . $responseBody);
             $errorData = json_decode($responseBody, true);
             if (json_last_error() === JSON_ERROR_NONE && isset($errorData['error']['message'])) {
                  throw new Exception('ChatGPT API Error: ' . $errorData['error']['message']);
             } else {
                  throw new Exception('Error calling ChatGPT API: ' . $e->getMessage());
             }
         }
         throw new Exception('Error calling ChatGPT API: ' . $e->getMessage());

    } catch (Exception $e) {
        error_log("General Error calling ChatGPT: " . $e->getMessage());
        throw new Exception('Error generating consolidated report: ' . $e->getMessage());
    }

    // Ensure proper JSON encoding
    $json_response = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json_response === false) {
        error_log("Error encoding final JSON response: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error encoding response.']);
    } else {
        error_log("Sending final JSON response.");
        echo $json_response;
    }

} catch (Exception $e) {
    error_log("Critical Error in generate_fishing_report.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A critical error occurred during report generation.' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} 