<?php
/**
 * Rate Limiting & IP Ban System
 * 
 * Prevents abuse by limiting requests per IP address
 * Uses database storage with fallback to JSON files
 */

// Rate limit storage file (fallback)
define('RATE_LIMIT_FILE', sys_get_temp_dir() . '/rate_limits.json');
define('IP_BAN_FILE', sys_get_temp_dir() . '/ip_bans.json');

// Try to use database
require_once __DIR__ . '/../../config/database.php';

/**
 * Get user's real IP address
 * Handles Cloudflare and proxy headers
 */
if (!function_exists('getUserIP')) {
    function getUserIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}

/**
 * Check if IP is banned (database version)
 */
function checkIPBanDB() {
    $ip = getUserIP();
    
    try {
        $stmt = Database::execute(
            "SELECT is_banned, ban_expiry, ban_reason 
             FROM rate_limits 
             WHERE ip_address = ? AND is_banned = TRUE",
            [$ip]
        );
        
        $ban = $stmt->fetch();
        
        if ($ban) {
            $banExpiry = strtotime($ban['ban_expiry']);
            
            if (time() < $banExpiry) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => $ban['ban_reason'] ?? 'Your IP has been temporarily banned due to suspicious activity',
                    'ban_expires' => date('Y-m-d H:i:s', $banExpiry)
                ]);
                exit;
            } else {
                // Ban expired, remove it
                Database::execute(
                    "UPDATE rate_limits SET is_banned = FALSE, ban_expiry = NULL WHERE ip_address = ?",
                    [$ip]
                );
            }
        }
    } catch (Exception $e) {
        error_log("Database IP ban check failed: " . $e->getMessage());
        // Fallback to file-based
        return checkIPBanFile();
    }
}

/**
 * Check if IP is banned (file version - fallback)
 */
function checkIPBanFile() {
    $ip = getUserIP();
    
    if (!file_exists(IP_BAN_FILE)) {
        file_put_contents(IP_BAN_FILE, json_encode([]));
        return;
    }
    
    $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    
    if (isset($bans[$ip])) {
        $banData = $bans[$ip];
        $banExpiry = $banData['expiry'] ?? 0;
        
        if (time() < $banExpiry) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Your IP has been temporarily banned due to suspicious activity',
                'ban_expires' => date('Y-m-d H:i:s', $banExpiry)
            ]);
            exit;
        } else {
            // Ban expired, remove it
            unset($bans[$ip]);
            file_put_contents(IP_BAN_FILE, json_encode($bans));
        }
    }
}

/**
 * Check IP ban (tries database first, fallback to file)
 */
function checkIPBan() {
    if (Database::isAvailable()) {
        checkIPBanDB();
    } else {
        checkIPBanFile();
    }
}

/**
 * Check rate limit for IP (database version)
 * 
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 */
function checkRateLimitDB($maxRequests = 3, $timeWindow = 60) {
    $ip = getUserIP();
    $currentTime = time();
    
    try {
        Database::beginTransaction();
        
        // Get or create rate limit entry
        $stmt = Database::execute(
            "SELECT request_count, UNIX_TIMESTAMP(first_request) as first_request 
             FROM rate_limits 
             WHERE ip_address = ? 
             FOR UPDATE",
            [$ip]
        );
        
        $entry = $stmt->fetch();
        
        if (!$entry) {
            // Create new entry
            Database::execute(
                "INSERT INTO rate_limits (ip_address, request_count, first_request, last_request) 
                 VALUES (?, 1, FROM_UNIXTIME(?), FROM_UNIXTIME(?))",
                [$ip, $currentTime, $currentTime]
            );
            Database::commit();
            return;
        }
        
        $timeElapsed = $currentTime - $entry['first_request'];
        
        // Reset if time window passed
        if ($timeElapsed > $timeWindow) {
            Database::execute(
                "UPDATE rate_limits 
                 SET request_count = 1, first_request = FROM_UNIXTIME(?), last_request = FROM_UNIXTIME(?) 
                 WHERE ip_address = ?",
                [$currentTime, $currentTime, $ip]
            );
            Database::commit();
            return;
        }
        
        // Increment counter
        $newCount = $entry['request_count'] + 1;
        Database::execute(
            "UPDATE rate_limits 
             SET request_count = ?, last_request = FROM_UNIXTIME(?) 
             WHERE ip_address = ?",
            [$newCount, $currentTime, $ip]
        );
        
        // Check if exceeded
        if ($newCount > $maxRequests) {
            $timeLeft = $timeWindow - $timeElapsed;
            
            // Ban if too many violations
            if ($newCount > $maxRequests * 3) {
                Database::execute(
                    "UPDATE rate_limits 
                     SET is_banned = TRUE, ban_expiry = FROM_UNIXTIME(?), ban_reason = ? 
                     WHERE ip_address = ?",
                    [$currentTime + 3600, 'Rate limit exceeded', $ip]
                );
            }
            
            Database::commit();
            
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Rate limit exceeded. Please wait before trying again.',
                'retry_after' => max(1, $timeLeft)
            ]);
            exit;
        }
        
        Database::commit();
    } catch (Exception $e) {
        if (Database::getConnection()->inTransaction()) {
            Database::rollback();
        }
        error_log("Database rate limit check failed: " . $e->getMessage());
        // Fallback to file-based
        checkRateLimitFile($maxRequests, $timeWindow);
    }
}

