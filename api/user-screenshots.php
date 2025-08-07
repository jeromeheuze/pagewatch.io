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