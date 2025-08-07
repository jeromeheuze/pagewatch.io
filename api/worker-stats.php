<?php
// api/worker-stats.php
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