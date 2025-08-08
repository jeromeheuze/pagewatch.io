<?php
session_start();
include './bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user's current plan and usage info
$user_id = $_SESSION['user_id'];
$stmt = $DBcon->prepare("SELECT plan, daily_screenshot_limit, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$current_plan = $user['plan'];

// Get today's usage for context
$usage_stmt = $DBcon->prepare("
    SELECT COALESCE(screenshots_used, 0) as used 
    FROM daily_usage 
    WHERE user_id = ? AND date = CURDATE()
");
$usage_stmt->bind_param("i", $user_id);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result()->fetch_assoc();
$daily_used = $usage_result['used'] ?? 0;

// Get website count
$websites_stmt = $DBcon->prepare("SELECT COUNT(*) as count FROM websites WHERE user_id = ? AND is_active = 1");
$websites_stmt->bind_param("i", $user_id);
$websites_stmt->execute();
$website_count = $websites_stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Upgrade – PageWatch.io</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>
    <style>
        body {
            margin: 0;
            background: #111;
            font-family: system-ui, sans-serif;
            display: flex;
            min-height: 100vh;
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

        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .hero-title {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .current-usage {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .plans-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .plan-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            max-width: 300px;
            width: 100%;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            border-color: #3b82f6;
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.1);
        }

        .plan-card.popular {
            border: 2px solid #3b82f6;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.2);
        }

        .plan-card.current {
            border: 2px solid #22c55e;
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.2);
        }

        .plan-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            padding: 0.25rem 1rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-popular {
            background: #3b82f6;
            color: white;
        }

        .badge-current {
            background: #22c55e;
            color: white;
        }

        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .plan-period {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .feature-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .feature-highlight {
            font-weight: 600;
            color: #fff;
        }

        .plan-cta {
            width: 100%;
        }

        .features-section {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .feature-icon-large {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="/">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="quick-screenshots.php">Quick Screenshots</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php" class="active">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <div class="hero-section">
        <h1 class="hero-title">Simple Website Screenshot Monitoring</h1>
        <p style="color: #94a3b8; font-size: 1.1rem; max-width: 600px; margin: 0 auto;">
            Track changes to your websites with automatic screenshots. Get notified when something changes.
        </p>
    </div>

    <!-- Current Usage Info -->
    <div class="current-usage">
        <h3 style="color: #3b82f6; margin: 0 0 1rem 0;">
            Current Plan: <strong><?php echo ucfirst($current_plan); ?></strong>
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; color: #94a3b8;">
            <div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #3b82f6;"><?php echo $website_count; ?></div>
                <div style="font-size: 0.9rem;">Websites Monitored</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #22c55e;"><?php echo $daily_used; ?></div>
                <div style="font-size: 0.9rem;">Screenshots Today</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #fbbf24;"><?php echo $user['daily_screenshot_limit']; ?></div>
                <div style="font-size: 0.9rem;">Daily Limit</div>
            </div>
        </div>
    </div>

    <!-- Pricing Plans -->
    <div class="plans-container">
        <!-- Free Plan -->
        <div class="plan-card <?php echo $current_plan === 'free' ? 'current' : ''; ?>">
            <?php if ($current_plan === 'free'): ?>
                <div class="plan-badge badge-current">Current Plan</div>
            <?php endif; ?>

            <div class="plan-header">
                <div class="plan-name">Free</div>
                <div class="plan-price" style="color: #94a3b8;">$0</div>
                <div class="plan-period">Try it out</div>
            </div>

            <ul class="plan-features">
                <li>
                    <div class="feature-icon">✓</div>
                    <span><span class="feature-highlight">3 websites</span></span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Weekly screenshots</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>View last 7 days</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>1 manual screenshot/day</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Hardware-powered processing</span>
                </li>
            </ul>

            <?php if ($current_plan === 'free'): ?>
                <sl-button variant="success" class="plan-cta" disabled>
                    ✓ Current Plan
                </sl-button>
            <?php else: ?>
                <sl-button variant="default" class="plan-cta" disabled>
                    Current: <?php echo ucfirst($current_plan); ?>
                </sl-button>
            <?php endif; ?>
        </div>

        <!-- Starter Plan -->
        <div class="plan-card <?php echo $current_plan === 'starter' ? 'current' : ($current_plan === 'free' ? 'popular' : ''); ?>">
            <?php if ($current_plan === 'starter'): ?>
                <div class="plan-badge badge-current">Current Plan</div>
            <?php elseif ($current_plan === 'free'): ?>
                <div class="plan-badge badge-popular">Most Popular</div>
            <?php endif; ?>

            <div class="plan-header">
                <div class="plan-name">Starter</div>
                <div class="plan-price" style="color: #3b82f6;">$9</div>
                <div class="plan-period">per month</div>
            </div>

            <ul class="plan-features">
                <li>
                    <div class="feature-icon">✓</div>
                    <span><span class="feature-highlight">10 websites</span></span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Daily + weekly screenshots</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>View last 30 days</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>10 manual screenshots/day</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Compare changes side-by-side</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Priority processing</span>
                </li>
            </ul>

            <?php if ($current_plan === 'starter'): ?>
                <sl-button variant="success" class="plan-cta" disabled>
                    ✓ Current Plan
                </sl-button>
            <?php elseif ($current_plan === 'free'): ?>
                <form action="create-checkout-session.php" method="POST">
                    <input type="hidden" name="plan" value="starter">
                    <sl-button variant="primary" type="submit" class="plan-cta">
                        Choose Starter
                    </sl-button>
                </form>
            <?php else: ?>
                <sl-button variant="default" class="plan-cta" disabled>
                    Downgrade Not Available
                </sl-button>
            <?php endif; ?>
        </div>

        <!-- Pro Plan -->
        <div class="plan-card <?php echo $current_plan === 'pro' ? 'current' : ''; ?>">
            <?php if ($current_plan === 'pro'): ?>
                <div class="plan-badge badge-current">Current Plan</div>
            <?php endif; ?>

            <div class="plan-header">
                <div class="plan-name">Pro</div>
                <div class="plan-price" style="color: #10b981;">$29</div>
                <div class="plan-period">per month</div>
            </div>

            <ul class="plan-features">
                <li>
                    <div class="feature-icon">✓</div>
                    <span><span class="feature-highlight">50 websites</span></span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Daily + weekly screenshots</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>View last 90 days</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>50 manual screenshots/day</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Compare changes side-by-side</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Download screenshots</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>API access</span>
                </li>
                <li>
                    <div class="feature-icon">✓</div>
                    <span>Priority support</span>
                </li>
            </ul>

            <?php if ($current_plan === 'pro'): ?>
                <sl-button variant="success" class="plan-cta" disabled>
                    ✓ Current Plan
                </sl-button>
            <?php else: ?>
                <form action="create-checkout-session.php" method="POST">
                    <input type="hidden" name="plan" value="pro">
                    <sl-button variant="success" type="submit" class="plan-cta">
                        Choose Pro
                    </sl-button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <h2 style="color: #fff; text-align: center; margin-bottom: 1rem;">How it works</h2>
        <p style="color: #94a3b8; text-align: center; max-width: 600px; margin: 0 auto 2rem auto;">
            Add your website URLs, choose how often to check them, and we'll automatically take screenshots.
            Check back anytime to see what changed on your websites.
        </p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-large">🖥️</div>
                <h3 style="color: #fff;">Desktop Quality</h3>
                <p style="color: #94a3b8;">Full desktop resolution screenshots captured with real browsers on dedicated hardware.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-large">⚡</div>
                <h3 style="color: #fff;">Lightning Fast</h3>
                <p style="color: #94a3b8;">Powered by NanoPi and Raspberry Pi 4 clusters for reliable, fast screenshot generation.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-large">☁️</div>
                <h3 style="color: #fff;">CDN Hosted</h3>
                <p style="color: #94a3b8;">All screenshots automatically uploaded to global CDN for fast access worldwide.</p>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 3rem; padding: 2rem; background: #1a1a1a; border-radius: 12px;">
        <h3 style="color: #fff; margin-bottom: 1rem;">Questions?</h3>
        <p style="color: #94a3b8; margin: 0;">
            Contact us at <strong style="color: #fff;">info@pagewatch.io</strong>
        </p>
    </div>
</main>

</body>
</html>