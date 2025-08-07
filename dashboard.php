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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #1a1a1a;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #333;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--sl-color-primary-500);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #ccc;
            font-size: 0.9rem;
        }

        .quick-screenshot {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .screenshot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .screenshot-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 1rem;
            transition: transform 0.2s;
        }

        .screenshot-card:hover {
            transform: translateY(-2px);
            border-color: #444;
        }

        .screenshot-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #333;
            margin-bottom: 1rem;
        }

        .screenshot-url {
            color: var(--sl-color-primary-400);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }

        .screenshot-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #999;
        }

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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            margin-top: 1rem;
        }

        .url-input {
            flex: 1;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            z-index: 10;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #333;
            border-top: 3px solid var(--sl-color-primary-500);
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
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <h1 style="color:#fff; margin-bottom: 2rem;">Dashboard</h1>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" id="todayCount">-</div>
            <div class="stat-label">Screenshots Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="totalCount">-</div>
            <div class="stat-label">Total Screenshots</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="queueCount">-</div>
            <div class="stat-label">Queue Length</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="workerCount">-</div>
            <div class="stat-label">Active Workers</div>
        </div>
    </div>

    <!-- Quick Screenshot -->
    <div class="quick-screenshot">
        <h2 style="color: #fff; margin-bottom: 1rem;">Take a Screenshot</h2>
        <form id="quickScreenshotForm">
            <div class="form-row">
                <sl-input
                        id="quickUrlInput"
                        type="url"
                        placeholder="Enter website URL"
                        size="large"
                        class="url-input"
                        required
                >
                    <sl-icon name="link" slot="prefix"></sl-icon>
                </sl-input>
                <sl-button type="submit" variant="primary" size="large" id="quickScreenshotBtn">
                    <sl-icon name="camera" slot="prefix"></sl-icon>
                    Capture
                </sl-button>
            </div>
        </form>
        <div id="quickStatus" style="margin-top: 1rem;"></div>
    </div>

    <!-- Screenshots Gallery -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="color: #fff; margin: 0;">Recent Screenshots</h2>
        <sl-button variant="default" size="small" onclick="loadScreenshots()">
            <sl-icon name="refresh-cw" slot="prefix"></sl-icon>
            Refresh
        </sl-button>
    </div>

    <div id="screenshotsContainer">
        <div class="loading-overlay">
            <div class="spinner"></div>
        </div>
    </div>
</main>

<script>
    let refreshInterval;

    // Load dashboard data on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardStats();
        loadScreenshots();

        // Auto-refresh every 30 seconds
        refreshInterval = setInterval(() => {
            loadDashboardStats();
            loadScreenshots();
        }, 30000);
    });

    // Quick screenshot form
    document.getElementById('quickScreenshotForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const urlInput = document.getElementById('quickUrlInput');
        const submitBtn = document.getElementById('quickScreenshotBtn');
        const statusDiv = document.getElementById('quickStatus');

        const url = urlInput.value.trim();
        if (!url) return;

        submitBtn.loading = true;

        try {
            const response = await fetch('api/screenshot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: url })
            });

            const result = await response.json();

            if (result.success) {
                statusDiv.innerHTML = `
                        <sl-alert variant="success" open>
                            <sl-icon slot="icon" name="check-circle"></sl-icon>
                            Screenshot queued! Position #${result.queue_position}
                        </sl-alert>
                    `;
                urlInput.value = '';
                setTimeout(() => loadScreenshots(), 2000);
            } else {
                statusDiv.innerHTML = `
                        <sl-alert variant="danger" open>
                            <sl-icon slot="icon" name="x-circle"></sl-icon>
                            ${result.message}
                        </sl-alert>
                    `;
            }
        } catch (error) {
            statusDiv.innerHTML = `
                    <sl-alert variant="danger" open>
                        <sl-icon slot="icon" name="x-circle"></sl-icon>
                        Network error. Please try again.
                    </sl-alert>
                `;
        } finally {
            submitBtn.loading = false;
        }
    });

    async function loadDashboardStats() {
        try {
            const response = await fetch('api/dashboard-stats.php');
            const stats = await response.json();

            if (stats.success) {
                document.getElementById('todayCount').textContent = stats.today_count || 0;
                document.getElementById('totalCount').textContent = stats.total_count || 0;
                document.getElementById('queueCount').textContent = stats.queue_count || 0;
                document.getElementById('workerCount').textContent = stats.active_workers || 0;
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    async function loadScreenshots() {
        const container = document.getElementById('screenshotsContainer');

        try {
            const response = await fetch('api/user-screenshots.php');
            const data = await response.json();

            if (data.success && data.screenshots.length > 0) {
                container.innerHTML = `
                        <div class="screenshot-grid">
                            ${data.screenshots.map(screenshot => createScreenshotCard(screenshot)).join('')}
                        </div>
                    `;
            } else {
                container.innerHTML = `
                        <div class="empty-state">
                            <sl-icon name="camera" style="font-size: 3rem; color: #444; margin-bottom: 1rem;"></sl-icon>
                            <h3 style="color: #666;">No screenshots yet</h3>
                            <p style="color: #666;">Take your first screenshot using the form above!</p>
                        </div>
                    `;
            }
        } catch (error) {
            container.innerHTML = `
                    <div class="empty-state">
                        <sl-icon name="alert-circle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></sl-icon>
                        <h3 style="color: #ef4444;">Failed to load screenshots</h3>
                        <p style="color: #666;">Please refresh the page and try again.</p>
                    </div>
                `;
        }
    }

    function createScreenshotCard(screenshot) {
        const createdAt = new Date(screenshot.created_at).toLocaleDateString();
        const domain = new URL(screenshot.url).hostname;

        return `
                <div class="screenshot-card">
                    ${screenshot.status === 'completed' ?
            `<img src="${screenshot.cdn_url}" alt="Screenshot of ${domain}" class="screenshot-image" onclick="viewScreenshot('${screenshot.cdn_url}', '${domain}')" style="cursor: pointer;" />` :
            `<div class="screenshot-image" style="display: flex; align-items: center; justify-content: center; background: #2a2a2a;">
                            <div style="text-align: center;">
                                ${screenshot.status === 'processing' ?
                '<div class="spinner" style="margin: 0 auto 1rem;"></div><p style="color: #3b82f6;">Processing...</p>' :
                screenshot.status === 'pending' ?
                    '<sl-icon name="clock" style="font-size: 2rem; color: #fbbf24; margin-bottom: 1rem;"></sl-icon><p style="color: #fbbf24;">Queued</p>' :
                    '<sl-icon name="x-circle" style="font-size: 2rem; color: #ef4444; margin-bottom: 1rem;"></sl-icon><p style="color: #ef4444;">Failed</p>'
            }
                            </div>
                        </div>`
        }

                    <div class="screenshot-url">${domain}</div>
                    <div class="screenshot-meta">
                        <span>${createdAt}</span>
                        <span class="status-badge status-${screenshot.status}">${screenshot.status}</span>
                    </div>

                    ${screenshot.status === 'completed' ?
            `<div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <sl-button href="${screenshot.cdn_url}" target="_blank" variant="primary" size="small" style="flex: 1;">
                                <sl-icon name="external-link" slot="prefix"></sl-icon>
                                View
                            </sl-button>
                            <sl-button onclick="copyToClipboard('${screenshot.cdn_url}')" variant="default" size="small">
                                <sl-icon name="copy"></sl-icon>
                            </sl-button>
                        </div>` : ''
        }

                    ${screenshot.status === 'failed' && screenshot.error_message ?
            `<div style="margin-top: 1rem; padding: 0.5rem; background: rgba(239, 68, 68, 0.1); border-radius: 6px; font-size: 0.8rem; color: #ef4444;">
                            ${screenshot.error_message}
                        </div>` : ''
        }
                </div>
            `;
    }

    function viewScreenshot(url, domain) {
        // Open in a modal-like new tab
        const newWindow = window.open('', '_blank');
        newWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Screenshot: ${domain}</title>
                    <style>
                        body { margin: 0; background: #000; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                        img { max-width: 100%; max-height: 100%; border: 1px solid #333; }
                    </style>
                </head>
                <body>
                    <img src="${url}" alt="Screenshot of ${domain}" />
                </body>
                </html>
            `);
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

    // Cleanup interval on page unload
    window.addEventListener('beforeunload', () => {
        if (refreshInterval) clearInterval(refreshInterval);
    });
</script>
</body>
</html>