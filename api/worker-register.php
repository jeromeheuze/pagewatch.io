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