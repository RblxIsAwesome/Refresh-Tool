<?php
// Start session with long lifetime (30 days)
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

function parse_env(): array {
    // First try Railway environment variables
    if (!empty($_ENV) || !empty($_SERVER)) {
        return [
            'DISCORD_CLIENT_ID' => $_ENV['DISCORD_CLIENT_ID'] ?? $_SERVER['DISCORD_CLIENT_ID'] ?? '',
            'DISCORD_CLIENT_SECRET' => $_ENV['DISCORD_CLIENT_SECRET'] ?? $_SERVER['DISCORD_CLIENT_SECRET'] ?? '',
            'DISCORD_REDIRECT_URI' => $_ENV['DISCORD_REDIRECT_URI'] ?? $_SERVER['DISCORD_REDIRECT_URI'] ?? '',
        ];
    }
    
    // Fallback to local config file
    $envFile = __DIR__ . '/../config/env.txt';
    if (is_file($envFile)) {
        $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        return is_array($vars) ? $vars : [];
    }
    
    return [];
}

$env = parse_env();

// Validate we have the required OAuth code
if (!isset($_GET['code'])) {
    die('Error: No authorization code received. <a href="login.php">Try again</a>');
}

$code = $_GET['code'];

// Validate environment variables
if (empty($env['DISCORD_CLIENT_ID']) || empty($env['DISCORD_CLIENT_SECRET']) || empty($env['DISCORD_REDIRECT_URI'])) {
    die('Error: Discord OAuth credentials not configured. Please check environment variables.');
}

// Exchange code for access token
$tokenUrl = 'https://discord.com/api/oauth2/token';
$tokenData = [
    'client_id' => $env['DISCORD_CLIENT_ID'],
    'client_secret' => $env['DISCORD_CLIENT_SECRET'],
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $env['DISCORD_REDIRECT_URI']
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die('Error: Failed to get access token from Discord. HTTP ' . $httpCode);
}

$tokenInfo = json_decode($response, true);

if (!isset($tokenInfo['access_token'])) {
    die('Error: Invalid token response from Discord. <a href="login.php">Try again</a>');
}

// Get user info from Discord
$userUrl = 'https://discord.com/api/users/@me';
$ch = curl_init($userUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenInfo['access_token']
]);

$userResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die('Error: Failed to get user data from Discord. HTTP ' . $httpCode);
}

$userData = json_decode($userResponse, true);

if (!isset($userData['id'])) {
    die('Error: Invalid user data from Discord. <a href="login.php">Try again</a>');
}

// ✅ SUCCESS - Save user to session (OPEN TO EVERYONE)
$_SESSION['discord_user'] = [
    'id' => $userData['id'],
    'username' => $userData['username'],
    'discriminator' => $userData['discriminator'] ?? '0',
    'avatar' => $userData['avatar'],
    'email' => $userData['email'] ?? null,
    'login_time' => time(),
    'remember' => true
];

// Redirect to dashboard
header('Location: dashboard.php');
exit;
