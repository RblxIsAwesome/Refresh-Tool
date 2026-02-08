<?php
/**
 * Landing Page
 * 
 * Displays the main landing page with login prompt.
 * Redirects authenticated users to dashboard.
 * 
 * @package RobloxRefresher
 * @author  Your Name
 * @version 1.0.0
 */

session_start();

// Redirect authenticated users
if (isset($_SESSION['discord_user'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure Roblox cookie refresher with Discord authentication">
    <title>Roblox Refresher - Secure Cookie Management</title>
    <style>
        /* CSS Variables */
        :root {
            --bg-primary: #070A12;
            --bg-secondary: #050712;
            --text-primary: rgba(255, 255, 255, 0.92);
            --text-secondary: rgba(255, 255, 255, 0.62);
            --accent-blue: #7CB6FF;
            --accent-blue-light: #A8CEFF;
            --card-bg: rgba(255, 255, 255, 0.06);
            --card-border: rgba(140, 190, 255, 0.14);
            --success-green: #29c27f;
        }

        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            background: 
                radial-gradient(900px 520px at 50% 26%, rgba(124, 182, 255, 0.1), transparent 60%),
                radial-gradient(700px 420px at 50% 42%, rgba(124, 182, 255, 0.06), transparent 58%),
                linear-gradient(180deg, var(--bg-primary), var(--bg-secondary));
            display: grid;
            place-items: center;
            padding: 28px 16px;
            min-height: 100vh;
        }

        /* Container */
        .container {
            width: min(760px, 100%);
            text-align: center;
        }

        /* Header */
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }

        .logo {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(140, 190, 255, 0.18);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.52);
        }

        .logo svg {
            width: 22px;
            height: 22px;
            color: var(--accent-blue);
        }

        h1 {
            font-weight: 600;
            letter-spacing: -0.02em;
            font-size: clamp(28px, 4vw, 40px);
            line-height: 1.15;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* Card */
        .card {
            width: min(560px, 100%);
            margin: 0 auto;
            border-radius: 18px;
            border: 1px solid var(--card-border);
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            padding: 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -2px;
            background: radial-gradient(520px 180px at 40% 0%, rgba(124, 182, 255, 0.12), transparent 60%);
            opacity: 0.55;
            pointer-events: none;
        }

        .card-content {
            position: relative;
            z-index: 1;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(41, 194, 127, 0.15);
            border: 1px solid rgba(41, 194, 127, 0.3);
            color: var(--success-green);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Features */
        .features {
            display: grid;
            gap: 14px;
            margin: 24px 0;
            text-align: left;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px 16px;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(124, 182, 255, 0.12);
            border: 1px solid rgba(124, 182, 255, 0.2);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 18px;
            height: 18px;
            color: var(--accent-blue-light);
        }

        .feature-text {
            flex: 1;
        }

        .feature-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .feature-desc {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        /* Button */
        .btn {
            width: 100%;
            height: 52px;
            border: 1px solid rgba(124, 182, 255, 0.26);
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(88, 101, 242, 0.4), rgba(88, 101, 242, 0.3));
            color: var(--text-primary);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            margin-top: 24px;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: linear-gradient(180deg, rgba(88, 101, 242, 0.5), rgba(88, 101, 242, 0.4));
            border-color: rgba(124, 182, 255, 0.4);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn svg {
            width: 24px;
            height: 24px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .footer span {
            color: var(--accent-blue-light);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo" aria-label="Roblox Refresher Logo">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.5l7 3.6v6.2c0 5.1-3.1 9.1-7 10.7-3.9-1.6-7-5.6-7-10.7V6.1l7-3.6z" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M12 6.2v13.6" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2"/>
                </svg>
            </div>
            <h1>Roblox Refresher</h1>
            <p class="subtitle">Secure • Fast • Reliable</p>
        </header>

        <main class="card">
            <div class="card-content">
                <span class="badge">
                    <svg viewBox="0 0 16 16" fill="currentColor" width="14" height="14">
                        <path d="M8 0a8 8 0 110 16A8 8 0 018 0zm3.97 4.97a.75.75 0 00-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 00-1.06 1.06L6.97 11.03a.75.75 0 001.079-.02l3.992-4.99a.75.75 0 00-.01-1.05z"/>
                    </svg>
                    Free Access
                </span>

                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <div class="feature-title">Instant Refresh</div>
                            <div class="feature-desc">Refresh your Roblox cookies in seconds</div>
                        </div>
                    </div>

                    <div class="feature">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <div class="feature-title">Complete Stats</div>
                            <div class="feature-desc">View Robux, RAP, premium status & more</div>
                        </div>
                    </div>

                    <div class="feature">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <div class="feature-title">Secure Login</div>
                            <div class="feature-desc">OAuth2 authentication with Discord</div>
                        </div>
                    </div>

                    <div class="feature">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="feature-text">
                            <div class="feature-title">Analytics</div>
                            <div class="feature-desc">Track success rates and leaderboards</div>
                        </div>
                    </div>
                </div>

                <a href="login.php" class="btn">
                    <svg viewBox="0 0 71 55" fill="none">
                        <path d="M60.1045 4.8978C55.5792 2.8214 50.7265 1.2916 45.6527 0.41542C45.5603 0.39851 45.468 0.440769 45.4204 0.525289C44.7963 1.6353 44.105 3.0834 43.6209 4.2216C38.1637 3.4046 32.7345 3.4046 27.3892 4.2216C26.905 3.0581 26.1886 1.6353 25.5617 0.525289C25.5141 0.443589 25.4218 0.40133 25.3294 0.41542C20.2584 1.2888 15.4057 2.8186 10.8776 4.8978C10.8384 4.9147 10.8048 4.9429 10.7825 4.9795C1.57795 18.7309 -0.943561 32.1443 0.293408 45.3914C0.299005 45.4562 0.335386 45.5182 0.385761 45.5576C6.45866 50.0174 12.3413 52.7249 18.1147 54.5195C18.2071 54.5477 18.305 54.5139 18.3638 54.4378C19.7295 52.5728 20.9469 50.6063 21.9907 48.5383C22.0523 48.4172 21.9935 48.2735 21.8676 48.2256C19.9366 47.4931 18.0979 46.6 16.3292 45.5858C16.1893 45.5041 16.1781 45.304 16.3068 45.2082C16.679 44.9293 17.0513 44.6391 17.4067 44.3461C17.471 44.2926 17.5606 44.2813 17.6362 44.3151C29.2558 49.6202 41.8354 49.6202 53.3179 44.3151C53.3935 44.2785 53.4831 44.2898 53.5502 44.3433C53.9057 44.6363 54.2779 44.9293 54.6529 45.2082C54.7816 45.304 54.7732 45.5041 54.6333 45.5858C52.8646 46.6197 51.0259 47.4931 49.0921 48.2228C48.9662 48.2707 48.9102 48.4172 48.9718 48.5383C50.038 50.6034 51.2554 52.5699 52.5959 54.435C52.6519 54.5139 52.7526 54.5477 52.845 54.5195C58.6464 52.7249 64.529 50.0174 70.6019 45.5576C70.6551 45.5182 70.6887 45.459 70.6943 45.3942C72.1747 30.0791 68.2147 16.7757 60.1968 4.9823C60.1772 4.9429 60.1437 4.9147 60.1045 4.8978ZM23.7259 37.3253C20.2276 37.3253 17.3451 34.1136 17.3451 30.1693C17.3451 26.225 20.1717 23.0133 23.7259 23.0133C27.308 23.0133 30.1626 26.2532 30.1066 30.1693C30.1066 34.1136 27.28 37.3253 23.7259 37.3253ZM47.3178 37.3253C43.8196 37.3253 40.9371 34.1136 40.9371 30.1693C40.9371 26.225 43.7636 23.0133 47.3178 23.0133C50.9 23.0133 53.7545 26.2532 53.6986 30.1693C53.6986 34.1136 50.9 37.3253 47.3178 37.3253Z" fill="currentColor"/>
                    </svg>
                    Login with Discord
                </a>
            </div>
        </main>

        <footer class="footer">
            <span>🔒 Secure</span> • 
            <span>⚡ Fast</span> • 
            <span>🌟 Free</span>
            <br>
            Open to everyone — no restrictions
        </footer>
    </div>
</body>
</html>
