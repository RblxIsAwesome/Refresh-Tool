<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session with long lifetime (30 days)
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

// Log that we reached this file
error_log("callback.php: Script started");

function parse_env(): array {
    error_log("callback.php: Parsing environment variables");
    
    // First try Railway environment variables
    if (!empty($_ENV) || !empty($_SERVER)) {
        $clientId = $_ENV['DISCORD_CLIENT_ID'] ?? $_SERVER['DISCORD_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['DISCORD_CLIENT_SECRET'] ?? $_SERVER['DISCORD_CLIENT_SECRET'] ?? '';
        $redirectUri = $_ENV['DISCORD_REDIRECT_URI'] ?? $_SERVER['DISCORD_REDIRECT_URI'] ?? '';
        
        error_log("callback.php: Found env vars - ClientID: " . (!empty($clientId) ? 'SET' : 'MISSING'));
        
        return [
            'DISCORD_CLIENT_ID' => $clientId,
            'DISCORD_CLIENT_SECRET' => $clientSecret,
            'DISCORD_REDIRECT_URI' => $redirectUri,
        ];
    }
    
    // Fallback to local config file
    $envFile = __DIR__ . '/../config/env.txt';
    error_log("callback.php: Checking local file: $envFile");
    
    if (is_file($envFile)) {
        $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        return is_array($vars) ? $vars : [];
    }
    
    error_log("callback.php: No env vars found!");
    return [];
}

$env = parse_env();

// Validate we have the required OAuth code
if (!isset($_GET['code'])) {
    error_log("callback.php: ERROR - No code parameter received");
    die('<h1>Error: No Authorization Code</h1><p>No authorization code was received from Discord.</p><p><a href="login.php">Try logging in again</a></p>');
}

$code = $_GET['code'];
error_log("callback.php: Received auth code: " . substr($code, 0, 10) . "...");

// Validate environment variables
if (empty($env['DISCORD_CLIENT_ID']) || empty($env['DISCORD_CLIENT_SECRET']) || empty($env['DISCORD_REDIRECT_URI'])) {
    error_log("callback.php: ERROR - Missing environment variables");
    die('<h1>Configuration Error</h1><p>Discord OAuth credentials are not configured properly.</p><p>Missing: ' . 
        (empty($env['DISCORD_CLIENT_ID']) ? 'CLIENT_ID ' : '') .
        (empty($env['DISCORD_CLIENT_SECRET']) ? 'CLIENT_SECRET ' : '') .
        (empty($env['DISCORD_REDIRECT_URI']) ? 'REDIRECT_URI' : '') . 
        '</p>');
}

error_log("callback.php: Environment variables OK");

// Exchange code for access token
$tokenUrl = 'https://discord.com/api/oauth2/token';
$tokenData = [
    'client_id' => $env['DISCORD_CLIENT_ID'],
    'client_secret' => $env['DISCORD_CLIENT_SECRET'],
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $env['DISCORD_REDIRECT_URI']
];

error_log("callback.php: Exchanging code for token...");

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("callback.php: Token request HTTP $httpCode");

if ($httpCode !== 200) {
    error_log("callback.php: ERROR - Token request failed: $response");
    die('<h1>Discord API Error</h1><p>Failed to get access token from Discord.</p><p>HTTP Code: ' . $httpCode . '</p><p>Response: ' . htmlspecialchars($response) . '</p><p><a href="login.php">Try again</a></p>');
}

$tokenInfo = json_decode($response, true);

if (!isset($tokenInfo['access_token'])) {
    error_log("callback.php: ERROR - No access token in response: $response");
    die('<h1>Invalid Token Response</h1><p>Discord did not return an access token.</p><p><a href="login.php">Try again</a></p>');
}

error_log("callback.php: Got access token, fetching user data...");

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

error_log("callback.php: User request HTTP $httpCode");

if ($httpCode !== 200) {
    error_log("callback.php: ERROR - User request failed: $userResponse");
    die('<h1>Discord API Error</h1><p>Failed to get user data from Discord.</p><p>HTTP Code: ' . $httpCode . '</p><p><a href="login.php">Try again</a></p>');
}

$userData = json_decode($userResponse, true);

if (!isset($userData['id'])) {
    error_log("callback.php: ERROR - No user ID in response: $userResponse");
    die('<h1>Invalid User Data</h1><p>Discord did not return valid user data.</p><p><a href="login.php">Try again</a></p>');
}

error_log("callback.php: SUCCESS - User " . $userData['username'] . " logged in");

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

error_log("callback.php: Redirecting to dashboard.php");

// Redirect to dashboard
header('Location: dashboard.php');
exit;
