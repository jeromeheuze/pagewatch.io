<?php
// api/delete-screenshot.php
session_start();
header('Content-Type: application/json');
include '../bin/dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$screenshot_id = intval($input['screenshot_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (empty($screenshot_id)) {
    echo json_encode(['success' => false, 'message' => 'Screenshot ID is required']);
    exit;
}

try {
    // Get screenshot details first (to verify ownership and get CDN URL)
    $stmt = $DBcon->prepare("
        SELECT id, user_id, cdn_url, status, created_at 
        FROM screenshot_jobs 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $screenshot_id, $user_id);
    $stmt->execute();
    $screenshot = $stmt->get_result()->fetch_assoc();

    if (!$screenshot) {
        echo json_encode(['success' => false, 'message' => 'Screenshot not found or access denied']);
        exit;
    }

    // Delete from CDN if exists
    $cdn_deleted = true;
    if (!empty($screenshot['cdn_url'])) {
        $cdn_deleted = deleteCDNFile($screenshot['cdn_url']);
        if (!$cdn_deleted) {
            error_log("Failed to delete CDN file: " . $screenshot['cdn_url']);
        }
    }

    // Delete from database
    $delete_stmt = $DBcon->prepare("DELETE FROM screenshot_jobs WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $screenshot_id, $user_id);

    if ($delete_stmt->execute()) {
        // Update daily usage count (reduce by 1) if the screenshot was completed
        if ($screenshot['status'] === 'completed') {
            $usage_stmt = $DBcon->prepare("
                UPDATE daily_usage 
                SET screenshots_used = GREATEST(0, screenshots_used - 1)
                WHERE user_id = ? AND date = DATE(?)
            ");
            $usage_stmt->bind_param("is", $user_id, $screenshot['created_at']);
            $usage_stmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Screenshot deleted successfully',
            'cdn_deleted' => $cdn_deleted
        ]);
    } else {
        throw new Exception('Failed to delete screenshot from database');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Delete file from CDNBunny
 */
function deleteCDNFile($cdn_url) {
    try {
        // Extract filename from CDN URL
        $filename = basename(parse_url($cdn_url, PHP_URL_PATH));
        $delete_url = "https://la.storage.bunnycdn.com/pagewatch/" . $filename;

        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => [
                    'AccessKey: 6cac3ad1-1f4a-42f2-b4012d8a3120-1640-4584',
                    'Content-Type: application/json'
                ],
                'timeout' => 30
            ]
        ]);

        $result = file_get_contents($delete_url, false, $context);

        // Check if deletion was successful
        $http_code = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $http_code = intval($matches[1]);
                    break;
                }
            }
        }

        return in_array($http_code, [200, 204, 404]); // 404 is OK (already deleted)

    } catch (Exception $e) {
        error_log("CDN deletion error: " . $e->getMessage());
        return false;
    }
}
?>