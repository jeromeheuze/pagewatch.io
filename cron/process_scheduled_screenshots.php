<?php
// cron/process_scheduled_screenshots.php
// Run this script every 5 minutes via cron: */5 * * * * /usr/local/bin/ea-php84 /home/spectrum/public_html/pagewatch.io/cron/process_scheduled_screenshots.php

include __DIR__ . '/../bin/dbconnect.php';

$log_file = __DIR__ . '/scheduled_screenshots.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("Starting scheduled screenshot processor");

try {
    // Find websites that need screenshots
    $stmt = $DBcon->query("
        SELECT w.id, w.user_id, w.url, w.name, w.frequency, w.next_screenshot_at
        FROM websites w
        WHERE w.is_active = 1 
        AND w.next_screenshot_at <= NOW()
        AND w.next_screenshot_at IS NOT NULL
        ORDER BY w.next_screenshot_at ASC
        LIMIT 50
    ");

    $websites_to_process = $stmt->fetch_all(MYSQLI_ASSOC);

    if (empty($websites_to_process)) {
        log_message("No websites need screenshots at this time");
        exit(0);
    }

    log_message("Found " . count($websites_to_process) . " websites needing screenshots");

    foreach ($websites_to_process as $website) {
        try {
            // Check if user is within daily limits
            $usage_stmt = $DBcon->prepare("
                SELECT 
                    u.daily_screenshot_limit,
                    COALESCE(du.screenshots_used, 0) as used_today
                FROM users u
                LEFT JOIN daily_usage du ON u.id = du.user_id AND du.date = CURDATE()
                WHERE u.id = ?
            ");
            $usage_stmt->bind_param("i", $website['user_id']);
            $usage_stmt->execute();
            $usage = $usage_stmt->get_result()->fetch_assoc();

            if ($usage['used_today'] >= $usage['daily_screenshot_limit']) {
                log_message("User {$website['user_id']} has reached daily limit, skipping website {$website['id']}");

                // Schedule for tomorrow
                $next_time = date('Y-m-d H:i:s', strtotime('+1 day'));
                $update_stmt = $DBcon->prepare("UPDATE websites SET next_screenshot_at = ? WHERE id = ?");
                $update_stmt->bind_param("si", $next_time, $website['id']);
                $update_stmt->execute();
                continue;
            }

            // Create screenshot job
            $job_stmt = $DBcon->prepare("
                INSERT INTO screenshot_jobs (user_id, website_id, url, priority, is_scheduled) 
                VALUES (?, ?, ?, 2, 1)
            ");
            $job_stmt->bind_param("iis", $website['user_id'], $website['id'], $website['url']);

            if ($job_stmt->execute()) {
                $job_id = $DBcon->insert_id;
                log_message("Created screenshot job #{$job_id} for website {$website['name']} ({$website['url']})");

                // Update daily usage
                $usage_update = $DBcon->prepare("
                    INSERT INTO daily_usage (user_id, date, screenshots_used) 
                    VALUES (?, CURDATE(), 1)
                    ON DUPLICATE KEY UPDATE screenshots_used = screenshots_used + 1
                ");
                $usage_update->bind_param("i", $website['user_id']);
                $usage_update->execute();

                // Calculate next screenshot time based on frequency
                $next_time = '';
                switch ($website['frequency']) {
                    case 'hourly':
                        $next_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        break;
                    case 'daily':
                        $next_time = date('Y-m-d H:i:s', strtotime('+1 day'));
                        break;
                    case 'weekly':
                    default:
                        $next_time = date('Y-m-d H:i:s', strtotime('+1 week'));
                        break;
                }

                // Update next screenshot time
                $update_stmt = $DBcon->prepare("
                    UPDATE websites 
                    SET next_screenshot_at = ?, last_screenshot_at = NOW() 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("si", $next_time, $website['id']);
                $update_stmt->execute();

                log_message("Next screenshot for {$website['name']} scheduled for {$next_time}");

            } else {
                log_message("Failed to create screenshot job for website {$website['id']}");
            }

        } catch (Exception $e) {
            log_message("Error processing website {$website['id']}: " . $e->getMessage());
        }
    }

    log_message("Scheduled screenshot processor completed successfully");

} catch (Exception $e) {
    log_message("Fatal error in scheduled screenshot processor: " . $e->getMessage());
    exit(1);
}

// Clean up old screenshots based on retention policies
try {
    log_message("Starting cleanup of old screenshots");

    $cleanup_stmt = $DBcon->query("
        DELETE sj FROM screenshot_jobs sj
        INNER JOIN users u ON sj.user_id = u.id
        WHERE sj.completed_at < DATE_SUB(NOW(), INTERVAL u.screenshot_retention_days DAY)
        AND sj.status = 'completed'
    ");

    $deleted_count = $DBcon->affected_rows;
    if ($deleted_count > 0) {
        log_message("Cleaned up {$deleted_count} old screenshots");
    }

} catch (Exception $e) {
    log_message("Error during cleanup: " . $e->getMessage());
}

log_message("Process completed");
?>