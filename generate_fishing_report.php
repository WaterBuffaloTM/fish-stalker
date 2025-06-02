<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateFishingReport($instagramHandles) {
    // Initialize Apify client
    $apifyClient = new ApifyClient(getenv('APIFY_TOKEN'));
    
    // Run Instagram scraper for each handle
    $allPosts = [];
    foreach ($instagramHandles as $handle) {
        $run = $apifyClient->actor("apify/instagram-scraper")->call([
            "usernames" => [$handle],
            "resultsLimit" => 10
        ]);
        
        // Wait for the run to complete
        $dataset = $apifyClient->dataset($run->data->defaultDatasetId);
        $items = $dataset->listItems()->items;
        $allPosts = array_merge($allPosts, $items);
    }
    
    // Generate report using GPT-4
    $openai = new OpenAI([
        'api_key' => getenv('OPENAI_API_KEY')
    ]);
    
    $prompt = "Generate a concise fishing report based on these recent posts:\n\n";
    foreach ($allPosts as $post) {
        $prompt .= "Location: " . ($post['locationName'] ?? 'Unknown') . "\n";
        $prompt .= "Date: " . ($post['timestamp'] ?? 'Unknown') . "\n";
        $prompt .= "Post: " . ($post['caption'] ?? '') . "\n\n";
    }
    
    $response = $openai->chat->completions->create([
        'model' => 'gpt-4',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a professional fishing report writer. Create concise, informative reports focusing on fishing conditions, catches, and locations.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ]);
    
    return $response->choices[0]->message->content;
}

function sendFishingReport($email, $report) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USERNAME');
        $mail->Password = getenv('EMAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@fishstalker.com', 'Fish Stalker');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Fishing Report - Fish Stalker';
        $mail->Body = "
            <h1>Your Fishing Report</h1>
            <div style='background-color: #f5f5f5; padding: 20px; border-radius: 8px;'>
                " . nl2br(htmlspecialchars($report)) . "
            </div>
            <p>Thank you for using Fish Stalker!</p>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending email: " . $mail->ErrorInfo);
        return false;
    }
}

// API endpoint to generate and send report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    try {
        // Get user's watchlist
        $stmt = $pdo->prepare("SELECT instagram_link FROM watchlist WHERE email = ?");
        $stmt->execute([$email]);
        $watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($watchlist)) {
            echo json_encode(['success' => false, 'message' => 'No boats found in your watchlist']);
            exit;
        }
        
        // Extract Instagram handles
        $instagramHandles = array_map(function($item) {
            preg_match('/instagram\.com\/([^\/\?]+)/', $item['instagram_link'], $matches);
            return $matches[1] ?? '';
        }, $watchlist);
        
        // Generate report
        $report = generateFishingReport($instagramHandles);
        
        // Send email
        if (sendFishingReport($email, $report)) {
            echo json_encode(['success' => true, 'message' => 'Report sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send report']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
    }
}
?> 