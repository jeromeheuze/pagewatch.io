<?php
session_start();
include './bin/dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    $stmt = $DBcon->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;
        $update = $DBcon->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <title>Login – PageWatch.io</title>
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
        <h2>Login</h2>
        <form method="POST">
            <sl-input type="email" name="email" label="Email" required></sl-input>
            <sl-input type="password" name="password" label="Password" required></sl-input>
            <sl-button type="submit" variant="primary">Login</sl-button>
            <?php if (isset($error)) echo "<sl-alert variant='danger' open><sl-icon slot='icon' name='x-circle'></sl-icon>$error</sl-alert>"; ?>
        </form>
        <p style="margin-top: 1rem; text-align:center; font-size: 0.9rem;">
            Don’t have an account? <a href="register.php">Sign up</a>
        </p>
    </sl-card>
</main>
<?php include './includes/footer.php'; ?>
</div>
</body>
</html>
