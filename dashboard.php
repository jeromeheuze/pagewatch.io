<?php
session_start();
include './bin/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user plan to enforce limits
$stmt = $DBcon->prepare("SELECT plan FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$user_plan = $user['plan'];

// Set plan limits
$plan_limits = [
        'free' => 3,
        'starter' => 10,
        'pro' => 50
];
$max_urls = $plan_limits[$user_plan];

// Create websites table if not exists (for quick setup)
$DBcon->query("CREATE TABLE IF NOT EXISTS websites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    url VARCHAR(500),
    name VARCHAR(255),
    frequency ENUM('weekly', 'daily', 'hourly') DEFAULT 'weekly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

$DBcon->query("CREATE TABLE IF NOT EXISTS screenshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT,
    file_path VARCHAR(500),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (website_id) REFERENCES websites(id)
)");

// Handle form submission
$message = '';
if ($_POST['action'] === 'add_website') {
    $url = trim($_POST['url']);
    $name = trim($_POST['name']) ?: parse_url($url, PHP_URL_HOST);

    // Check URL limit
    $count_stmt = $DBcon->prepare("SELECT COUNT(*) as count FROM websites WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $current_count = $count_stmt->get_result()->fetch_assoc()['count'];

    if ($current_count >= $max_urls) {
        $message = "You've reached your plan limit of {$max_urls} websites. <a href='upgrade.php'>Upgrade</a> to add more.";
    } else {
        // Add website
        $stmt = $DBcon->prepare("INSERT INTO websites (user_id, url, name) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $url, $name);
        if ($stmt->execute()) {
            $message = "Website added successfully!";
        } else {
            $message = "Error adding website.";
        }
    }
}

// Handle screenshot request
if ($_POST['action'] === 'take_screenshot') {
    $website_id = $_POST['website_id'];

    // Add screenshot request
    $stmt = $DBcon->prepare("INSERT INTO screenshots (website_id, status) VALUES (?, 'pending')");
    $stmt->bind_param("i", $website_id);
    $stmt->execute();

    $message = "Screenshot request added! Check back in a few minutes.";
}

// Get user's websites
$websites_stmt = $DBcon->prepare("SELECT * FROM websites WHERE user_id = ? ORDER BY created_at DESC");
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
            margin-bottom: 2rem;
        }
        .form-wrap form {
            display: grid;
            gap: 1rem;
        }
        .plan-info {
            background: #1a1a1a;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #333;
        }
        .website-card {
            padding: 1rem;
        }
        .website-url {
            color: #3b82f6;
            font-size: 0.9rem;
            word-break: break-all;
        }
        .screenshot-status {
            margin-top: 1rem;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .status-pending { background: #fbbf24; color: #000; }
        .status-completed { background: #10b981; color: #fff; }
        .status-failed { background: #ef4444; color: #fff; }
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
    <h1 style="color:#fff;">Dashboard</h1>

    <div class="plan-info">
        <strong style="color: #fff;"><?php echo ucfirst($user_plan); ?> Plan</strong> -
        <?php
        $current_count = $websites->num_rows;
        echo "$current_count / $max_urls websites";
        ?>
        <?php if ($user_plan === 'free'): ?>
            <a href="upgrade.php" style="margin-left: 1rem; color: #3b82f6;">Upgrade for more</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <sl-alert variant="primary" open style="margin-bottom: 2rem;">
            <?php echo $message; ?>
        </sl-alert>
    <?php endif; ?>

    <!-- Add Website Form -->
    <div class="form-wrap">
        <sl-card>
            <h3 style="color: #fff; margin-bottom: 1rem;">Add Website to Monitor</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_website">
                <sl-input
                        type="url"
                        name="url"
                        label="Website URL"
                        placeholder="https://example.com"
                        required
                ></sl-input>
                <sl-input
                        type="text"
                        name="name"
                        label="Display Name (optional)"
                        placeholder="My Company Homepage"
                ></sl-input>
                <sl-button type="submit" variant="primary">Add Website</sl-button>
            </form>
        </sl-card>
    </div>

    <!-- Websites Grid -->
    <?php if ($websites->num_rows > 0): ?>
        <div class="grid">
            <?php while ($website = $websites->fetch_assoc()): ?>
                <sl-card class="website-card">
                    <h4 style="color: #fff; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($website['name']); ?></h4>
                    <div class="website-url"><?php echo htmlspecialchars($website['url']); ?></div>
                    <div style="color: #94a3b8; font-size: 0.8rem; margin: 0.5rem 0;">
                        Added: <?php echo date('M j, Y', strtotime($website['created_at'])); ?>
                    </div>

                    <!-- Take Screenshot Button -->
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="take_screenshot">
                        <input type="hidden" name="website_id" value="<?php echo $website['id']; ?>">
                        <sl-button type="submit" variant="default" size="small">Take Screenshot</sl-button>
                    </form>

                    <?php
                    // Get latest screenshot status
                    $screenshot_stmt = $DBcon->prepare("SELECT * FROM screenshots WHERE website_id = ? ORDER BY taken_at DESC LIMIT 1");
                    $screenshot_stmt->bind_param("i", $website['id']);
                    $screenshot_stmt->execute();
                    $screenshot = $screenshot_stmt->get_result()->fetch_assoc();

                    if ($screenshot):
                        ?>
                        <div class="screenshot-status status-<?php echo $screenshot['status']; ?>">
                            Status: <?php echo ucfirst($screenshot['status']); ?>
                            <br>Requested: <?php echo date('M j, H:i', strtotime($screenshot['taken_at'])); ?>
                        </div>
                    <?php endif; ?>
                </sl-card>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #94a3b8;">
            <h3>No websites added yet</h3>
            <p>Add your first website above to start monitoring!</p>
        </div>
    <?php endif; ?>

</main>

</body>
</html>