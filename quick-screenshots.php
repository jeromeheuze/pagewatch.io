<?php
session_start();
include './bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's recent quick screenshots from the job queue system
$screenshots_stmt = $DBcon->prepare("
    SELECT id, url, status, created_at, completed_at, cdn_url, error_message, worker_id,
           TIMESTAMPDIFF(SECOND, created_at, COALESCE(completed_at, NOW())) as processing_time
    FROM screenshot_jobs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$screenshots_stmt->bind_param("i", $user_id);
$screenshots_stmt->execute();
$screenshots = $screenshots_stmt->get_result();

// Get today's usage
$usage_stmt = $DBcon->prepare("
    SELECT COALESCE(screenshots_used, 0) as used 
    FROM daily_usage 
    WHERE user_id = ? AND date = CURDATE()
");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result()->fetch_assoc();

// Get user's daily limit
$limit_stmt = $DBcon->prepare("SELECT daily_screenshot_limit FROM users WHERE id = ?");
$limit_stmt->bind_param("i", $user_id);
$limit_stmt->execute();
$limit_result = $limit_stmt->get_result()->fetch_assoc();

// Get completed count properly
$completed_stmt = $DBcon->prepare("
    SELECT COUNT(*) as completed_count 
    FROM screenshot_jobs 
    WHERE user_id = ? AND status = 'completed'
");
$completed_stmt->bind_param("i", $user_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result()->fetch_assoc();

$used_today = $usage_result['used'] ?? 0;
$daily_limit = $limit_result['daily_screenshot_limit'] ?? 1;
$completed_count = $completed_result['completed_count'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Quick Screenshots – PageWatch.io</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>
    <style>
        body {
            margin: 0;
            background: #111;
            font-family: system-ui, sans-serif;
            display: flex;
            height: 100vh;
        }
        aside {
            width: 220px;
            background: #1a1a1a;
            padding: 1rem;
            border-right: 1px solid #222;
            display: flex;
            flex-direction: column;
        }
        aside .logo {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--sl-color-primary-500);
            margin-bottom: 2rem;
        }
        aside nav a {
            display: block;
            color: #ccc;
            margin: 0.5rem 0;
            text-decoration: none;
            font-size: 0.95rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        aside nav a:hover, aside nav a.active {
            color: var(--sl-color-primary-500);
            background: rgba(59, 130, 246, 0.1);
        }
        main {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        sl-card::part(base) {
            background: #1a1a1a;
            border-radius: var(--sl-border-radius-large);
            box-shadow: var(--sl-shadow-large);
        }
        .screenshot-form {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .usage-info {
            background: #1a1a1a;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .screenshots-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            margin-top: 1.5rem;
        }
        .screenshot-card {
            padding: 1.25rem;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .screenshot-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        .screenshot-url {
            color: #3b82f6;
            font-size: 0.9rem;
            word-break: break-all;
            margin-bottom: 0.75rem;
            font-weight: 500;
            line-height: 1.4;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .status-badge.pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        .status-badge.processing {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        .status-badge.completed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        .status-badge.failed {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .screenshot-preview {
            width: 100%;
            max-width: 250px;
            border-radius: 6px;
            border: 1px solid #333;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin: 0.75rem 0;
        }
        .screenshot-preview:hover {
            transform: scale(1.05);
        }
        .meta-info {
            color: #94a3b8;
            font-size: 0.8rem;
            margin: 0.75rem 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .screenshot-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        .url-input {
            flex: 1;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--sl-color-primary-400);
        }
        .stat-label {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="/">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="quick-screenshots.php" class="active">Quick Screenshots</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="color:#fff; margin: 0;">📸 Quick Screenshots</h1>
        <sl-button variant="default" size="small" onclick="location.reload()">
            Refresh
        </sl-button>
    </div>

    <!-- Usage Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $used_today; ?>/<?php echo $daily_limit; ?></div>
            <div class="stat-label">Screenshots Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $screenshots->num_rows; ?></div>
            <div class="stat-label">Total Screenshots</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $completed_count; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <!-- Screenshot Form -->
    <div class="screenshot-form">
        <h2 style="color: #fff; margin-bottom: 1rem;">Take New Screenshot</h2>
        <p style="color: #94a3b8; margin-bottom: 1.5rem;">
            Screenshots are processed by our dedicated hardware workers (NanoPi & Raspberry Pi 4)
        </p>

        <form id="screenshotForm">
            <div class="form-row">
                <sl-input
                    type="url"
                    id="urlInput"
                    placeholder="Enter website URL (e.g., https://example.com)"
                    required
                    class="url-input"
                    size="large"
                ></sl-input>
                <sl-button type="submit" variant="primary" size="large" id="submitBtn">
                    Take Screenshot
                </sl-button>
            </div>
        </form>
        <div id="statusMessage" style="margin-top: 1rem;"></div>
    </div>

    <!-- Screenshots List -->
    <?php if ($screenshots->num_rows > 0): ?>
        <h2 style="color: #fff; margin-bottom: 1.5rem;">Your Screenshots</h2>
        <div class="screenshots-grid" id="screenshotsContainer">
            <?php while ($screenshot = $screenshots->fetch_assoc()): ?>
                <div class="screenshot-card" data-job-id="<?php echo $screenshot['id']; ?>">
                    <div class="screenshot-url"><?php echo htmlspecialchars($screenshot['url']); ?></div>

                    <div class="status-badge <?php echo $screenshot['status']; ?>">
                        <?php if ($screenshot['status'] === 'processing'): ?>
                            <div class="loading-spinner"></div>
                        <?php endif; ?>

                        <?php
                        $statusIcons = [
                            'pending' => '⏳',
                            'processing' => '🔄',
                            'completed' => '✅',
                            'failed' => '❌'
                        ];
                        echo $statusIcons[$screenshot['status']] . ' ' . ucfirst($screenshot['status']);
                        ?>
                    </div>

                    <?php if ($screenshot['status'] === 'completed' && $screenshot['cdn_url']): ?>
                        <img src="<?php echo htmlspecialchars($screenshot['cdn_url']); ?>"
                             alt="Website Screenshot"
                             class="screenshot-preview"
                             onclick="window.open('<?php echo htmlspecialchars($screenshot['cdn_url']); ?>', '_blank')" />

                        <div class="screenshot-actions">
                            <sl-button variant="primary" size="small"
                                       onclick="window.open('<?php echo htmlspecialchars($screenshot['cdn_url']); ?>', '_blank')">
                                View Full Size
                            </sl-button>
                            <sl-button variant="default" size="small"
                                       onclick="copyToClipboard('<?php echo htmlspecialchars($screenshot['cdn_url']); ?>')">
                                Copy URL
                            </sl-button>
                            <sl-button variant="default" size="small"
                                       onclick="downloadScreenshot('<?php echo htmlspecialchars($screenshot['cdn_url']); ?>', '<?php echo htmlspecialchars(parse_url($screenshot['url'], PHP_URL_HOST)); ?>')">
                                Download
                            </sl-button>
                            <sl-button variant="danger" size="small"
                                       onclick="deleteScreenshot(<?php echo $screenshot['id']; ?>)">
                                Delete
                            </sl-button>
                        </div>
                    <?php endif; ?>

                    <?php if ($screenshot['status'] === 'failed'): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; padding: 1rem; margin: 1rem 0;">
                            <strong style="color: #ef4444;">Error Details:</strong>
                            <br>
                            <span style="color: #fca5a5;"><?php echo htmlspecialchars($screenshot['error_message'] ?: 'Unknown error occurred'); ?></span>
                        </div>
                        <div class="screenshot-actions">
                            <sl-button variant="primary" size="small"
                                       onclick="retryScreenshot('<?php echo htmlspecialchars($screenshot['url']); ?>')">
                                Retry
                            </sl-button>
                            <sl-button variant="danger" size="small"
                                       onclick="deleteScreenshot(<?php echo $screenshot['id']; ?>)">
                                Delete
                            </sl-button>
                        </div>
                    <?php endif; ?>

                    <div class="meta-info">
                        <span>📅 <?php echo date('M j, Y H:i', strtotime($screenshot['created_at'])); ?></span>
                        <?php if ($screenshot['worker_id']): ?>
                            <span>🤖 <?php echo htmlspecialchars($screenshot['worker_id']); ?></span>
                        <?php endif; ?>
                        <?php if ($screenshot['processing_time'] && $screenshot['status'] !== 'pending'): ?>
                            <span>⏱️ <?php echo round($screenshot['processing_time']); ?>s</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No screenshots yet</h3>
            <p>Take your first screenshot using the form above!</p>
        </div>
    <?php endif; ?>

</main>

<script>
    let pollInterval;
    let activeJobs = new Set();

    // Initialize polling for processing jobs
    document.addEventListener('DOMContentLoaded', function() {
        // Find all processing jobs and start polling
        document.querySelectorAll('[data-job-id]').forEach(card => {
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge && (statusBadge.classList.contains('processing') || statusBadge.classList.contains('pending'))) {
                const jobId = card.getAttribute('data-job-id');
                activeJobs.add(jobId);
            }
        });

        if (activeJobs.size > 0) {
            startPolling();
        }
    });

    // Screenshot form handler
    document.getElementById('screenshotForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const urlInput = document.getElementById('urlInput');
        const submitBtn = document.getElementById('submitBtn');
        const statusMessage = document.getElementById('statusMessage');

        const url = urlInput.value.trim();
        if (!url) return;

        // Disable form
        submitBtn.loading = true;
        submitBtn.textContent = 'Taking Screenshot...';

        try {
            const response = await fetch('api/screenshot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: url })
            });

            const result = await response.json();

            if (result.success) {
                statusMessage.innerHTML = `
                <sl-alert variant="success" open>
                    <sl-icon slot="icon" name="check-circle"></sl-icon>
                    Screenshot queued successfully! Queue position: #${result.queue_position || 1}
                </sl-alert>
            `;

                // Add job to active polling
                activeJobs.add(result.job_id.toString());
                startPolling();

                // Refresh page after a moment to show the new job
                setTimeout(() => location.reload(), 3000);

                urlInput.value = '';
            } else {
                statusMessage.innerHTML = `
                <sl-alert variant="danger" open>
                    <sl-icon slot="icon" name="x-circle"></sl-icon>
                    ${result.message}
                </sl-alert>
            `;
            }
        } catch (error) {
            statusMessage.innerHTML = `
            <sl-alert variant="danger" open>
                <sl-icon slot="icon" name="wifi-off"></sl-icon>
                Network error. Please check your connection and try again.
            </sl-alert>
        `;
        } finally {
            submitBtn.loading = false;
            submitBtn.textContent = 'Take Screenshot';
        }
    });

    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);

        if (activeJobs.size === 0) return;

        pollInterval = setInterval(async () => {
            const jobsToCheck = Array.from(activeJobs);

            for (const jobId of jobsToCheck) {
                try {
                    const response = await fetch(`api/job-status.php?id=${jobId}`);
                    const result = await response.json();

                    if (result.success) {
                        if (result.status === 'completed' || result.status === 'failed') {
                            activeJobs.delete(jobId);
                            // Refresh the page to show the updated status
                            location.reload();
                        }
                    }
                } catch (error) {
                    console.error(`Error checking job ${jobId}:`, error);
                }
            }

            // Stop polling if no active jobs
            if (activeJobs.size === 0) {
                clearInterval(pollInterval);
            }
        }, 3000); // Check every 3 seconds
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('success', 'URL copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('success', 'URL copied to clipboard!');
        });
    }

    function downloadScreenshot(url, filename) {
        const link = document.createElement('a');
        link.href = url;
        link.download = `${filename || 'screenshot'}-${Date.now()}.png`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('success', 'Download started!');
    }

    async function deleteScreenshot(jobId) {
        if (!confirm('Are you sure you want to delete this screenshot? This action cannot be undone.')) return;

        try {
            const response = await fetch('api/delete-screenshot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ screenshot_id: jobId })
            });

            const result = await response.json();

            if (result.success) {
                // Remove the card from the page
                const card = document.querySelector(`[data-job-id="${jobId}"]`);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'translateX(-100%)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }

                showToast('success', 'Screenshot deleted successfully!');
            } else {
                showToast('danger', 'Error: ' + result.message);
            }
        } catch (error) {
            showToast('danger', 'Network error while deleting screenshot');
        }
    }

    function retryScreenshot(url) {
        document.getElementById('urlInput').value = url;
        document.getElementById('urlInput').scrollIntoView({ behavior: 'smooth' });
        showToast('primary', 'URL filled in form above - click "Take Screenshot" to retry');
    }

    function showToast(variant, message) {
        const toast = Object.assign(document.createElement('sl-alert'), {
            variant: variant,
            duration: 4000,
            innerHTML: `<sl-icon slot="icon" name="${variant === 'success' ? 'check' : variant === 'danger' ? 'x' : 'info'}"></sl-icon>${message}`
        });
        document.body.appendChild(toast);
        toast.show();
    }

    // Clean up polling on page unload
    window.addEventListener('beforeunload', () => {
        if (pollInterval) clearInterval(pollInterval);
    });

    // Auto-refresh every 30 seconds if there are active jobs
    setInterval(() => {
        if (activeJobs.size > 0) {
            console.log(`Auto-refreshing: ${activeJobs.size} active jobs`);
            location.reload();
        }
    }, 30000);
</script>

</body>
</html>