<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_plan = '';

if ($is_logged_in) {
    include './bin/dbconnect.php';
    $user_id = $_SESSION['user_id'];

    // Get user plan for logged-in users
    $stmt = $DBcon->prepare("SELECT plan FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $user_plan = $user['plan'] ?? 'free';
}
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- SEO Meta Tags -->
    <title>PageWatch.io - Automated Website Screenshot Monitoring | Track Website Changes</title>
    <meta name="description" content="Monitor website changes with automated screenshots. PageWatch.io captures high-quality screenshots of your websites daily, weekly, or hourly. Perfect for competitor monitoring and change tracking." />
    <meta name="keywords" content="website monitoring, screenshot automation, website changes, competitor tracking, web monitoring, automated screenshots, website surveillance" />
    <meta name="author" content="PageWatch.io" />
    <meta name="robots" content="index, follow" />

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="PageWatch.io - Automated Website Screenshot Monitoring" />
    <meta property="og:description" content="Monitor website changes with automated screenshots. Track competitors, monitor your sites, and catch changes instantly." />
    <meta property="og:url" content="https://pagewatch.io" />
    <meta property="og:site_name" content="PageWatch.io" />
    <meta property="og:image" content="https://pagewatch.io/og-banner.jpg" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="PageWatch.io - Website Screenshot Monitoring" />
    <meta name="twitter:description" content="Automated website screenshots to track changes and monitor competitors." />
    <meta name="twitter:image" content="https://pagewatch.io/og-banner.jpg" />

    <!-- Canonical URL -->
    <link rel="canonical" href="https://pagewatch.io" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

    <!-- Structured Data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "PageWatch.io",
            "description": "Automated website screenshot monitoring service",
            "url": "https://pagewatch.io",
            "applicationCategory": "WebApplication",
            "operatingSystem": "Web Browser",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "USD",
                "description": "Free plan available"
            },
            "author": {
                "@type": "Organization",
                "name": "PageWatch.io"
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>
    <style>
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--sl-color-primary-500);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--sl-color-primary-500);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero {
            text-align: center;
            padding: 4rem 0;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .hero .subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            color: #cbd5e1;
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .cta-section {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .screenshot-demo {
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
            font-size: 1rem;
        }

        sl-button::part(base):hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }

        .feature-card {
            background: rgba(26, 26, 26, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .testimonials {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 3rem 2rem;
            margin: 4rem 0;
            text-align: center;
        }

        .pricing-preview {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 3rem 2rem;
            margin: 4rem 0;
            text-align: center;
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .pricing-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 2rem;
            position: relative;
        }

        .pricing-card.popular {
            border-color: #3b82f6;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
        }

        .plan-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #3b82f6;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
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

        .footer {
            background: rgba(26, 26, 26, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 4rem;
            padding: 3rem 0;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--sl-color-primary-500);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .nav-links {
                gap: 1rem;
            }

            .hero {
                padding: 2rem 0;
            }
        }
    </style>
</head>
<body>
<!-- Header -->
<header class="header">
    <div class="header-content">
        <a href="/" class="logo">PageWatch.io</a>
        <nav class="nav-links">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="workers.php">Workers</a>
                <a href="upgrade.php">Upgrade</a>
                <sl-button href="logout.php" variant="default" size="small">Logout</sl-button>
            <?php else: ?>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <sl-button href="login.php" variant="default" size="small">Login</sl-button>
                <sl-button href="register.php" variant="primary" size="small">Sign Up Free</sl-button>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <h1>Monitor Website Changes with Automated Screenshots</h1>
        <p class="subtitle">
            Track competitors, monitor your websites, and catch changes instantly.
            Powered by dedicated hardware for reliable, high-quality screenshots.
        </p>
    </div>

    <?php if ($is_logged_in): ?>
        <!-- Logged-in User: Quick Screenshot Tool -->
        <div class="screenshot-demo">
            <h2 style="color: #fff; margin-bottom: 1rem; text-align: center;">
                Welcome back! Take a quick screenshot
            </h2>
            <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem;">
                Current plan: <strong style="color: #3b82f6;"><?php echo ucfirst($user_plan); ?></strong> •
                <a href="dashboard.php" style="color: #3b82f6;">Go to Dashboard →</a>
            </p>

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
                        Take Screenshot
                    </sl-button>
                    <div style="color: #94a3b8; font-size: 0.9rem; align-self: center;">
                        <span id="usage-display">Loading usage...</span>
                    </div>
                </div>
            </form>

            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem; font-size: 0.9rem; color: #93c5fd; text-align: center;">
                ℹ️ Screenshots are processed by our dedicated NanoPi and Raspberry Pi 4 hardware clusters.
            </div>

            <div id="statusContainer"></div>
        </div>
    <?php else: ?>
        <!-- Not Logged In: Call to Action -->
        <div class="cta-section">
            <h2 style="color: #fff; margin-bottom: 1rem; text-align: center;">
                Start Monitoring Your Websites Today
            </h2>
            <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem;">
                Join thousands of users tracking website changes. Free plan includes 3 websites with weekly screenshots.
            </p>

            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <sl-button href="register.php" variant="primary" size="large">
                    Start Free Account
                </sl-button>
                <sl-button href="login.php" variant="default" size="large">
                    Login
                </sl-button>
            </div>

            <div style="text-align: center; margin-top: 2rem; color: #94a3b8; font-size: 0.9rem;">
                ✅ No credit card required • ✅ 3 websites free • ✅ Cancel anytime
            </div>
        </div>
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features">
        <h2 style="color: #fff; text-align: center; font-size: 2.5rem; margin-bottom: 3rem;">
            Why Choose PageWatch.io?
        </h2>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🖥️</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Real Browser Screenshots</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    Full desktop resolution screenshots captured with actual Chrome browsers
                    running on dedicated NanoPi and Raspberry Pi 4 hardware.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Lightning Fast Processing</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    Dedicated ARM hardware clusters ensure your screenshots are processed
                    quickly and reliably, without shared resource delays.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Automated Monitoring</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    Set up automatic screenshots on hourly, daily, or weekly schedules.
                    Never miss important changes to your websites.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">☁️</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Global CDN Storage</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    All screenshots automatically uploaded to BunnyCDN for
                    lightning-fast access from anywhere in the world.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Easy Comparison</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    View screenshot history, compare changes over time,
                    and track exactly when modifications occurred.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3 style="color: #fff; margin-bottom: 1rem;">Privacy & Security</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    Screenshots processed on isolated hardware with automatic cleanup.
                    Your data is secure and private.
                </p>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    <section id="pricing" class="pricing-preview">
        <h2 style="color: #fff; margin-bottom: 1rem;">Simple, Transparent Pricing</h2>
        <p style="color: #94a3b8; margin-bottom: 2rem;">
            Start free, upgrade when you need more. No hidden fees.
        </p>

        <div class="pricing-cards">
            <div class="pricing-card">
                <h3 style="color: #fff; margin-bottom: 0.5rem;">Free</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #94a3b8; margin-bottom: 1rem;">$0</div>
                <ul style="list-style: none; padding: 0; color: #94a3b8; text-align: left;">
                    <li>✅ 3 websites</li>
                    <li>✅ Weekly screenshots</li>
                    <li>✅ 7-day history</li>
                    <li>✅ 1 manual screenshot/day</li>
                </ul>
            </div>

            <div class="pricing-card popular">
                <div class="plan-badge">Most Popular</div>
                <h3 style="color: #fff; margin-bottom: 0.5rem;">Starter</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #3b82f6; margin-bottom: 1rem;">$9<span style="font-size: 1rem;">/mo</span></div>
                <ul style="list-style: none; padding: 0; color: #94a3b8; text-align: left;">
                    <li>✅ 10 websites</li>
                    <li>✅ Daily + weekly screenshots</li>
                    <li>✅ 30-day history</li>
                    <li>✅ 10 manual screenshots/day</li>
                </ul>
            </div>

            <div class="pricing-card">
                <h3 style="color: #fff; margin-bottom: 0.5rem;">Pro</h3>
                <div style="font-size: 2rem; font-weight: bold; color: #10b981; margin-bottom: 1rem;">$29<span style="font-size: 1rem;">/mo</span></div>
                <ul style="list-style: none; padding: 0; color: #94a3b8; text-align: left;">
                    <li>✅ 50 websites</li>
                    <li>✅ Daily + weekly screenshots</li>
                    <li>✅ 90-day history</li>
                    <li>✅ 50 manual screenshots/day</li>
                </ul>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <?php if ($is_logged_in): ?>
                <sl-button href="upgrade.php" variant="primary" size="large">
                    View Full Pricing
                </sl-button>
            <?php else: ?>
                <sl-button href="register.php" variant="primary" size="large">
                    Start Free Account
                </sl-button>
            <?php endif; ?>
        </div>
    </section>

    <!-- Social Proof / Testimonials -->
    <div class="testimonials">
        <h2 style="color: #fff; margin-bottom: 2rem;">Trusted by Developers & Businesses</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; text-align: left;">
            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px;">
                <p style="color: #94a3b8; margin-bottom: 1rem;">"Perfect for monitoring competitor websites. The hardware-based approach ensures reliable screenshots every time."</p>
                <div style="color: #3b82f6; font-weight: 600;">— Sarah K., Marketing Director</div>
            </div>
            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px;">
                <p style="color: #94a3b8; margin-bottom: 1rem;">"Finally, a screenshot service that actually works consistently. The dedicated hardware makes all the difference."</p>
                <div style="color: #3b82f6; font-weight: 600;">— Mike R., Web Developer</div>
            </div>
            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 8px;">
                <p style="color: #94a3b8; margin-bottom: 1rem;">"Great for tracking changes to our landing pages and monitoring uptime. Much better than cloud-based alternatives."</p>
                <div style="color: #3b82f6; font-weight: 600;">— Alex T., Startup Founder</div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-links">
            <a href="/privacy/">Privacy Policy</a>
<!--            <a href="/terms/">Terms of Service</a>-->
<!--            <a href="/contact/">Contact</a>-->
<!--            <a href="/api-docs/">API Docs</a>-->
<!--            <a href="/status/">Status</a>-->
        </div>
        <p style="color: #94a3b8; margin: 0;">
            &copy; <?= date("Y") ?> PageWatch.io. All rights reserved.
            Powered by dedicated hardware clusters.
        </p>
    </div>
</footer>

<script>
    let pollInterval;
    let currentJobId = null;

    <?php if ($is_logged_in): ?>
    // Load usage info for logged-in users
    function loadUsageInfo() {
        fetch('api/usage.php')
            .then(response => response.json())
            .then(data => {
                const usageDisplay = document.getElementById('usage-display');
                if (usageDisplay) {
                    usageDisplay.textContent = `${data.used || 0}/${data.limit || 1} screenshots used today`;

                    const btn = document.getElementById('takeScreenshotBtn');
                    if (data.used >= data.limit) {
                        btn.disabled = true;
                        usageDisplay.style.color = '#ef4444';
                    }
                }
            })
            .catch(() => {
                const usageDisplay = document.getElementById('usage-display');
                if (usageDisplay) {
                    usageDisplay.textContent = 'Usage info unavailable';
                }
            });
    }

    // Screenshot form handler for logged-in users
    const form = document.getElementById('screenshotForm');
    if (form) {
        loadUsageInfo();

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const urlInput = document.getElementById('urlInput');
            const submitBtn = document.getElementById('takeScreenshotBtn');
            const statusContainer = document.getElementById('statusContainer');

            const url = urlInput.value.trim();
            if (!url) return;

            submitBtn.loading = true;
            submitBtn.textContent = 'Queuing Screenshot...';

            try {
                const response = await fetch('api/screenshot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url })
                });

                const result = await response.json();

                if (result.success) {
                    currentJobId = result.job_id;
                    showStatus('pending', 'Screenshot queued successfully! Processing will begin shortly...');
                    startPolling(result.job_id);
                    loadUsageInfo();
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
    }

    function showStatus(status, message) {
        const statusContainer = document.getElementById('statusContainer');
        if (!statusContainer) return;

        statusContainer.innerHTML = `
                <div class="status-card status-${status}">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        ${status === 'processing' ? '<div class="loading-spinner"></div>' : ''}
                        <strong>${getStatusIcon(status)} ${getStatusTitle(status)}</strong>
                    </div>
                    <p style="margin: 0.5rem 0 0 0;">${message}</p>
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
        }, 3000);
    }

    function showCompletedResult(result) {
        const statusContainer = document.getElementById('statusContainer');
        if (!statusContainer) return;

        statusContainer.innerHTML = `
                <div class="status-card status-completed">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <strong>✅ Screenshot Complete!</strong>
                    </div>
                    <img src="${result.cdn_url}" alt="Website Screenshot" class="screenshot-preview" />
                    <div style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <sl-button href="${result.cdn_url}" target="_blank" variant="primary" size="small">
                            View Full Size
                        </sl-button>
                        <sl-button onclick="copyToClipboard('${result.cdn_url}')" variant="default" size="small">
                            Copy URL
                        </sl-button>
                    </div>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #94a3b8; text-align: center;">
                        Processed in ${result.processing_time || 'unknown'} | Worker: ${result.worker_id || 'Hardware'}
                    </p>
                </div>
            `;
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

    // Clean up polling on page unload
    window.addEventListener('beforeunload', () => {
        if (pollInterval) clearInterval(pollInterval);
    });
    <?php endif; ?>

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>
</body>
</html>