<?php
// includes/QueueManager.php
class QueueManager {
    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     * Add a screenshot job to the queue
     */
    public function addJob($user_id, $url, $priority = 0) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid URL format");
        }

        // Check daily limit
        if (!$this->checkDailyLimit($user_id)) {
            throw new Exception("Daily screenshot limit reached");
        }

        // Insert job
        $stmt = $this->db->prepare("INSERT INTO screenshot_jobs (user_id, url, priority) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $url, $priority);

        if ($stmt->execute()) {
            $job_id = $this->db->insert_id;
            $this->updateDailyUsage($user_id);
            return $job_id;
        }

        throw new Exception("Failed to queue screenshot job");
    }

    /**
     * Get next job for a worker
     */
    public function getNextJob($worker_id) {
        $this->db->begin_transaction();

        try {
            // Find the highest priority pending job
            $stmt = $this->db->prepare("
                SELECT id, user_id, url 
                FROM screenshot_jobs 
                WHERE status = 'pending' 
                ORDER BY priority DESC, created_at ASC 
                LIMIT 1 
                FOR UPDATE
            ");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($job = $result->fetch_assoc()) {
                // Mark as processing
                $update_stmt = $this->db->prepare("
                    UPDATE screenshot_jobs 
                    SET status = 'processing', worker_id = ?, started_at = NOW() 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("si", $worker_id, $job['id']);
                $update_stmt->execute();

                $this->db->commit();
                return $job;
            }

            $this->db->commit();
            return null;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Mark job as completed
     */
    public function completeJob($job_id, $cdn_url) {
        $stmt = $this->db->prepare("
            UPDATE screenshot_jobs 
            SET status = 'completed', completed_at = NOW(), cdn_url = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $cdn_url, $job_id);
        return $stmt->execute();
    }

    /**
     * Mark job as failed
     */
    public function failJob($job_id, $error_message) {
        $stmt = $this->db->prepare("
            UPDATE screenshot_jobs 
            SET status = 'failed', completed_at = NOW(), error_message = ?, retry_count = retry_count + 1 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $error_message, $job_id);
        $stmt->execute();

        // Check if should retry
        $check_stmt = $this->db->prepare("SELECT retry_count, max_retries FROM screenshot_jobs WHERE id = ?");
        $check_stmt->bind_param("i", $job_id);
        $check_stmt->execute();
        $job = $check_stmt->get_result()->fetch_assoc();

        if ($job && $job['retry_count'] < $job['max_retries']) {
            // Reset to pending for retry
            $retry_stmt = $this->db->prepare("UPDATE screenshot_jobs SET status = 'pending', worker_id = NULL WHERE id = ?");
            $retry_stmt->bind_param("i", $job_id);
            $retry_stmt->execute();
        }
    }

    /**
     * Check if user has reached daily limit
     */
    private function checkDailyLimit($user_id) {
        // Get user's daily limit
        $limit_stmt = $this->db->prepare("SELECT daily_screenshot_limit FROM users WHERE id = ?");
        $limit_stmt->bind_param("i", $user_id);
        $limit_stmt->execute();
        $user = $limit_stmt->get_result()->fetch_assoc();

        if (!$user) return false;

        // Check today's usage
        $usage_stmt = $this->db->prepare("
            SELECT screenshots_used 
            FROM daily_usage 
            WHERE user_id = ? AND date = CURDATE()
        ");
        $usage_stmt->bind_param("i", $user_id);
        $usage_stmt->execute();
        $usage = $usage_stmt->get_result()->fetch_assoc();

        $used_today = $usage ? $usage['screenshots_used'] : 0;
        return $used_today < $user['daily_screenshot_limit'];
    }

    /**
     * Update daily usage counter
     */
    private function updateDailyUsage($user_id) {
        $stmt = $this->db->prepare("
            INSERT INTO daily_usage (user_id, date, screenshots_used) 
            VALUES (?, CURDATE(), 1)
            ON DUPLICATE KEY UPDATE screenshots_used = screenshots_used + 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    /**
     * Get user's job history
     */
    public function getUserJobs($user_id, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT id, url, status, created_at, completed_at, cdn_url, error_message
            FROM screenshot_jobs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(completed_at, NOW()))) as avg_duration
            FROM screenshot_jobs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY status
        ");
        return $stmt->fetch_all(MYSQLI_ASSOC);
    }
}