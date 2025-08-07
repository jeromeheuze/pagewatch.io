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

<?php
// api/job-status.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$job_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $DBcon->prepare("
    SELECT 
        id, status, worker_id, cdn_url, error_message, 
        created_at, started_at, completed_at,
        TIMESTAMPDIFF(SECOND, created_at, COALESCE(completed_at, NOW())) as processing_time
    FROM screenshot_jobs 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Job not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'status' => $job['status'],
    'worker_id' => $job['worker_id'],
    'cdn_url' => $job['cdn_url'],
    'error_message' => $job['error_message'],
    'processing_time' => $job['processing_time'] . ' seconds'
]);
?>

<?php
// api/check-auth.php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'authenticated' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null
]);
?>

<?php
// api/usage.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's daily limit
$limit_stmt = $DBcon->prepare("SELECT daily_screenshot_limit FROM users WHERE id = ?");
$limit_stmt->bind_param("i", $user_id);
$limit_stmt->execute();
$user = $limit_stmt->get_result()->fetch_assoc();

// Get today's usage
$usage_stmt = $DBcon->prepare("
    SELECT COALESCE(screenshots_used, 0) as used 
    FROM daily_usage 
    WHERE user_id = ? AND date = CURDATE()
");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage = $usage_stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'used' => $usage['used'] ?? 0,
    'limit' => $user['daily_screenshot_limit'] ?? 1
]);
?>