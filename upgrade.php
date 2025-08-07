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
        }
        aside nav a:hover {
            color: var(--sl-color-primary-500);
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
    </style>
</head>
<body>

<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="workers.php">Workers</a>
        <a href="upgrade.php"><strong>Upgrade</strong></a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <h1 style="color:#fff;">Upgrade Your Plan</h1>
    <div style="display: flex; gap: 2rem; flex-wrap: wrap; justify-content: center; margin-top: 2rem;margin-bottom: 2rem;">

        <!-- Starter Plan Card -->
        <sl-card style="max-width: 300px;">
            <h2>Starter</h2>
            <p>$9/month</p>
            <ul>
                <li>Track up to 10 URLs</li>
                <li>Weekly frequency</li>
                <li>Alerts, history, gallery</li>
            </ul>
            <form action="create-checkout-session.php" method="POST">
                <input type="hidden" name="plan" value="starter">
                <sl-button variant="primary" type="submit" style="margin-top: 1rem;">Choose Starter</sl-button>
            </form>
        </sl-card>

        <!-- Pro Plan Card -->
        <sl-card style="max-width: 300px;">
            <h2>Pro</h2>
            <p>$29/month</p>
            <ul>
                <li>Track up to 50 URLs</li>
                <li>Daily frequency</li>
                <li>Alerts, compare changes, export</li>
            </ul>
            <form action="create-checkout-session.php" method="POST">
                <input type="hidden" name="plan" value="pro">
                <sl-button variant="success" type="submit" style="margin-top: 1rem;">Choose Pro</sl-button>
            </form>
        </sl-card>

    </div>
    <sl-alert variant="warning" open style="max-width: 300px;">
        if you have any questions please contact us at: <strong>info@pagewatch.io</strong>
    </sl-alert>
</main>


</body>
</html>
