<?php
// api/dashboard-stats.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Today's screenshot count for this user
    $today_stmt = $DBcon->prepare("
        SELECT COALESCE(screenshots_used, 0) as count 
        FROM daily_usage 
        WHERE user_id = ? AND date = CURDATE()
    ");
    $today_stmt->bind_param("i", $user_id);
    $today_stmt->execute();
    $today_result = $today_stmt->get_result()->fetch_assoc();

    // Total screenshots for this user
    $total_stmt = $DBcon->prepare("
        SELECT COUNT(*) as count 
        FROM screenshot_jobs 
        WHERE user_id = ?
    ");
    $total_stmt->bind_param("i", $user_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result()->fetch_assoc();

    // Current queue length
    $queue_stmt = $DBcon->query("
        SELECT COUNT(*) as count 
        FROM screenshot_jobs 
        WHERE status IN ('pending', 'processing')
    ");
    $queue_result = $queue_stmt->fetch_assoc();

    // Active workers count
    $workers_stmt = $DBcon->query("
        SELECT COUNT(*) as count 
        FROM workers 
        WHERE status = 'online' 
        AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    ");
    $workers_result = $workers_stmt->fetch_assoc();

    echo json_encode([
        'success' => true,
        'today_count' => $today_result['count'] ?? 0,
        'total_count' => $total_result['count'] ?? 0,
        'queue_count' => $queue_result['count'] ?? 0,
        'active_workers' => $workers_result['count'] ?? 0
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>