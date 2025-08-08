<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" class="sl-theme-dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- SEO Meta Tags -->
    <title>Privacy Policy - PageWatch.io | Website Screenshot Monitoring</title>
    <meta name="description" content="Privacy Policy for PageWatch.io. Learn how we protect your data when using our automated website screenshot monitoring service." />
    <meta name="robots" content="index, follow" />

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Privacy Policy - PageWatch.io" />
    <meta property="og:description" content="Privacy Policy for PageWatch.io automated website screenshot monitoring service." />
    <meta property="og:url" content="https://pagewatch.io/privacy/" />
    <meta property="og:site_name" content="PageWatch.io" />

    <!-- Canonical URL -->
    <link rel="canonical" href="https://pagewatch.io/privacy" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />

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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .page-title {
            font-size: clamp(2rem, 4vw, 3rem);
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .last-updated {
            color: #64748b;
            font-size: 0.9rem;
        }

        .content-section {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .toc {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .toc h3 {
            color: #3b82f6;
            margin-top: 0;
            margin-bottom: 1rem;
        }

        .toc ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .toc li {
            margin-bottom: 0.5rem;
        }

        .toc a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        .toc a:hover {
            color: #3b82f6;
        }

        h1, h2, h3, h4 {
            color: #fff;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2rem;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.5rem;
            color: #3b82f6;
        }

        h3 {
            font-size: 1.2rem;
            color: #8b5cf6;
        }

        p, li {
            color: #e2e8f0;
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        ul, ol {
            padding-left: 1.5rem;
        }

        li {
            margin-bottom: 0.5rem;
        }

        strong {
            color: #fff;
        }

        .highlight-box {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .warning-box {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .contact-section {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
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
            .nav-links {
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .content-section {
                padding: 1.5rem;
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
                <a href="/dashboard.php">Dashboard</a>
                <a href="/workers.php">Workers</a>
                <a href="/upgrade.php">Upgrade</a>
                <sl-button href="logout.php" variant="default" size="small">Logout</sl-button>
            <?php else: ?>
                <a href="/">Home</a>
                <a href="/#features">Features</a>
                <a href="/#pricing">Pricing</a>
                <sl-button href="/login.php" variant="default" size="small">Login</sl-button>
                <sl-button href="/register.php" variant="primary" size="small">Sign Up Free</sl-button>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Privacy Policy</h1>
        <p class="page-subtitle">
            Your privacy is important to us. This policy explains how we collect, use, and protect your information.
        </p>
        <div class="last-updated">
            Last updated: <?= date("F j, Y") ?>
        </div>
    </div>

    <!-- Table of Contents -->
    <div class="toc">
        <h3>📋 Table of Contents</h3>
        <ul>
            <li><a href="#information-we-collect">1. Information We Collect</a></li>
            <li><a href="#how-we-use-information">2. How We Use Your Information</a></li>
            <li><a href="#data-storage">3. Data Storage and Security</a></li>
            <li><a href="#screenshot-data">4. Screenshot Data Handling</a></li>
            <li><a href="#third-party-services">5. Third-Party Services</a></li>
            <li><a href="#data-retention">6. Data Retention</a></li>
            <li><a href="#your-rights">7. Your Rights</a></li>
            <li><a href="#cookies">8. Cookies and Tracking</a></li>
            <li><a href="#international-transfers">9. International Data Transfers</a></li>
            <li><a href="#changes">10. Changes to This Policy</a></li>
            <li><a href="#contact">11. Contact Us</a></li>
        </ul>
    </div>

    <div class="content-section">
        <h1 id="information-we-collect">1. Information We Collect</h1>

        <h2>Account Information</h2>
        <p>When you create an account with PageWatch.io, we collect:</p>
        <ul>
            <li><strong>Email address:</strong> Used for account authentication and service communications</li>
            <li><strong>Password:</strong> Stored in encrypted form using industry-standard hashing</li>
            <li><strong>Plan information:</strong> Your subscription level and usage limits</li>
            <li><strong>Usage data:</strong> Screenshot counts, website monitoring settings</li>
        </ul>

        <h2>Website Monitoring Data</h2>
        <p>To provide our screenshot monitoring service, we collect:</p>
        <ul>
            <li><strong>Website URLs:</strong> The websites you choose to monitor</li>
            <li><strong>Website names:</strong> Display names you assign to monitored sites</li>
            <li><strong>Monitoring frequency:</strong> How often you want screenshots taken</li>
            <li><strong>Screenshots:</strong> Images captured from the websites you monitor</li>
        </ul>

        <h2>Technical Information</h2>
        <p>We automatically collect certain technical information:</p>
        <ul>
            <li><strong>IP addresses:</strong> For security and abuse prevention</li>
            <li><strong>Browser information:</strong> User agent strings for compatibility</li>
            <li><strong>Usage patterns:</strong> How you interact with our service</li>
            <li><strong>Error logs:</strong> Technical issues for service improvement</li>
        </ul>
    </div>

    <div class="content-section">
        <h1 id="how-we-use-information">2. How We Use Your Information</h1>

        <p>We use your information for the following purposes:</p>

        <h2>Service Provision</h2>
        <ul>
            <li>Providing website screenshot monitoring services</li>
            <li>Processing and storing screenshots on our CDN</li>
            <li>Managing your account and subscription</li>
            <li>Providing customer support</li>
        </ul>

        <h2>Service Improvement</h2>
        <ul>
            <li>Analyzing usage patterns to improve our service</li>
            <li>Developing new features and capabilities</li>
            <li>Optimizing our hardware infrastructure</li>
            <li>Debugging and fixing technical issues</li>
        </ul>

        <h2>Communication</h2>
        <ul>
            <li>Sending service-related notifications</li>
            <li>Providing technical support</li>
            <li>Notifying you of important service changes</li>
            <li>Marketing communications (with your consent)</li>
        </ul>

        <div class="highlight-box">
            <strong>🔒 We never sell your personal information to third parties.</strong>
        </div>
    </div>

    <div class="content-section">
        <h1 id="data-storage">3. Data Storage and Security</h1>

        <h2>Security Measures</h2>
        <p>We implement comprehensive security measures to protect your data:</p>
        <ul>
            <li><strong>Encryption:</strong> All data is encrypted in transit using TLS/SSL</li>
            <li><strong>Password Security:</strong> Passwords are hashed using bcrypt</li>
            <li><strong>Access Controls:</strong> Limited access to personal data on a need-to-know basis</li>
            <li><strong>Regular Security Audits:</strong> Ongoing security assessments and improvements</li>
        </ul>

        <h2>Infrastructure Security</h2>
        <p>Our dedicated hardware infrastructure includes:</p>
        <ul>
            <li><strong>Isolated Processing:</strong> Screenshots processed on dedicated ARM hardware</li>
            <li><strong>Secure Networks:</strong> Private networks for hardware communication</li>
            <li><strong>Physical Security:</strong> Secure data center hosting</li>
            <li><strong>Regular Updates:</strong> Automated security updates for all systems</li>
        </ul>

        <div class="warning-box">
            <strong>⚠️ No system is 100% secure.</strong> While we implement industry-standard security measures, we cannot guarantee absolute security. Please use strong, unique passwords and report any security concerns immediately.
        </div>
    </div>

    <div class="content-section">
        <h1 id="screenshot-data">4. Screenshot Data Handling</h1>

        <h2>Screenshot Processing</h2>
        <p>Our screenshot processing follows strict privacy guidelines:</p>
        <ul>
            <li><strong>Automated Processing:</strong> Screenshots are taken automatically by our hardware</li>
            <li><strong>No Human Review:</strong> We do not manually review your screenshots</li>
            <li><strong>Temporary Storage:</strong> Screenshots are temporarily stored during processing</li>
            <li><strong>CDN Upload:</strong> Final screenshots are stored on our global CDN</li>
        </ul>

        <h2>Screenshot Access</h2>
        <ul>
            <li><strong>Your Access:</strong> Only you can access your screenshots</li>
            <li><strong>URL Security:</strong> Screenshot URLs are unique and not easily guessable</li>
            <li><strong>No Sharing:</strong> We do not share your screenshots with third parties</li>
            <li><strong>Automatic Cleanup:</strong> Screenshots are automatically deleted based on your plan's retention period</li>
        </ul>

        <h2>Screenshot Content</h2>
        <div class="highlight-box">
            <p><strong>🔍 Important:</strong> We only screenshot publicly accessible websites that you specify. We do not access password-protected content, private areas, or personal information unless explicitly provided by you through the monitored URLs.</p>
        </div>
    </div>

    <div class="content-section">
        <h1 id="third-party-services">5. Third-Party Services</h1>

        <p>We use select third-party services to operate PageWatch.io:</p>

        <h2>CDN Services (BunnyCDN)</h2>
        <ul>
            <li><strong>Purpose:</strong> Global content delivery for fast screenshot access</li>
            <li><strong>Data Shared:</strong> Screenshot images only</li>
            <li><strong>Location:</strong> Global edge servers</li>
            <li><strong>Privacy Policy:</strong> <a href="https://bunny.net/privacy" target="_blank" style="color: #3b82f6;">BunnyCDN Privacy Policy</a></li>
        </ul>

        <h2>Payment Processing (Stripe)</h2>
        <ul>
            <li><strong>Purpose:</strong> Processing subscription payments</li>
            <li><strong>Data Shared:</strong> Payment information only</li>
            <li><strong>Security:</strong> PCI DSS compliant</li>
            <li><strong>Privacy Policy:</strong> <a href="https://stripe.com/privacy" target="_blank" style="color: #3b82f6;">Stripe Privacy Policy</a></li>
        </ul>

        <h2>Analytics</h2>
        <ul>
            <li><strong>Purpose:</strong> Understanding service usage and performance</li>
            <li><strong>Data Collected:</strong> Anonymized usage statistics</li>
            <li><strong>No Personal Data:</strong> We do not share personal information with analytics providers</li>
        </ul>
    </div>

    <div class="content-section">
        <h1 id="data-retention">6. Data Retention</h1>

        <h2>Account Data</h2>
        <ul>
            <li><strong>Active Accounts:</strong> Retained while your account is active</li>
            <li><strong>Deleted Accounts:</strong> Permanently deleted within 30 days of account deletion</li>
            <li><strong>Backup Systems:</strong> May be retained in backups for up to 90 days</li>
        </ul>

        <h2>Screenshot Data</h2>
        <p>Screenshot retention varies by plan:</p>
        <ul>
            <li><strong>Free Plan:</strong> 7 days</li>
            <li><strong>Starter Plan:</strong> 30 days</li>
            <li><strong>Pro Plan:</strong> 90 days</li>
        </ul>

        <h2>Legal Obligations</h2>
        <p>We may retain certain data longer if required by law or for legitimate business purposes such as fraud prevention or security investigations.</p>
    </div>

    <div class="content-section">
        <h1 id="your-rights">7. Your Rights</h1>

        <p>You have several rights regarding your personal data:</p>

        <h2>Access and Portability</h2>
        <ul>
            <li><strong>Data Access:</strong> Request a copy of your personal data</li>
            <li><strong>Data Export:</strong> Download your screenshots and account data</li>
            <li><strong>Account Dashboard:</strong> View and manage your data through your account</li>
        </ul>

        <h2>Correction and Deletion</h2>
        <ul>
            <li><strong>Data Correction:</strong> Update incorrect personal information</li>
            <li><strong>Account Deletion:</strong> Delete your account and associated data</li>
            <li><strong>Selective Deletion:</strong> Remove specific screenshots or websites</li>
        </ul>

        <h2>Control and Consent</h2>
        <ul>
            <li><strong>Marketing Opt-out:</strong> Unsubscribe from marketing communications</li>
            <li><strong>Service Control:</strong> Pause or modify monitoring settings</li>
            <li><strong>Data Processing:</strong> Object to certain data processing activities</li>
        </ul>

        <div class="highlight-box">
            <p><strong>🛠️ Exercise Your Rights:</strong> Contact us at <strong>privacy@pagewatch.io</strong> to exercise any of these rights. We'll respond within 30 days.</p>
        </div>
    </div>

    <div class="content-section">
        <h1 id="cookies">8. Cookies and Tracking</h1>

        <h2>Essential Cookies</h2>
        <p>We use essential cookies for:</p>
        <ul>
            <li><strong>Authentication:</strong> Keeping you logged in</li>
            <li><strong>Security:</strong> Preventing CSRF attacks</li>
            <li><strong>Preferences:</strong> Remembering your settings</li>
        </ul>

        <h2>Analytics Cookies</h2>
        <p>With your consent, we may use analytics cookies to:</p>
        <ul>
            <li>Understand how our service is used</li>
            <li>Improve user experience</li>
            <li>Identify and fix issues</li>
        </ul>

        <h2>Cookie Control</h2>
        <p>You can control cookies through your browser settings. Note that disabling essential cookies may affect service functionality.</p>
    </div>

    <div class="content-section">
        <h1 id="international-transfers">9. International Data Transfers</h1>

        <p>PageWatch.io operates globally, which may involve transferring your data internationally:</p>

        <h2>Data Location</h2>
        <ul>
            <li><strong>Primary Servers:</strong> United States</li>
            <li><strong>CDN Network:</strong> Global edge servers for fast access</li>
            <li><strong>Backup Systems:</strong> Geographically distributed for reliability</li>
        </ul>

        <h2>Transfer Safeguards</h2>
        <ul>
            <li><strong>Encryption:</strong> All data encrypted in transit and at rest</li>
            <li><strong>Contractual Protections:</strong> Data processing agreements with all service providers</li>
            <li><strong>Limited Access:</strong> Data access restricted to necessary personnel only</li>
        </ul>
    </div>

    <div class="content-section">
        <h1 id="changes">10. Changes to This Policy</h1>

        <p>We may update this privacy policy from time to time. When we do:</p>
        <ul>
            <li><strong>Notification:</strong> We'll notify you via email of significant changes</li>
            <li><strong>Posted Changes:</strong> Updates will be posted on this page</li>
            <li><strong>Effective Date:</strong> Changes take effect 30 days after notification</li>
            <li><strong>Continued Use:</strong> Continued use constitutes acceptance of changes</li>
        </ul>

        <div class="warning-box">
            <p><strong>📧 Stay Informed:</strong> Make sure your email address is up to date to receive important policy notifications.</p>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="contact-section" id="contact">
        <h1>11. Contact Us</h1>
        <p style="color: #94a3b8; margin-bottom: 2rem;">
            Have questions about this privacy policy or how we handle your data?
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; text-align: left; margin-bottom: 2rem;">
            <div>
                <h3 style="color: #3b82f6; margin-bottom: 1rem;">📧 Email</h3>
                <p style="color: #e2e8f0;">
                    <strong>Privacy Questions:</strong><br>
                    <a href="mailto:info@pagewatch.io" style="color: #3b82f6;">info@pagewatch.io</a>
                </p>
                <p style="color: #e2e8f0;">
                    <strong>General Support:</strong><br>
                    <a href="mailto:info@pagewatch.io" style="color: #3b82f6;">info@pagewatch.io</a>
                </p>
            </div>

            <div>
                <h3 style="color: #3b82f6; margin-bottom: 1rem;">⏱️ Response Time</h3>
                <p style="color: #e2e8f0;">
                    <strong>Privacy Requests:</strong> Within 30 days<br>
                    <strong>General Support:</strong> Within 24 hours<br>
                    <strong>Urgent Issues:</strong> Within 4 hours
                </p>
            </div>
        </div>

        <?php if ($is_logged_in): ?>
            <sl-button href="/dashboard.php" variant="primary" size="large">
                Return to Dashboard
            </sl-button>
        <?php else: ?>
            <sl-button href="/" variant="primary" size="large">
                Return to Home
            </sl-button>
        <?php endif; ?>
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

    // Highlight current section in table of contents
    const observerOptions = {
        rootMargin: '-20% 0px -70% 0px',
        threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const id = entry.target.getAttribute('id');
            const tocLink = document.querySelector(`.toc a[href="#${id}"]`);

            if (entry.isIntersecting) {
                // Remove active class from all TOC links
                document.querySelectorAll('.toc a').forEach(link => {
                    link.style.color = '#94a3b8';
                    link.style.fontWeight = 'normal';
                });

                // Add active class to current section
                if (tocLink) {
                    tocLink.style.color = '#3b82f6';
                    tocLink.style.fontWeight = 'bold';
                }
            }
        });
    }, observerOptions);

    // Observe all sections with IDs
    document.querySelectorAll('[id]').forEach(section => {
        observer.observe(section);
    });
</script>
</body>
</html>