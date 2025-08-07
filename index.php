<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PageWatch.io – Website Screenshot Tool</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>
    <style>
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            padding: 1rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .hero {
            text-align: center;
            padding: 3rem 0;
        }

        .hero h1 {
            font-size: 3rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .hero p {
            font-size: 1.25rem;
            color: #cbd5e1;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .screenshot-module {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .url-input {
            flex: 1;
        }

        sl-button::part(base) {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }

        sl-button::part(base):hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .status-card {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            color: #fbbf24;
        }

        .status-processing {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }

        .status-completed {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .status-failed {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .screenshot-preview {
            margin-top: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 100%;
            animation: fadeIn 0.5s ease;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .feature-card {
            background: rgba(26, 26, 26, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-section {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(26, 26, 26, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-button, .register-button {
            margin: 0.5rem;
        }

        .queue-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #93c5fd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="hero">
        <h1>PageWatch.io</h1>
        <p>Capture high-quality screenshots of any website instantly. Perfect for competitor monitoring, design inspiration, and archiving web pages.</p>
    </div>

    <!-- Check if user is logged in -->
    <div id="auth-check" style="display: none;">
        <div class="screenshot-module">
            <h2 style="color: #fff; margin-bottom: 1rem; text-align: center;">Take a Screenshot</h2>

            <form id="screenshotForm">
                <div class="form-group">
                    <sl-input
                            id="urlInput"
                            type="url"
                            placeholder="Enter website URL (e.g., https://example.com)"
                            size="large"
                            required
                            style="width: 100%;"
                    >
                        🔗
                    </sl-input>
                </div>

                <div class="form-row">
                    <sl-button type="submit" variant="primary" size="large" id="takeScreenshotBtn">
                        📷 Take Screenshot
                    </sl-button>
                    <div style="color: #94a3b8; font-size: 0.9rem; align-self: center;">
                        <span id="usage-display">Loading...</span>
                    </div>
                </div>
            </form>

            <div class="queue-info">
                ℹ️ Screenshots are processed by our dedicated hardware. Typical processing time: 30-60 seconds.
            </div>

            <div id="statusContainer"></div>
            <div id="queuePosition" style="display: none;"></div>
        </div>

        <div class="features">
            <div class="feature-card">
                <div style="font-size: 2rem; color: #3b82f6; margin-bottom: 1rem;">🖥️</div>
                <h3 style="color: #fff;">Desktop Quality</h3>
                <p style="color: #94a3b8;">Full desktop resolution screenshots captured with real browsers on dedicated hardware.</p>
            </div>
            <div class="feature-card">
                <div style="font-size: 2rem; color: #8b5cf6; margin-bottom: 1rem;">⚡</div>
                <h3 style="color: #fff;">Lightning Fast</h3>
                <p style="color: #94a3b8;">Powered by NanoPi and Raspberry Pi 4 clusters for reliable, fast screenshot generation.</p>
            </div>
            <div class="feature-card">
                <div style="font-size: 2rem; color: #06b6d4; margin-bottom: 1rem;">☁️</div>
                <h3 style="color: #fff;">CDN Hosted</h3>
                <p style="color: #94a3b8;">All screenshots automatically uploaded to global CDN for fast access worldwide.</p>
            </div>
        </div>
    </div>

    <!-- Auth section for non-logged-in users -->
    <div id="auth-required" class="auth-section">
        <h2 style="color: #fff; margin-bottom: 1rem;">Get Started with Free Screenshots</h2>
        <p style="color: #94a3b8; margin-bottom: 2rem;">Create an account to take your first screenshot. Free users get 1 screenshot per day!</p>

        <sl-button href="login.php" variant="primary" class="login-button">
            <sl-icon name="log-in" slot="prefix"></sl-icon>
            Login
        </sl-button>

        <sl-button href="register.php" variant="default" class="register-button">
            <sl-icon name="user-plus" slot="prefix"></sl-icon>
            Sign Up Free
        </sl-button>
    </div>
</div>

<script>
    let pollInterval;
    let currentJobId = null;

    // Check authentication status
    fetch('api/check-auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                document.getElementById('auth-check').style.display = 'block';
                document.getElementById('auth-required').style.display = 'none';
                loadUsageInfo();
            } else {
                document.getElementById('auth-check').style.display = 'none';
                document.getElementById('auth-required').style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('auth-check').style.display = 'none';
            document.getElementById('auth-required').style.display = 'block';
        });

    function loadUsageInfo() {
        fetch('api/usage.php')
            .then(response => response.json())
            .then(data => {
                const usageDisplay = document.getElementById('usage-display');
                usageDisplay.textContent = `${data.used}/${data.limit} screenshots used today`;

                if (data.used >= data.limit) {
                    document.getElementById('takeScreenshotBtn').disabled = true;
                    usageDisplay.style.color = '#ef4444';
                }
            });
    }

    // Screenshot form handler
    document.getElementById('screenshotForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const urlInput = document.getElementById('urlInput');
        const submitBtn = document.getElementById('takeScreenshotBtn');
        const statusContainer = document.getElementById('statusContainer');

        const url = urlInput.value.trim();
        if (!url) return;

        // Disable form and show processing state
        submitBtn.loading = true;
        submitBtn.textContent = 'Queuing Screenshot...';

        try {
            const response = await fetch('api/screenshot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url: url })
            });

            const result = await response.json();

            if (result.success) {
                currentJobId = result.job_id;
                showStatus('pending', 'Screenshot queued successfully! Processing will begin shortly...', result.queue_position);
                startPolling(result.job_id);
                loadUsageInfo(); // Refresh usage display
            } else {
                showStatus('failed', result.message || 'Failed to queue screenshot');
            }
        } catch (error) {
            showStatus('failed', 'Network error. Please try again.');
        } finally {
            submitBtn.loading = false;
            submitBtn.textContent = 'Take Screenshot';
        }
    });

    function showStatus(status, message, queuePosition = null) {
        const statusContainer = document.getElementById('statusContainer');

        statusContainer.innerHTML = `
                <div class="status-card status-${status}">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${status === 'processing' ? '<div class="loading-spinner"></div>' : ''}
                        <strong>${getStatusIcon(status)} ${getStatusTitle(status)}</strong>
                    </div>
                    <p style="margin: 0.5rem 0 0 0;">${message}</p>
                    ${queuePosition ? `<p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Queue position: #${queuePosition}</p>` : ''}
                </div>
            `;
    }

    function getStatusIcon(status) {
        const icons = {
            'pending': '⏳',
            'processing': '🔄',
            'completed': '✅',
            'failed': '❌'
        };
        return icons[status] || '';
    }

    function getStatusTitle(status) {
        const titles = {
            'pending': 'Queued',
            'processing': 'Processing',
            'completed': 'Complete',
            'failed': 'Failed'
        };
        return titles[status] || status;
    }

    function startPolling(jobId) {
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`api/job-status.php?id=${jobId}`);
                const result = await response.json();

                if (result.status === 'processing') {
                    showStatus('processing', `Being processed by ${result.worker_id || 'hardware worker'}...`);
                } else if (result.status === 'completed') {
                    clearInterval(pollInterval);
                    showCompletedResult(result);
                } else if (result.status === 'failed') {
                    clearInterval(pollInterval);
                    showStatus('failed', result.error_message || 'Screenshot failed to process');
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 3000); // Poll every 3 seconds
    }

    function showCompletedResult(result) {
        const statusContainer = document.getElementById('statusContainer');

        statusContainer.innerHTML = `
                <div class="status-card status-completed">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <strong>✅ Screenshot Complete!</strong>
                    </div>
                    <img src="${result.cdn_url}" alt="Website Screenshot" class="screenshot-preview" />
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: center;">
                        <sl-button href="${result.cdn_url}" target="_blank" variant="primary" size="small">
                            🔗 View Full Size
                        </sl-button>
                        <sl-button onclick="copyToClipboard('${result.cdn_url}')" variant="default" size="small">
                            📋 Copy URL
                        </sl-button>
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #94a3b8;">
                        Processed in ${result.processing_time || 'unknown'} | Worker: ${result.worker_id || 'Hardware'}
                    </p>
                </div>
            `;
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show temporary success message
            const toast = Object.assign(document.createElement('sl-alert'), {
                variant: 'success',
                duration: 3000,
                innerHTML: '<sl-icon slot="icon" name="check"></sl-icon>URL copied to clipboard!'
            });
            document.body.appendChild(toast);
            toast.show();
        });
    }

    // Clean up polling on page unload
    window.addEventListener('beforeunload', () => {
        if (pollInterval) clearInterval(pollInterval);
    });
</script>
</body>
</html>