<?php
session_start();
include './bin/dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $DBcon->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Email already exists.";
    } else {
        $stmt = $DBcon->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hash);
        $stmt->execute();
        $_SESSION['user_id'] = $stmt->insert_id;
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Register – PageWatch.io</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>
    <style>
        body {
            background: #111;
            display: flex;
            flex-direction: column; /* Make header, main, footer stack vertically */
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: system-ui, sans-serif;
        }

        main {
            max-width: 600px;
            width: 100%;
            padding: 1rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        sl-card {
            max-width: 600px;
            width: 100%;
        }
        sl-card::part(base) {
            padding: 2rem;
            background: #1a1a1a;
            box-shadow: var(--sl-shadow-large);
            border-radius: var(--sl-border-radius-large);
        }
        sl-input, sl-button {
            width: 100%;
        }
        form {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div style="display: flex; flex-direction: column;align-items: center; min-height: 100vh; width: 100%;">
    <?php include './includes/header.php'; ?>
    <main>
        <sl-card>
            <h2>Register</h2>
            <form method="POST">
                <sl-input type="email" name="email" label="Email" required></sl-input>
                <sl-input type="password" name="password" label="Password" required></sl-input>
                <sl-button type="submit" variant="primary">Create Account</sl-button>
                <?php if (isset($error)) echo "<sl-alert variant='danger' open><sl-icon slot='icon' name='x-circle'></sl-icon>$error</sl-alert>"; ?>
            </form>
            <p style="margin-top: 1rem; text-align:center; font-size: 0.9rem;">
                Already have an account? <a href="login.php">Login</a>
            </p>
        </sl-card>
    </main>
    <?php include './includes/footer.php'; ?>
</div>
</body>
</html>
