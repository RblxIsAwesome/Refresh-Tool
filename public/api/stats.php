<?php
/**
 * Statistics API Endpoint
 * 
 * Returns public statistics about refresh operations
 * Used by analytics dashboard
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('X-Robots-Tag: noindex, nofollow');

require_once __DIR__ . '/../../config/database.php';

try {
    $stats = [];
    
    // Try to get stats from database
    if (Database::isAvailable()) {
        $stats = getStatsFromDatabase();
    } else {
        $stats = getStatsFromFile();
    }
    
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve statistics']);
    error_log("Stats API error: " . $e->getMessage());
}

/**
 * Get statistics from database
 */
function getStatsFromDatabase(): array
{
    $stats = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'success_rate' => 0,
        'today' => ['total' => 0, 'success' => 0, 'failed' => 0],
        'week' => ['total' => 0, 'success' => 0, 'failed' => 0],
        'month' => ['total' => 0, 'success' => 0, 'failed' => 0],
        'avg_response_time' => 0,
        'active_users' => 0,
        'hourly' => [],
        'daily' => [],
        'top_errors' => [],
        'recent' => []
    ];
    
    // Get total counts
    $stmt = Database::execute("SELECT COUNT(*) as total FROM refresh_history");
    $result = $stmt->fetch();
    $stats['total'] = (int)($result['total'] ?? 0);
    
    // Get success/failed counts
    $stmt = Database::execute("SELECT status, COUNT(*) as count FROM refresh_history GROUP BY status");
    while ($row = $stmt->fetch()) {
        if ($row['status'] === 'success') {
            $stats['success'] = (int)$row['count'];
        } elseif ($row['status'] === 'failed') {
            $stats['failed'] = (int)$row['count'];
        }
    }
    
    // Calculate success rate
    if ($stats['total'] > 0) {
        $stats['success_rate'] = round(($stats['success'] / $stats['total']) * 100, 2);
    }
    
    // Today's stats
    $stmt = Database::execute(
        "SELECT status, COUNT(*) as count 
         FROM refresh_history 
         WHERE DATE(created_at) = CURDATE() 
         GROUP BY status"
    );
    while ($row = $stmt->fetch()) {
        $stats['today']['total'] += (int)$row['count'];
        if ($row['status'] === 'success') {
            $stats['today']['success'] = (int)$row['count'];
        } else {
            $stats['today']['failed'] = (int)$row['count'];
        }
    }
    
    // This week's stats
    $stmt = Database::execute(
        "SELECT status, COUNT(*) as count 
         FROM refresh_history 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
         GROUP BY status"
    );
    while ($row = $stmt->fetch()) {
        $stats['week']['total'] += (int)$row['count'];
        if ($row['status'] === 'success') {
            $stats['week']['success'] = (int)$row['count'];
        } else {
            $stats['week']['failed'] = (int)$row['count'];
        }
    }
    
    // This month's stats
    $stmt = Database::execute(
        "SELECT status, COUNT(*) as count 
         FROM refresh_history 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
         GROUP BY status"
    );
    while ($row = $stmt->fetch()) {
        $stats['month']['total'] += (int)$row['count'];
        if ($row['status'] === 'success') {
            $stats['month']['success'] = (int)$row['count'];
        } else {
            $stats['month']['failed'] = (int)$row['count'];
        }
    }
    
    // Average response time
    $stmt = Database::execute("SELECT AVG(response_time) as avg_time FROM refresh_history WHERE response_time IS NOT NULL");
    $result = $stmt->fetch();
    $stats['avg_response_time'] = round((float)($result['avg_time'] ?? 0), 2);
    
    // Active users (last 24 hours)
    $stmt = Database::execute(
        "SELECT COUNT(DISTINCT user_id) as count 
         FROM refresh_history 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
         AND user_id IS NOT NULL"
    );
    $result = $stmt->fetch();
    $stats['active_users'] = (int)($result['count'] ?? 0);
    
    // Hourly stats (last 24 hours)
    $stmt = Database::execute(
        "SELECT 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
         FROM refresh_history
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY hour
         ORDER BY hour ASC"
    );
    while ($row = $stmt->fetch()) {
        $stats['hourly'][$row['hour']] = [
            'total' => (int)$row['total'],
            'success' => (int)$row['success'],
            'failed' => (int)$row['failed']
        ];
    }
    
    // Daily stats (last 7 days)
    $stmt = Database::execute(
        "SELECT 
            DATE(created_at) as day,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
         FROM refresh_history
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY day
         ORDER BY day ASC"
    );
    while ($row = $stmt->fetch()) {
        $stats['daily'][$row['day']] = [
            'total' => (int)$row['total'],
            'success' => (int)$row['success'],
            'failed' => (int)$row['failed']
        ];
    }
    
    // Top error types
    $stmt = Database::execute(
        "SELECT error_type, COUNT(*) as count 
         FROM refresh_history 
         WHERE error_type IS NOT NULL 
         GROUP BY error_type 
         ORDER BY count DESC 
         LIMIT 10"
    );
    while ($row = $stmt->fetch()) {
        $stats['top_errors'][$row['error_type']] = (int)$row['count'];
    }
    
    // Recent activity (last 10)
    $stmt = Database::execute(
        "SELECT status, error_message, UNIX_TIMESTAMP(created_at) as timestamp 
         FROM refresh_history 
         ORDER BY created_at DESC 
         LIMIT 10"
    );
    while ($row = $stmt->fetch()) {
        $stats['recent'][] = [
            'success' => $row['status'] === 'success',
            'error' => $row['error_message'],
            'timestamp' => (int)$row['timestamp']
        ];
    }
    
    // User leaderboard (anonymized)
    $stmt = Database::execute(
        "SELECT 
            user_id,
            total_refreshes,
            successful_refreshes,
            failed_refreshes,
            ROUND((successful_refreshes / NULLIF(total_refreshes, 0)) * 100, 2) as success_rate
         FROM users
         WHERE total_refreshes > 0
         ORDER BY total_refreshes DESC
         LIMIT 10"
    );
    $leaderboard = [];
    $position = 1;
    while ($row = $stmt->fetch()) {
        $leaderboard[] = [
            'position' => $position++,
            'user' => 'User #' . substr($row['user_id'], -4), // Anonymized
            'total' => (int)$row['total_refreshes'],
            'success' => (int)$row['successful_refreshes'],
            'failed' => (int)$row['failed_refreshes'],
            'success_rate' => (float)($row['success_rate'] ?? 0)
        ];
    }
    $stats['leaderboard'] = $leaderboard;
    
    return $stats;
}

/**
 * Get statistics from file (fallback)
 */
function getStatsFromFile(): array
{
    $logFile = sys_get_temp_dir() . '/refresh_stats.json';
    
    if (!file_exists($logFile)) {
        return [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'success_rate' => 0,
            'today' => ['total' => 0, 'success' => 0, 'failed' => 0],
            'week' => ['total' => 0, 'success' => 0, 'failed' => 0],
            'month' => ['total' => 0, 'success' => 0, 'failed' => 0],
            'avg_response_time' => 0,
            'active_users' => 0,
            'hourly' => [],
            'daily' => [],
            'top_errors' => [],
            'recent' => [],
            'leaderboard' => []
        ];
    }
    
    $stats = json_decode(file_get_contents($logFile), true) ?: [];
    
    // Add calculated fields
    if (isset($stats['total']) && $stats['total'] > 0 && isset($stats['success'])) {
        $stats['success_rate'] = round(($stats['success'] / $stats['total']) * 100, 2);
    } else {
        $stats['success_rate'] = 0;
    }
    
    // Add empty arrays for missing fields
    $stats['leaderboard'] = $stats['leaderboard'] ?? [];
    $stats['top_errors'] = $stats['errors'] ?? [];
    
    return $stats;
}
?>
