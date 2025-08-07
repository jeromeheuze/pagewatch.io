<?php
session_start();
include './bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];



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
        }
        aside nav a:hover {
            color: var(--sl-color-primary-500);
        }
        main {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        .grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            margin-top: 2rem;
        }
        sl-card::part(base) {
            background: #1a1a1a;
            border-radius: var(--sl-border-radius-large);
            box-shadow: var(--sl-shadow-large);
        }
        .screenshot-preview {
            margin-top: 0.75rem;
            max-width: 100%;
            border-radius: 0.5rem;
            border: 1px solid #333;
        }
        .form-wrap {
            max-width: 500px;
            margin-top: 2rem;
        }
        .upgrade-banner {
            margin-top: 2rem;
            padding: 1rem;
            background: #222;
            border: 1px dashed #444;
            color: #ccc;
            text-align: center;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div id="refresh-progress" style="
    position: fixed;
    top: 0;
    left: 0;
    height: 4px;
    width: 0%;
    background-color: #0af;
    transition: width 1s linear;
    z-index: 9999;
"></div>
<aside>
    <div class="logo">PageWatch.io</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<main>
    <h1 style="color:#fff;">Dashboard</h1>

</main>

</body>
</html>
