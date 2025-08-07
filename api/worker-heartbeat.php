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