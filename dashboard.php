<?php
session_start();
include './bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info with plan details
$stmt = $DBcon->prepare("SELECT email, plan, daily_screenshot_limit, max_websites, screenshot_retention_days, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set default values if columns don't exist yet
$user['max_websites'] = $user['max_websites'] ?? ($user['plan'] === 'free' ? 3 : ($user['plan'] === 'starter' ? 10 : 50));
$user['screenshot_retention_days'] = $user['screenshot_retention_days'] ?? ($user['plan'] === 'free' ? 7 : ($user['plan'] === 'starter' ? 30 : 90));

// Get today's screenshot usage
$usage_stmt = $DBcon->prepare("
    SELECT COALESCE(screenshots_used, 0) as used 
    FROM daily_usage 
    WHERE user_id = ? AND date = CURDATE()
");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result()->fetch_assoc();
$daily_used = $usage_result['used'] ?? 0;

// Handle form submissions
$message = '';
$message_type = 'primary';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_website') {
        $url = trim($_POST['url']);
        $name = trim($_POST['name']) ?: parse_url($url, PHP_URL_HOST);
        $frequency = $_POST['frequency'] ?? 'weekly';

        // Check URL limit
        $count_stmt = $DBcon->prepare("SELECT COUNT(*) as count FROM websites WHERE user_id = ? AND is_active = 1");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $current_count = $count_stmt->get_result()->fetch_assoc()['count'];

        if ($current_count >= $user['max_websites']) {
            $message = "You've reached your plan limit of {$user['max_websites']} websites. <a href='upgrade.php' style='color: #3b82f6;'>Upgrade</a> to add more.";
            $message_type = 'warning';
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
            $message = "Please enter a valid URL.";
            $message_type = 'danger';
        } else {
            // Calculate next screenshot time
            $next_screenshot = date('Y-m-d H:i:s', strtotime('+1 ' . $frequency));

            $stmt = $DBcon->prepare("INSERT INTO websites (user_id, url, name, frequency, next_screenshot_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $url, $name, $frequency, $next_screenshot);

            if ($stmt->execute()) {
                $message = "Website added successfully! First screenshot will be taken within the next few minutes.";
                $message_type = 'success';

                // Schedule immediate first screenshot
                $website_id = $DBcon->insert_id;
                $job_stmt = $DBcon->prepare("INSERT INTO screenshot_jobs (user_id, website_id, url, priority, is_scheduled) VALUES (?, ?, ?, 1, 1)");
                $job_stmt->bind_param("iis", $user_id, $website_id, $url);
                $job_stmt->execute();
            } else {
                $message = "Error adding website.";
                $message_type = 'danger';
            }
        }
    }

    elseif ($_POST['action'] === 'take_screenshot') {
        $website_id = intval($_POST['website_id']);

        // Get website URL
        $url_stmt = $DBcon->prepare("SELECT url FROM websites WHERE id = ? AND user_id = ?");
        $url_stmt->bind_param("ii", $website_id, $user_id);
        $url_stmt->execute();
        $website = $url_stmt->get_result()->fetch_assoc();

        if ($website && $daily_used < $user['daily_screenshot_limit']) {
            $stmt = $DBcon->prepare("INSERT INTO screenshot_jobs (user_id, website_id, url, priority) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iis", $user_id, $website_id, $website['url']);

            if ($stmt->execute()) {
                // Update daily usage
                $usage_update = $DBcon->prepare("
                    INSERT INTO daily_usage (user_id, date, screenshots_used) 
                    VALUES (?, CURDATE(), 1)
                    ON DUPLICATE KEY UPDATE screenshots_used = screenshots_used + 1
                ");
                $usage_update->bind_param("i", $user_id);
                $usage_update->execute();

                $daily_used++;
                $message = "Screenshot queued successfully!";
                $message_type = 'success';
            } else {
                $message = "Error queuing screenshot.";
                $message_type = 'danger';
            }
        } elseif ($daily_used >= $user['daily_screenshot_limit']) {
            $message = "Daily screenshot limit reached.";
            $message_type = 'warning';
        }
    }

    elseif ($_POST['action'] === 'delete_website') {
        $website_id = intval($_POST['website_id']);

        $stmt = $DBcon->prepare("UPDATE websites SET is_active = 0 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $website_id, $user_id);

        if ($stmt->execute()) {
            $message = "Website removed from monitoring.";
            $message_type = 'success';
        }
    }

    elseif ($_POST['action'] === 'quick_screenshot') {
        $url = trim($_POST['url']);

        if ($url && filter_var($url, FILTER_VALIDATE_URL) && $daily_used < $user['daily_screenshot_limit']) {
            $stmt = $DBcon->prepare("INSERT INTO screenshot_jobs (user_id, url, priority) VALUES (?, ?, 1)");
            $stmt->bind_param("is", $user_id, $url);

            if ($stmt->execute()) {
                // Update daily usage
                $usage_update = $DBcon->prepare("
                    INSERT INTO daily_usage (user_id, date, screenshots_used) 
                    VALUES (?, CURDATE(), 1)
                    ON DUPLICATE KEY UPDATE screenshots_used = screenshots_used + 1
                ");
                $usage_update->bind_param("i", $user_id);
                $usage_update->execute();

                $daily_used++;
                $job_id = $DBcon->insert_id;
                $message = "Screenshot queued successfully! Job ID: #$job_id";
                $message_type = 'success';
            }
        }
    }
}

// Get user's websites
$websites_stmt = $DBcon->prepare("
    SELECT w.*, 
           COUNT(sj.id) as total_screenshots,
           MAX(sj.completed_at) as last_screenshot_at
    FROM websites w 
    LEFT JOIN screenshot_jobs sj ON w.id = sj.website_id AND sj.status = 'completed'
    WHERE w.user_id = ? AND w.is_active = 1 
    GROUP BY w.id 
    ORDER BY w.created_at DESC
");
$websites_stmt->bind_param("i", $user_id);
$websites_stmt->execute();
$websites = $websites_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard – PageWatch.io</title>
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

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .plan-info {
            background: #1a1a1a;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .plan-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .plan-free { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .plan-starter { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .plan-pro { background: rgba(16, 185, 129, 0.2); color: #10b981; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        sl-card::part(base) {
            background: #1a1a1a;
            border-radius: var(--sl-border-radius-large);
            box-shadow: var(--sl-shadow-large);
            border: 1px solid #333;
        }

        .websites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .website-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.2s;
        }

        .website-card:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
        }

        .website-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .website-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .website-url {
            color: #3b82f6;
            font-size: 0.9rem;
            word-break: break-all;
        }

        .frequency-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .freq-weekly { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .freq-daily { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .freq-hourly { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }

        .website-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: bold;
            color: #3b82f6;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .website-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #333;
            border-top: 2px solid #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="/">Home</a>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <div class="dashboard-header">
        <div>
            <h1 style="color:#fff; margin: 0;">Dashboard</h1>
            <p style="color: #94a3b8; margin: 0.5rem 0 0 0;">
                Monitor your websites and capture screenshots automatically
            </p>
        </div>
    </div>

    <!-- Plan Info -->
    <div class="plan-info">
        <div>
            <span class="plan-badge plan-<?php echo $user['plan']; ?>">
                <?php echo ucfirst($user['plan']); ?> Plan
            </span>
            <div style="margin-top: 0.5rem; color: #94a3b8; font-size: 0.9rem;">
                <?php echo $websites->num_rows; ?> / <?php echo $user['max_websites']; ?> websites •
                <?php echo $daily_used; ?> / <?php echo $user['daily_screenshot_limit']; ?> screenshots today
            </div>
        </div>
        <?php if ($user['plan'] === 'free'): ?>
            <a href="upgrade.php" style="color: #3b82f6; text-decoration: none;">Upgrade →</a>
        <?php endif; ?>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size: 2rem; font-weight: bold; color: #3b82f6; margin-bottom: 0.5rem;">
                <?php echo $websites->num_rows; ?>
            </div>
            <div style="color: #94a3b8; font-size: 0.9rem;">Monitored Websites</div>
        </div>

        <div class="stat-card">
            <div style="font-size: 2rem; font-weight: bold; color: #22c55e; margin-bottom: 0.5rem;">
                <?php echo $daily_used; ?>
            </div>
            <div style="color: #94a3b8; font-size: 0.9rem;">Screenshots Today</div>
        </div>

        <div class="stat-card">
            <div style="font-size: 2rem; font-weight: bold; color: #fbbf24; margin-bottom: 0.5rem;">
                <?php echo $user['screenshot_retention_days']; ?>
            </div>
            <div style="color: #94a3b8; font-size: 0.9rem;">Days Retention</div>
        </div>

        <div class="stat-card">
            <div style="font-size: 2rem; font-weight: bold; color: #8b5cf6; margin-bottom: 0.5rem;" id="totalScreenshots">
                <div class="loading-spinner"></div>
            </div>
            <div style="color: #94a3b8; font-size: 0.9rem;">Total Screenshots</div>
        </div>
    </div>

    <?php if ($message): ?>
        <sl-alert variant="<?php echo $message_type; ?>" open style="margin-bottom: 2rem;">
            <?php echo $message; ?>
        </sl-alert>
    <?php endif; ?>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Add Website Form -->
        <sl-card>
            <h3 style="color: #fff; margin-bottom: 1rem;">Add Website to Monitor</h3>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="add_website">

                <sl-input
                        type="url"
                        name="url"
                        label="Website URL"
                        placeholder="https://example.com"
                        required
                        <?php echo ($websites->num_rows >= $user['max_websites']) ? 'disabled' : ''; ?>
                ></sl-input>

                <sl-input
                        type="text"
                        name="name"
                        label="Display Name (optional)"
                        placeholder="My Company Homepage"
                        <?php echo ($websites->num_rows >= $user['max_websites']) ? 'disabled' : ''; ?>
                ></sl-input>

                <sl-select
                        name="frequency"
                        label="Screenshot Frequency"
                        value="weekly"
                        <?php echo ($websites->num_rows >= $user['max_websites']) ? 'disabled' : ''; ?>
                >
                    <sl-option value="weekly">Weekly</sl-option>
                    <?php if ($user['plan'] !== 'free'): ?>
                        <sl-option value="daily">Daily</sl-option>
                    <?php endif; ?>
                    <?php if ($user['plan'] === 'pro'): ?>
                        <sl-option value="hourly">Hourly</sl-option>
                    <?php endif; ?>
                </sl-select>

                <sl-button
                        type="submit"
                        variant="primary"
                        <?php echo ($websites->num_rows >= $user['max_websites']) ? 'disabled' : ''; ?>
                >
                    <sl-icon name="plus" slot="prefix"></sl-icon>
                    Add Website
                </sl-button>

                <?php if ($websites->num_rows >= $user['max_websites']): ?>
                    <div style="color: #ef4444; font-size: 0.9rem; text-align: center;">
                        Website limit reached. <a href="upgrade.php" style="color: #3b82f6;">Upgrade</a> for more.
                    </div>
                <?php endif; ?>
            </form>
        </sl-card>

        <!-- Quick Screenshot -->
        <sl-card>
            <h3 style="color: #fff; margin-bottom: 1rem;">Quick Screenshot</h3>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="quick_screenshot">

                <sl-input
                        type="url"
                        name="url"
                        label="Website URL"
                        placeholder="https://example.com"
                        required
                        <?php echo ($daily_used >= $user['daily_screenshot_limit']) ? 'disabled' : ''; ?>
                ></sl-input>

                <sl-button
                        type="submit"
                        variant="primary"
                        <?php echo ($daily_used >= $user['daily_screenshot_limit']) ? 'disabled' : ''; ?>
                >
                    <sl-icon name="camera" slot="prefix"></sl-icon>
                    Take Screenshot
                </sl-button>

                <?php if ($daily_used >= $user['daily_screenshot_limit']): ?>
                    <div style="color: #ef4444; font-size: 0.9rem; text-align: center;">
                        Daily limit reached. <a href="upgrade.php" style="color: #3b82f6;">Upgrade</a> for more.
                    </div>
                <?php endif; ?>
            </form>
        </sl-card>
    </div>

    <!-- Monitored Websites -->
    <h2 style="color: #fff; margin-bottom: 1rem;">Monitored Websites</h2>

    <?php if ($websites->num_rows > 0): ?>
        <div class="websites-grid">
            <?php
            $websites->data_seek(0); // Reset pointer
            while ($website = $websites->fetch_assoc()): ?>
                <div class="website-card">
                    <div class="website-header">
                        <div>
                            <div class="website-name"><?php echo htmlspecialchars($website['name']); ?></div>
                            <div class="website-url"><?php echo htmlspecialchars($website['url']); ?></div>
                        </div>
                        <span class="frequency-badge freq-<?php echo $website['frequency']; ?>">
                            <?php echo ucfirst($website['frequency']); ?>
                        </span>
                    </div>

                    <div class="website-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $website['total_screenshots']; ?></div>
                            <div class="stat-label">Screenshots</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                if ($website['last_screenshot_at']) {
                                    $days = floor((time() - strtotime($website['last_screenshot_at'])) / 86400);
                                    echo $days . 'd';
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </div>
                            <div class="stat-label">Last Shot</div>
                        </div>
                    </div>

                    <div style="margin: 1rem 0; color: #94a3b8; font-size: 0.9rem;">
                        Added: <?php echo date('M j, Y', strtotime($website['created_at'])); ?>
                        <?php if ($website['next_screenshot_at']): ?>
                            <br>Next: <?php echo date('M j, H:i', strtotime($website['next_screenshot_at'])); ?>
                        <?php endif; ?>
                    </div>

                    <div class="website-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="take_screenshot">
                            <input type="hidden" name="website_id" value="<?php echo $website['id']; ?>">
                            <sl-button
                                    type="submit"
                                    variant="primary"
                                    size="small"
                                    <?php echo ($daily_used >= $user['daily_screenshot_limit']) ? 'disabled' : ''; ?>
                            >
                                <sl-icon name="camera" slot="prefix"></sl-icon>
                                Screenshot
                            </sl-button>
                        </form>

                        <sl-button
                                variant="default"
                                size="small"
                                onclick="viewWebsiteHistory(<?php echo $website['id']; ?>)"
                        >
                            History
                        </sl-button>

                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this website from monitoring?')">
                            <input type="hidden" name="action" value="delete_website">
                            <input type="hidden" name="website_id" value="<?php echo $website['id']; ?>">
                            <sl-button type="submit" variant="default" size="small">
                                Remove
                            </sl-button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div style="font-size: 3rem; margin-bottom: 1rem; color: #374151;">🖥️</div>
            <h3>No websites being monitored</h3>
            <p>Add your first website above to start automatic screenshot monitoring!</p>
        </div>
    <?php endif; ?>

</main>

<script>
    // Load total screenshots count
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalScreenshots').textContent = data.total_count;
            }
        })
        .catch(() => {
            document.getElementById('totalScreenshots').textContent = '0';
        });

    function viewWebsiteHistory(websiteId) {
        // Create a modal to show website screenshot history
        const dialog = Object.assign(document.createElement('sl-dialog'), {
            label: 'Website Screenshot History',
            open: true
        });

        dialog.innerHTML = `
            <div style="max-height: 400px; overflow-y: auto;">
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <div style="width: 30px; height: 30px; border: 3px solid #333; border-top: 3px solid var(--sl-color-primary-500); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                    Loading screenshot history...
                </div>
            </div>
            <sl-button slot="footer" variant="default" onclick="this.closest('sl-dialog').hide()">Close</sl-button>
        `;

        document.body.appendChild(dialog);

        // Load website history via API
        fetch('api/website-history.php?website_id=' + websiteId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.screenshots.length > 0) {
                    var historyHtml = '<div style="display: grid; gap: 1rem;">';

                    data.screenshots.forEach(function(shot) {
                        historyHtml += '<div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 8px;">';

                        if (shot.cdn_url) {
                            historyHtml += '<img src="' + shot.cdn_url + '" style="width: 120px; height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="window.open(\'' + shot.cdn_url + '\', \'_blank\')">';
                        } else {
                            historyHtml += '<div style="width: 120px; height: 80px; background: #2a2a2a; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">No image</div>';
                        }

                        historyHtml += '<div style="flex: 1;">';
                        historyHtml += '<div style="font-weight: 600; margin-bottom: 0.5rem; color: #fff;">';
                        historyHtml += new Date(shot.taken_at).toLocaleDateString() + ' at ' + new Date(shot.taken_at).toLocaleTimeString();
                        historyHtml += '</div>';
                        historyHtml += '<div style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 0.5rem;">';
                        historyHtml += 'Status: <span style="color: ' + (shot.status === 'completed' ? '#22c55e' : '#ef4444') + ';">' + shot.status + '</span>';
                        if (shot.worker_id) {
                            historyHtml += ' • Worker: ' + shot.worker_id;
                        }
                        historyHtml += '</div>';

                        if (shot.cdn_url) {
                            historyHtml += '<div style="display: flex; gap: 0.5rem;">';
                            historyHtml += '<sl-button size="small" onclick="window.open(\'' + shot.cdn_url + '\', \'_blank\')">View</sl-button>';
                            historyHtml += '<sl-button size="small" variant="default" onclick="copyToClipboard(\'' + shot.cdn_url + '\')">Copy URL</sl-button>';
                            historyHtml += '</div>';
                        }

                        historyHtml += '</div></div>';
                    });

                    historyHtml += '</div>';
                    dialog.querySelector('div').innerHTML = historyHtml;
                } else {
                    dialog.querySelector('div').innerHTML = '<div style="text-align: center; padding: 2rem; color: #666;"><div style="font-size: 3rem; margin-bottom: 1rem;">📷</div><h3>No screenshots yet</h3><p>Take your first screenshot of this website!</p></div>';
                }
            })
            .catch(() => {
                dialog.querySelector('div').innerHTML = '<div style="text-align: center; padding: 2rem; color: #ef4444;">Failed to load screenshot history</div>';
            });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            const toast = Object.assign(document.createElement('sl-alert'), {
                variant: 'success',
                duration: 3000,
                innerHTML: '<sl-icon slot="icon" name="check"></sl-icon>URL copied to clipboard!'
            });
            document.body.appendChild(toast);
            toast.show();
        });
    }
</script>

</body>
</html>