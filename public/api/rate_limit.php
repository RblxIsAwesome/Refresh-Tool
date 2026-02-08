<?php
/**
 * Rate Limiting & IP Ban System
 * 
 * Prevents abuse by limiting requests per IP and banning malicious IPs.
 * 
 * @package RobloxRefresher
 * @author  Your Name
 * @version 1.0.0
 */

// Storage file paths
define('RATE_LIMIT_FILE', __DIR__ . '/../storage/rate_limits.json');
define('IP_BAN_FILE', __DIR__ . '/../storage/ip_bans.json');

/**
 * Get user's real IP address
 * 
 * @return string IP address
 */
function getUserIP() {
    // Check for Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    // Check for proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    
    // Direct connection
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

/**
 * Check if IP is banned
 * 
 * @throws Exception if IP is banned
 */
function checkIPBan() {
    $ip = getUserIP();
    
    if (!file_exists(IP_BAN_FILE)) {
        return;
    }
    
    $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    
    if (isset($bans[$ip])) {
        $ban = $bans[$ip];
        
        // Check if ban has expired
        if (isset($ban['expires']) && $ban['expires'] > time()) {
            $expiresIn = round(($ban['expires'] - time()) / 60);
            throw new Exception("Your IP has been temporarily banned. Try again in {$expiresIn} minutes.");
        } elseif (!isset($ban['expires'])) {
            // Permanent ban
            throw new Exception('Your IP has been permanently banned for abuse.');
        } else {
            // Ban expired, remove it
            unset($bans[$ip]);
            file_put_contents(IP_BAN_FILE, json_encode($bans, JSON_PRETTY_PRINT));
        }
    }
}

/**
 * Check rate limit for current IP
 * 
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 * @throws Exception if rate limit exceeded
 */
function checkRateLimit($maxRequests = 3, $timeWindow = 60) {
    $ip = getUserIP();
    $currentTime = time();
    
    // Load existing rate limits
    $rateLimits = [];
    if (file_exists(RATE_LIMIT_FILE)) {
        $rateLimits = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];
    }
    
    // Clean up old entries
    $rateLimits = array_filter($rateLimits, function($data) use ($currentTime, $timeWindow) {
        return ($currentTime - ($data['first_request'] ?? 0)) < $timeWindow;
    });
    
    // Initialize or update IP record
    if (!isset($rateLimits[$ip])) {
        $rateLimits[$ip] = [
            'count' => 1,
            'first_request' => $currentTime,
            'last_request' => $currentTime
        ];
    } else {
        $rateLimits[$ip]['count']++;
        $rateLimits[$ip]['last_request'] = $currentTime;
        
        // Check if limit exceeded
        if ($rateLimits[$ip]['count'] > $maxRequests) {
            $elapsed = $currentTime - $rateLimits[$ip]['first_request'];
            
            if ($elapsed < $timeWindow) {
                $waitTime = $timeWindow - $elapsed;
                
                // Ban IP if too many violations
                if ($rateLimits[$ip]['count'] > ($maxRequests * 3)) {
                    banIP($ip, 3600); // Ban for 1 hour
                    throw new Exception('Rate limit exceeded multiple times. Your IP has been temporarily banned.');
                }
                
                throw new Exception("Rate limit exceeded. Please wait {$waitTime} seconds before trying again.");
            }
            
            // Reset counter after time window
            $rateLimits[$ip] = [
                'count' => 1,
                'first_request' => $currentTime,
                'last_request' => $currentTime
            ];
        }
    }
    
    // Save updated rate limits
    file_put_contents(RATE_LIMIT_FILE, json_encode($rateLimits, JSON_PRETTY_PRINT));
}

/**
 * Ban an IP address
 * 
 * @param string $ip IP address to ban
 * @param int|null $duration Ban duration in seconds (null for permanent)
 */
function banIP($ip, $duration = null) {
    $bans = [];
    if (file_exists(IP_BAN_FILE)) {
        $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    }
    
    $bans[$ip] = [
        'banned_at' => time(),
        'expires' => $duration ? (time() + $duration) : null,
        'reason' => 'Rate limit abuse'
    ];
    
    file_put_contents(IP_BAN_FILE, json_encode($bans, JSON_PRETTY_PRINT));
    
    error_log("Banned IP: {$ip} for " . ($duration ? "{$duration}s" : "permanently"));
}
