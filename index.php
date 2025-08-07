<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PageWatch.io – Monitor Competitor Websites with Automated Visual Tracking</title>
    <meta name="description" content="PageWatch.io automatically captures screenshots of competitor websites, tracks changes, and alerts you. Stay ahead with visual monitoring and change detection." />
    <meta name="keywords" content="competitor monitoring, website screenshot tool, change detection, landing page tracker, visual website monitoring, SaaS spy, competitor alerts" />
    <meta name="author" content="PageWatch.io" />
    <link rel="canonical" href="https://pagewatch.io/" />

    <!-- Open Graph Meta -->
    <meta property="og:title" content="PageWatch.io – Visual Competitor Monitoring Tool" />
    <meta property="og:description" content="Track competitor websites with daily screenshots and instant change alerts. Perfect for marketers, agencies, and SaaS founders." />
    <meta property="og:url" content="https://pagewatch.io/" />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="https://pagewatch.io/og-pagewatch.png" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="PageWatch.io – Competitor Page Screenshot Tracker" />
    <meta name="twitter:description" content="Monitor any website for visual changes. Get alerts when a competitor updates landing pages, pricing, or features." />
    <meta name="twitter:image" content="https://pagewatch.io/og-pagewatch.png" />

    <!-- Shoelace -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/themes/dark.css" />
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.15.0/cdn/shoelace.js"></script>

    <style>
        body {
            margin: 0;
            font-family: system-ui, sans-serif;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        main {
            max-width: 600px;
            width: 100%;
            margin-top: 1rem;
        }

        sl-card::part(base) {
            padding: 2rem;
            background: #1a1a1a;
            box-shadow: var(--sl-shadow-large);
            border-radius: var(--sl-border-radius-large);
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 0.75rem;
            color: var(--sl-color-primary-500);
        }

        form {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        #statusContainer {
            margin-top: 1rem;
            width: 100%;
        }

        p {
            color: #ccc;
            margin: 0.5rem 0 1.5rem;
            font-size: 1rem;
        }
        .mb-4 {
            margin-bottom: 1.5rem;
        }
    </style>

    <?php include 'includes/ga.php'; ?>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebApplication",
            "name": "PageWatch.io",
            "url": "https://pagewatch.io/",
            "applicationCategory": "MarketingTool",
            "operatingSystem": "All",
            "browserRequirements": "Requires JavaScript",
            "description": "PageWatch.io is a website change monitoring tool that captures screenshots of competitor pages, detects visual changes, and sends alerts.",
            "offers": {
                "@type": "Offer",
                "price": "0.00",
                "priceCurrency": "USD",
                "availability": "https://schema.org/PreOrder",
                "category": "free"
            },
            "creator": {
                "@type": "Organization",
                "name": "PageWatch.io",
                "url": "https://pagewatch.io/"
            }
        }
    </script>
</head>
<body>
<main>
    <sl-card class="mb-4">
        <h1 class="logo">Monitor Competitor Websites with Automated Visual Snapshots</h1>
        <p><strong>PageWatch.io tracks your competitors' websites by capturing daily or weekly screenshots and highlighting visual changes.</strong></p>
        <p>Get notified when landing pages, pricing, or feature pages change. Ideal for SaaS teams, marketers, and product strategists who want to stay ahead of the competition.</p>
    </sl-card>
    <sl-card>
        <h2>Coming Soon</h2>
        <p>Want to be the first to know when we launch full tracking? Leave your email:</p>
        <form id="notifyForm">
            <sl-input type="email" name="email" id="email" placeholder="Enter your email" filled required style="width: 100%; max-width: 300px;"></sl-input>
            <sl-button type="submit" variant="primary">Notify Me</sl-button>
        </form>
        <div id="statusContainer"></div>
    </sl-card>
    <footer style="text-align: center; padding: 1.5rem 1rem; font-size: 0.9rem; color: #888;">
        <p>&copy; <?=date("Y");?> <a href="/">PageWatch.io</a> – All rights reserved.</p>
    </footer>
</main>

<script>
    const form = document.getElementById('notifyForm');
    const statusContainer = document.getElementById('statusContainer');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('email').value;

        fetch('notify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `email=${encodeURIComponent(email)}`
        })
            .then(res => {
                const success = res.ok;
                const alert = document.createElement('sl-alert');
                alert.variant = success ? 'success' : 'danger';
                alert.duration = 4000;
                alert.innerHTML = success
                    ? '<sl-icon slot="icon" name="check-circle"></sl-icon> Thanks! You’ll be notified when we launch.'
                    : '<sl-icon slot="icon" name="exclamation-triangle"></sl-icon> Something went wrong. Try again later.';
                statusContainer.innerHTML = '';
                statusContainer.appendChild(alert);
                alert.toast();
                alert.show();
                if (success) document.getElementById('email').value = '';
            })
            .catch(() => {
                const alert = document.createElement('sl-alert');
                alert.variant = 'danger';
                alert.duration = 4000;
                alert.innerHTML = '<sl-icon slot="icon" name="x-circle"></sl-icon> Network error. Try again later.';
                statusContainer.innerHTML = '';
                statusContainer.appendChild(alert);
                alert.toast();
                alert.show();
            });
    });
</script>
</body>
</html>
