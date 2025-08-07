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