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

<?php
// api/worker-register.php
header('Content-Type: application/json');
include '../bin/dbconnect.php';

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = $input['worker_id'] ?? '';
$name = $input['name'] ?? '';
$ip_address = $input['ip_address'] ?? '';

if (empty($worker_id) || empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Worker ID and name required']);
    exit;
}

try {
    $stmt = $DBcon->prepare("
        INSERT INTO workers (id, name, ip_address, status, last_heartbeat) 
        VALUES (?, ?, ?, 'online', NOW())
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            ip_address = VALUES(ip_address),
            status = 'online',
            last_heartbeat = NOW()
    ");
    $stmt->bind_param("sss", $worker_id, $name, $ip_address);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Worker registered']);
    } else {
        throw new Exception('Database error');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
// api/worker-heartbeat.php
header('Content-Type: application/json');
include '../bin/dbconnect.php';

$input = json_decode(file_get_contents('php://input'), true);
$worker_id = $input['worker_id'] ?? '';
$status = $input['status'] ?? 'online';

if (empty($worker_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Worker ID required']);
    exit;
}

try {
    $stmt = $DBcon->prepare("
        UPDATE workers 
        SET status = ?, last_heartbeat = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ss", $status, $worker_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>