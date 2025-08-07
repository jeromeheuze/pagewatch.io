<?php
// api/screenshot.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';
include '../includes/QueueManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL is required']);
    exit;
}

// Validate and sanitize URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
    exit;
}

// Block private/local URLs for security
$parsed_url = parse_url($url);
$host = $parsed_url['host'] ?? '';

if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0']) ||
    preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $host)) {
    echo json_encode(['success' => false, 'message' => 'Private/local URLs are not allowed']);
    exit;
}

try {
    $queueManager = new QueueManager($DBcon);
    $job_id = $queueManager->addJob($_SESSION['user_id'], $url);

    // Get queue position
    $position_stmt = $DBcon->prepare("
        SELECT COUNT(*) as position 
        FROM screenshot_jobs 
        WHERE status = 'pending' AND id <= ? 
        ORDER BY priority DESC, created_at ASC
    ");
    $position_stmt->bind_param("i", $job_id);
    $position_stmt->execute();
    $position_result = $position_stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'job_id' => $job_id,
        'queue_position' => $position_result['position'] ?? 1,
        'message' => 'Screenshot queued successfully!'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>