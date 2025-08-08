<?php
// api/website-history.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$website_id = intval($_GET['website_id'] ?? 0);

if (empty($website_id)) {
    echo json_encode(['success' => false, 'message' => 'Website ID is required']);
    exit;
}

try {
    // Verify website belongs to user
    $verify_stmt = $DBcon->prepare("SELECT id FROM websites WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $website_id, $user_id);
    $verify_stmt->execute();

    if (!$verify_stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Get screenshot history for this website
    $stmt = $DBcon->prepare("
        SELECT 
            sj.id,
            sj.status,
            sj.cdn_url,
            sj.worker_id,
            sj.created_at as taken_at,
            sj.completed_at,
            TIMESTAMPDIFF(SECOND, sj.created_at, COALESCE(sj.completed_at, NOW())) as processing_time
        FROM screenshot_jobs sj
        WHERE sj.website_id = ? 
        AND sj.status IN ('completed', 'failed')
        ORDER BY sj.created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $website_id);
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