<?php
// api/fail-job.php
header('Content-Type: application/json');
include '../bin/dbconnect.php';
include '../includes/QueueManager.php';

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = $input['worker_id'] ?? '';
$job_id = intval($input['job_id'] ?? 0);
$error_message = $input['error_message'] ?? 'Unknown error';

if (empty($worker_id) || empty($job_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $queueManager = new QueueManager($DBcon);

    if ($queueManager->failJob($job_id, $error_message)) {
        // Update worker stats
        $stmt = $DBcon->prepare("
            UPDATE workers 
            SET jobs_failed = jobs_failed + 1, last_heartbeat = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("s", $worker_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to mark job as failed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>