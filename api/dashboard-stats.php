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

<?php
// api/user-screenshots.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = intval($_GET['limit'] ?? 20);

try {
    $stmt = $DBcon->prepare("
        SELECT 
            id, url, status, created_at, completed_at, cdn_url, error_message, worker_id,
            TIMESTAMPDIFF(SECOND, created_at, COALESCE(completed_at, NOW())) as processing_time
        FROM screenshot_jobs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $screenshots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'screenshots' => $screenshots
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
// api/worker-stats.php (for the workers page)
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    // Get all workers with their stats
    $workers_stmt = $DBcon->query("
        SELECT 
            id, name, ip_address, status, last_heartbeat,
            jobs_completed, jobs_failed, created_at,
            TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) as seconds_since_heartbeat
        FROM workers 
        ORDER BY last_heartbeat DESC
    ");
    $workers = $workers_stmt->fetch_all(MYSQLI_ASSOC);

    // Get queue stats by status
    $queue_stmt = $DBcon->query("
        SELECT 
            status,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(completed_at, NOW()))) as avg_duration
        FROM screenshot_jobs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY status
    ");
    $queue_stats = $queue_stmt->fetch_all(MYSQLI_ASSOC);

    // Get recent job activity
    $recent_stmt = $DBcon->query("
        SELECT 
            j.id, j.url, j.status, j.worker_id, j.created_at, j.completed_at,
            TIMESTAMPDIFF(SECOND, j.created_at, COALESCE(j.completed_at, NOW())) as duration
        FROM screenshot_jobs j
        WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY j.created_at DESC
        LIMIT 10
    ");
    $recent_jobs = $recent_stmt->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'workers' => $workers,
        'queue_stats' => $queue_stats,
        'recent_jobs' => $recent_jobs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>