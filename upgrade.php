<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
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
        .plans-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            align-items: end;
            justify-content: center;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="/">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php" class="active">Upgrade</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <h1 style="color:#fff;text-align: center;">Simple Website Screenshot Monitoring</h1>

    <p style="color: #94a3b8; font-size: 1.1rem; text-align: center; max-width: 600px; margin: 0 auto 3rem auto;">
        Track changes to your websites with automatic screenshots. Get notified when something changes.
    </p>

    <div class="plans-container">
        <!-- Free Plan -->
        <sl-card style="max-width: 280px;">
            <h2 style="color: #fff; margin-bottom: 0.5rem;">Free</h2>
            <p style="color: #94a3b8; font-size: 2rem; font-weight: bold; margin: 0;">$0</p>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Try it out</p>

            <ul style="color: #94a3b8; font-size: 0.9rem; line-height: 1.8; margin-bottom: 2rem;">
                <li><strong>3 websites</strong></li>
                <li>Weekly screenshots</li>
                <li>View last 7 days</li>
            </ul>

            <sl-button variant="default" style="width: 100%;" disabled>Current Plan</sl-button>
        </sl-card>

        <!-- Starter Plan -->
        <sl-card style="max-width: 280px; border: 2px solid #3b82f6;">
            <div style="position: absolute; top: -10px; right: 10px; background: #3b82f6; color: #fff; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem;">
                POPULAR
            </div>
            <h2 style="color: #fff; margin-bottom: 0.5rem;">Starter</h2>
            <p style="color: #3b82f6; font-size: 2rem; font-weight: bold; margin: 0;">$9</p>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">per month</p>

            <ul style="color: #94a3b8; font-size: 0.9rem; line-height: 1.8; margin-bottom: 2rem;">
                <li><strong>10 websites</strong></li>
                <li>Daily + weekly screenshots</li>
                <li>View last 30 days</li>
                <li>Compare changes side-by-side</li>
            </ul>

            <form action="create-checkout-session.php" method="POST">
                <input type="hidden" name="plan" value="starter">
                <sl-button variant="primary" type="submit" style="width: 100%;">Choose Starter</sl-button>
            </form>
        </sl-card>

        <!-- Pro Plan -->
        <sl-card style="max-width: 280px;">
            <h2 style="color: #fff; margin-bottom: 0.5rem;">Pro</h2>
            <p style="color: #10b981; font-size: 2rem; font-weight: bold; margin: 0;">$29</p>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">per month</p>

            <ul style="color: #94a3b8; font-size: 0.9rem; line-height: 1.8; margin-bottom: 2rem;">
                <li><strong>50 websites</strong></li>
                <li>Hourly, daily + weekly screenshots</li>
                <li>View last 90 days</li>
                <li>Compare changes side-by-side</li>
                <li>Download screenshots</li>
            </ul>

            <form action="create-checkout-session.php" method="POST">
                <input type="hidden" name="plan" value="pro">
                <sl-button variant="success" type="submit" style="width: 100%;">Choose Pro</sl-button>
            </form>
        </sl-card>
    </div>

    <div style="text-align: center; margin-top: 3rem; padding: 2rem; background: #1a1a1a; border-radius: 12px;">
        <h3 style="color: #fff; margin-bottom: 1rem;">How it works</h3>
        <p style="color: #94a3b8; max-width: 500px; margin: 0 auto;">
            Add your website URLs, choose how often to check them, and we'll automatically take screenshots.
            Check back anytime to see what changed on your websites.
        </p>

        <div style="margin-top: 2rem;">
            <p style="color: #64748b; font-size: 0.9rem;">
                Questions? Contact us at <strong style="color: #fff;">info@pagewatch.io</strong>
            </p>
        </div>
    </div>
</main>

</body>
</html>