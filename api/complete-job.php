<?php
// api/complete-job.php
header('Content-Type: application/json');
include '../bin/dbconnect.php';
include '../includes/QueueManager.php';

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = $input['worker_id'] ?? '';
$job_id = intval($input['job_id'] ?? 0);
$cdn_url = $input['cdn_url'] ?? '';

if (empty($worker_id) || empty($job_id) || empty($cdn_url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $queueManager = new QueueManager($DBcon);

    if ($queueManager->completeJob($job_id, $cdn_url)) {
        // Update worker stats
        $stmt = $DBcon->prepare("
            UPDATE workers 
            SET jobs_completed = jobs_completed + 1, last_heartbeat = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("s", $worker_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to mark job as completed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>