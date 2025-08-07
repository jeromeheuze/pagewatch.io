<?php
// api/get-job.php
header('Content-Type: application/json');
include '../bin/dbconnect.php';
include '../includes/QueueManager.php';

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = $input['worker_id'] ?? '';

if (empty($worker_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Worker ID required']);
    exit;
}

try {
    $queueManager = new QueueManager($DBcon);
    $job = $queueManager->getNextJob($worker_id);

    if ($job) {
        echo json_encode(['success' => true, 'job' => $job]);
    } else {
        echo json_encode(['success' => true, 'job' => null, 'message' => 'No jobs available']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>