/**
 * Check rate limit for IP (file version - fallback)
 * 
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 */
function checkRateLimitFile($maxRequests = 3, $timeWindow = 60) {
    $ip = getUserIP();
    
    if (!file_exists(RATE_LIMIT_FILE)) {
        file_put_contents(RATE_LIMIT_FILE, json_encode([]));
    }
    
    $rateLimits = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];
    
    $currentTime = time();
    
    // Clean old entries
    foreach ($rateLimits as $rip => $data) {
        if ($currentTime - $data['first_request'] > $timeWindow) {
            unset($rateLimits[$rip]);
        }
    }
    
    // Check current IP
    if (!isset($rateLimits[$ip])) {
        $rateLimits[$ip] = [
            'count' => 1,
            'first_request' => $currentTime
        ];
    } else {
        $rateLimits[$ip]['count']++;
        
        // Check if exceeded
        if ($rateLimits[$ip]['count'] > $maxRequests) {
            $timeLeft = $timeWindow - ($currentTime - $rateLimits[$ip]['first_request']);
            
            // Ban if too many violations
            if ($rateLimits[$ip]['count'] > $maxRequests * 3) {
                banIPFile($ip, 3600); // Ban for 1 hour
            }
            
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Rate limit exceeded. Please wait before trying again.',
                'retry_after' => max(1, $timeLeft)
            ]);
            exit;
        }
    }
    
    file_put_contents(RATE_LIMIT_FILE, json_encode($rateLimits));
}

/**
 * Check rate limit (tries database first, fallback to file)
 */
function checkRateLimit($maxRequests = 3, $timeWindow = 60) {
    if (Database::isAvailable()) {
        checkRateLimitDB($maxRequests, $timeWindow);
    } else {
        checkRateLimitFile($maxRequests, $timeWindow);
    }
}

/**
 * Ban an IP address (database version)
 * 
 * @param string $ip IP address to ban
 * @param int $duration Ban duration in seconds
 */
function banIPDB($ip, $duration = 3600) {
    try {
        Database::execute(
            "INSERT INTO rate_limits (ip_address, request_count, first_request, is_banned, ban_expiry, ban_reason)
             VALUES (?, 999, NOW(), TRUE, FROM_UNIXTIME(?), ?)
             ON DUPLICATE KEY UPDATE 
                 is_banned = TRUE,
                 ban_expiry = FROM_UNIXTIME(?),
                 ban_reason = VALUES(ban_reason)",
            [$ip, time() + $duration, 'Rate limit exceeded', time() + $duration]
        );
        
        error_log("IP banned: $ip for $duration seconds");
    } catch (Exception $e) {
        error_log("Failed to ban IP in database: " . $e->getMessage());
        banIPFile($ip, $duration);
    }
}

/**
 * Ban an IP address (file version - fallback)
 * 
 * @param string $ip IP address to ban
 * @param int $duration Ban duration in seconds
 */
function banIPFile($ip, $duration = 3600) {
    if (!file_exists(IP_BAN_FILE)) {
        file_put_contents(IP_BAN_FILE, json_encode([]));
    }
    
    $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    
    $bans[$ip] = [
        'banned_at' => time(),
        'expiry' => time() + $duration,
        'reason' => 'Rate limit exceeded'
    ];
    
    file_put_contents(IP_BAN_FILE, json_encode($bans));
    
    error_log("IP banned: $ip for $duration seconds");
}

/**
 * Ban an IP address (tries database first, fallback to file)
 */
function banIP($ip, $duration = 3600) {
    if (Database::isAvailable()) {
        banIPDB($ip, $duration);
    } else {
        banIPFile($ip, $duration);
    }
}
?>
