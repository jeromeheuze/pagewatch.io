<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Workers – PageWatch.io</title>
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

        .workers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .worker-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .worker-card.online {
            border-color: #22c55e;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.1);
        }

        .worker-card.offline {
            border-color: #ef4444;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.1);
        }

        .worker-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ef4444;
        }

        .worker-status.online {
            background: #22c55e;
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.5);
        }

        .worker-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .worker-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .worker-info h3 {
            margin: 0;
            color: #fff;
            font-size: 1.1rem;
        }

        .worker-info p {
            margin: 0.25rem 0 0 0;
            color: #999;
            font-size: 0.9rem;
        }

        .worker-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: #2a2a2a;
            border-radius: 6px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--sl-color-primary-400);
        }

        .stat-label {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .queue-overview {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .queue-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .queue-stat {
            text-align: center;
            padding: 1rem;
            background: #2a2a2a;
            border-radius: 8px;
        }

        .recent-activity {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #333;
            font-size: 0.9rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-url {
            color: var(--sl-color-primary-400);
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .activity-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            color: #999;
            font-size: 0.8rem;
        }

        .refresh-button {
            margin-bottom: 1rem;
        }

        .error-state {
            text-align: center;
            padding: 2rem;
            color: #ef4444;
        }

        .loading-state {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="workers.php" class="active">Workers</a>
        <a href="upgrade.php">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="color:#fff; margin: 0;">Workers & Queue</h1>
        <sl-button variant="default" size="small" onclick="loadAllData()" class="refresh-button">
            <sl-icon name="refresh-cw" slot="prefix"></sl-icon>
            Refresh
        </sl-button>
    </div>

    <!-- Queue Overview -->
    <div class="queue-overview">
        <h2 style="color: #fff; margin-bottom: 1rem;">Queue Status</h2>
        <div class="queue-stats" id="queueStats">
            <div class="loading-state">Loading queue statistics...</div>
        </div>
    </div>

    <!-- Workers Status -->
    <h2 style="color: #fff; margin-bottom: 1rem;">Hardware Workers</h2>
    <div class="workers-grid" id="workersContainer">
        <div class="loading-state">Loading worker information...</div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2 style="color: #fff; margin-bottom: 1rem;">Recent Activity</h2>
        <div class="activity-list" id="recentActivity">
            <div class="loading-state">Loading recent jobs...</div>
        </div>
    </div>
</main>

<script>
    let refreshInterval;

    document.addEventListener('DOMContentLoaded', function() {
        loadAllData();

        // Auto-refresh every 10 seconds
        refreshInterval = setInterval(loadAllData, 10000);
    });

    async function loadAllData() {
        try {
            const response = await fetch('api/worker-stats.php');
            const data = await response.json();

            if (data.success) {
                renderWorkers(data.workers);
                renderQueueStats(data.queue_stats);
                renderRecentActivity(data.recent_jobs);
            } else {
                showError('Failed to load worker data');
            }
        } catch (error) {
            console.error('Failed to load data:', error);
            showError('Network error while loading data');
        }
    }

    function renderWorkers(workers) {
        const container = document.getElementById('workersContainer');

        if (workers.length === 0) {
            container.innerHTML = `
                    <div class="error-state">
                        <sl-icon name="server" style="font-size: 3rem; margin-bottom: 1rem;"></sl-icon>
                        <h3>No Workers Connected</h3>
                        <p>Set up your NanoPi and Raspberry Pi 4 workers to start processing screenshots.</p>
                    </div>
                `;
            return;
        }

        container.innerHTML = workers.map(worker => {
            const isOnline = worker.seconds_since_heartbeat < 120; // 2 minutes
            const lastSeen = worker.seconds_since_heartbeat < 60 ?
                'Just now' :
                `${Math.floor(worker.seconds_since_heartbeat / 60)} min ago`;

            const deviceType = worker.name.toLowerCase().includes('nano') ? 'NanoPi' : 'RPi4';
            const deviceIcon = deviceType === 'NanoPi' ? '🔷' : '🍓';

            return `
                    <div class="worker-card ${isOnline ? 'online' : 'offline'}">
                        <div class="worker-status ${isOnline ? 'online' : ''}"></div>

                        <div class="worker-header">
                            <div class="worker-icon">${deviceIcon}</div>
                            <div class="worker-info">
                                <h3>${worker.name}</h3>
                                <p>${worker.ip_address || 'Unknown IP'}</p>
                            </div>
                        </div>

                        <div style="margin: 1rem 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: #ccc;">Status:</span>
                                <span style="color: ${isOnline ? '#22c55e' : '#ef4444'};">
                                    ${isOnline ? '🟢 Online' : '🔴 Offline'}
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #ccc;">Last seen:</span>
                                <span style="color: #999;">${lastSeen}</span>
                            </div>
                        </div>

                        <div class="worker-stats">
                            <div class="stat-item">
                                <div class="stat-number">${worker.jobs_completed}</div>
                                <div class="stat-label">Completed</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">${worker.jobs_failed}</div>
                                <div class="stat-label">Failed</div>
                            </div>
                        </div>

                        <div style="margin-top: 1rem; text-align: center;">
                            <sl-button variant="default" size="small" onclick="viewWorkerLogs('${worker.id}')">
                                <sl-icon name="file-text" slot="prefix"></sl-icon>
                                View Logs
                            </sl-button>
                        </div>
                    </div>
                `;
        }).join('');
    }

    function renderQueueStats(queueStats) {
        const container = document.getElementById('queueStats');

        const statsMap = {};
        queueStats.forEach(stat => {
            statsMap[stat.status] = stat;
        });

        const pending = statsMap.pending?.count || 0;
        const processing = statsMap.processing?.count || 0;
        const completed = statsMap.completed?.count || 0;
        const failed = statsMap.failed?.count || 0;

        const avgDuration = statsMap.completed?.avg_duration || 0;

        container.innerHTML = `
                <div class="queue-stat">
                    <div class="stat-number" style="color: #fbbf24;">${pending}</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="queue-stat">
                    <div class="stat-number" style="color: #3b82f6;">${processing}</div>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="queue-stat">
                    <div class="stat-number" style="color: #22c55e;">${completed}</div>
                    <div class="stat-label">Completed (24h)</div>
                </div>
                <div class="queue-stat">
                    <div class="stat-number" style="color: #ef4444;">${failed}</div>
                    <div class="stat-label">Failed (24h)</div>
                </div>
                <div class="queue-stat">
                    <div class="stat-number">${Math.round(avgDuration)}s</div>
                    <div class="stat-label">Avg Duration</div>
                </div>
            `;
    }

    function renderRecentActivity(recentJobs) {
        const container = document.getElementById('recentActivity');

        if (recentJobs.length === 0) {
            container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <sl-icon name="clock" style="font-size: 2rem; margin-bottom: 1rem;"></sl-icon>
                        <p>No recent activity</p>
                    </div>
                `;
            return;
        }

        container.innerHTML = recentJobs.map(job => {
            const createdAt = new Date(job.created_at).toLocaleTimeString();
            const domain = new URL(job.url).hostname;
            const duration = job.duration ? `${job.duration}s` : '-';

            return `
                    <div class="activity-item">
                        <div>
                            <div class="activity-url">${domain}</div>
                            <div style="color: #666; font-size: 0.8rem;">${job.worker_id || 'No worker'}</div>
                        </div>
                        <div class="activity-meta">
                            <span class="status-badge status-${job.status}">${job.status}</span>
                            <span>${duration}</span>
                            <span>${createdAt}</span>
                        </div>
                    </div>
                `;
        }).join('');
    }

    function showError(message) {
        document.getElementById('workersContainer').innerHTML = `
                <div class="error-state">
                    <sl-icon name="alert-circle" style="font-size: 3rem; margin-bottom: 1rem;"></sl-icon>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
    }

    function viewWorkerLogs(workerId) {
        // Create a modal to show worker logs
        const dialog = Object.assign(document.createElement('sl-dialog'), {
            label: `Worker Logs: ${workerId}`,
            open: true
        });

        dialog.innerHTML = `
                <div style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 0.9rem; line-height: 1.4;">
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <div class="spinner" style="margin: 0 auto 1rem;"></div>
                        Loading logs...
                    </div>
                </div>
                <sl-button slot="footer" variant="default" onclick="this.closest('sl-dialog').hide()">Close</sl-button>
            `;

        document.body.appendChild(dialog);

        // In a real implementation, you'd fetch logs from your server
        setTimeout(() => {
            dialog.querySelector('div').innerHTML = `
                    <div style="color: #22c55e;">2025-08-07 14:30:15 - INFO - Worker ${workerId} initialized</div>
                    <div style="color: #3b82f6;">2025-08-07 14:30:16 - INFO - Chrome driver initialized successfully</div>
                    <div style="color: #22c55e;">2025-08-07 14:30:17 - INFO - Worker registered successfully</div>
                    <div style="color: #fbbf24;">2025-08-07 14:32:45 - INFO - Processing job 123: https://example.com</div>
                    <div style="color: #22c55e;">2025-08-07 14:33:12 - INFO - Screenshot uploaded to CDN</div>
                    <div style="color: #22c55e;">2025-08-07 14:33:13 - INFO - Job 123 completed successfully</div>
                    <div style="color: #666; font-style: italic;">Real-time logs would be fetched from your server...</div>
                `;
        }, 1500);
    }

    // Cleanup interval on page unload
    window.addEventListener('beforeunload', () => {
        if (refreshInterval) clearInterval(refreshInterval);
    });

    // Add CSS for status badges and spinner
    const additionalCSS = `
            .status-badge {
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
            }

            .status-completed { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
            .status-pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
            .status-processing { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
            .status-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

            .spinner {
                width: 30px;
                height: 30px;
                border: 3px solid #333;
                border-top: 3px solid var(--sl-color-primary-500);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;

    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);
</script>
</body>
</html>