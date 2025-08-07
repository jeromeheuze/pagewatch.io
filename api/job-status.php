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