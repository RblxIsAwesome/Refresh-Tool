<?php
/**
 * Discord OAuth Callback Handler
 * 
 * Processes Discord OAuth2 authorization code and establishes user session.
 * Implements 30-day persistent sessions with proper error handling.
 * 
 * @package RobloxRefresher
 * @author  Your Name
 * @version 1.0.0
 */

// Configure session persistence
ini_set('session.gc_maxlifetime', 2592000); // 30 days
session_set_cookie_params(2592000);
session_start();

/**
 * Load environment configuration
 * 
 * Attempts to load from Railway environment variables first,
 * then falls back to local configuration file.
 * 
 * @return array Configuration array with OAuth credentials
 */
function loadConfig() {
    // Try Railway environment variables
    if (!empty($_ENV) || !empty($_SERVER)) {
        return [
            'client_id' => $_ENV['DISCORD_CLIENT_ID'] ?? $_SERVER['DISCORD_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'] ?? $_SERVER['DISCORD_CLIENT_SECRET'] ?? '',
            'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'] ?? $_SERVER['DISCORD_REDIRECT_URI'] ?? '',
        ];
    }
    
    // Fallback to local config
    $configPath = __DIR__ . '/../config/env.txt';
    if (is_file($configPath)) {
        $vars = parse_ini_file($configPath, false, INI_SCANNER_RAW);
        return [
            'client_id' => $vars['DISCORD_CLIENT_ID'] ?? '',
            'client_secret' => $vars['DISCORD_CLIENT_SECRET'] ?? '',
            'redirect_uri' => $vars['DISCORD_REDIRECT_URI'] ?? '',
        ];
    }
    
    return [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => ''
    ];
}

/**
 * Exchange authorization code for access token
 * 
 * @param string $code Authorization code from Discord
 * @param array $config OAuth configuration
 * @return array Token information or error
 */
function exchangeCodeForToken($code, $config) {
    $tokenUrl = 'https://discord.com/api/oauth2/token';
    $params = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri']
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

/**
 * Fetch user information from Discord
 * 
 * @param string $accessToken Discord access token
 * @return array User information or error
 */
function fetchUserInfo($accessToken) {
    $userUrl = 'https://discord.com/api/users/@me';
    
    $ch = curl_init($userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Validate authorization code presence
if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}

$code = $_GET['code'];
$config = loadConfig();

// Validate configuration
if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
    die('Configuration error: OAuth credentials not properly set.');
}

// Step 1: Exchange code for token
$tokenResult = exchangeCodeForToken($code, $config);

if (!$tokenResult['success'] || !isset($tokenResult['data']['access_token'])) {
    die('Authentication failed: Unable to obtain access token.');
}

// Step 2: Fetch user information
$userResult = fetchUserInfo($tokenResult['data']['access_token']);

if (!$userResult['success'] || !isset($userResult['data']['id'])) {
    die('Authentication failed: Unable to retrieve user information.');
}

$user = $userResult['data'];

// Step 3: Create persistent session
$_SESSION['discord_user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'discriminator' => $user['discriminator'] ?? '0',
    'avatar' => $user['avatar'],
    'email' => $user['email'] ?? null,
    'authenticated_at' => time(),
    'persistent' => true
];

// Redirect to dashboard
header('Location: dashboard.php');
exit;
