<?php
/**
 * Database Migration Script
 * 
 * Migrates data from JSON file-based storage to MySQL database.
 * Run this script once after creating the database schema.
 * 
 * Usage: php migrate.php
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

// Define access allowed for config
define('ACCESS_ALLOWED', true);

require_once __DIR__ . '/database.php';

class Migrator
{
    private PDO $db;
    private string $storageDir;
    private array $stats = [
        'users' => 0,
        'rate_limits' => 0,
        'refresh_history' => 0,
        'queue_jobs' => 0,
        'errors' => []
    ];
    
    public function __construct()
    {
        $this->storageDir = __DIR__ . '/../storage';
        
        try {
            $this->db = Database::getConnection();
            echo "✓ Database connection established\n";
        } catch (PDOException $e) {
            die("✗ Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * Run all migrations
     */
    public function migrate(): void
    {
        echo "\n========================================\n";
        echo "Starting Migration Process\n";
        echo "========================================\n\n";
        
        $this->migrateRateLimits();
        $this->migrateRefreshStats();
        $this->migrateQueue();
        $this->migrateIPBans();
        
        $this->printSummary();
    }
    
    /**
     * Migrate rate limits from rate_limits.json
     */
    private function migrateRateLimits(): void
    {
        echo "Migrating rate limits...\n";
        
        $file = $this->storageDir . '/rate_limits.json';
        if (!file_exists($file)) {
            echo "  ⚠ rate_limits.json not found, skipping\n";
            return;
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !is_array($data)) {
            echo "  ⚠ No rate limit data to migrate\n";
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (ip_address, request_count, first_request, last_request)
            VALUES (?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))
            ON DUPLICATE KEY UPDATE 
                request_count = VALUES(request_count),
                last_request = VALUES(last_request)
        ");
        
        foreach ($data as $ip => $info) {
            try {
                $stmt->execute([
                    $ip,
                    $info['count'] ?? 1,
                    $info['first_request'] ?? time(),
                    time()
                ]);
                $this->stats['rate_limits']++;
            } catch (PDOException $e) {
                $this->stats['errors'][] = "Rate limit migration error for IP $ip: " . $e->getMessage();
            }
        }
        
        echo "  ✓ Migrated {$this->stats['rate_limits']} rate limit entries\n";
    }
    
    /**
     * Migrate refresh statistics from refresh_stats.json
     */
    private function migrateRefreshStats(): void
    {
        echo "Migrating refresh statistics...\n";
        
        $file = $this->storageDir . '/refresh_stats.json';
        if (!file_exists($file)) {
            echo "  ⚠ refresh_stats.json not found, skipping\n";
            return;
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !is_array($data)) {
            echo "  ⚠ No refresh stats to migrate\n";
            return;
        }
        
        // Migrate aggregate statistics to analytics table
        $stmt = $this->db->prepare("
            INSERT INTO analytics (metric_type, metric_key, metric_value, period_start)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                metric_value = VALUES(metric_value),
                updated_at = NOW()
        ");
        
        if (isset($data['total'])) {
            $stmt->execute(['total', 'refreshes', $data['total']]);
        }
        if (isset($data['success'])) {
            $stmt->execute(['total', 'successful_refreshes', $data['success']]);
        }
        if (isset($data['failed'])) {
            $stmt->execute(['total', 'failed_refreshes', $data['failed']]);
        }
        
        // Migrate recent refresh history if available
        if (isset($data['recent']) && is_array($data['recent'])) {
            $stmt = $this->db->prepare("
                INSERT INTO refresh_history 
                (ip_address, cookie_hash, status, error_message, response_time, created_at)
                VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?))
            ");
            
            foreach ($data['recent'] as $entry) {
                try {
                    $stmt->execute([
                        $entry['ip'] ?? 'Unknown',
                        hash('sha256', $entry['cookie'] ?? ''),
                        $entry['status'] ?? 'unknown',
                        $entry['error'] ?? null,
                        $entry['response_time'] ?? null,
                        $entry['timestamp'] ?? time()
                    ]);
                    $this->stats['refresh_history']++;
                } catch (PDOException $e) {
                    $this->stats['errors'][] = "Refresh history migration error: " . $e->getMessage();
                }
            }
        }
        
        echo "  ✓ Migrated refresh statistics\n";
    }
    
    /**
     * Migrate queue from queue.json
     */
    private function migrateQueue(): void
    {
        echo "Migrating queue jobs...\n";
        
        $file = $this->storageDir . '/queue.json';
        if (!file_exists($file)) {
            echo "  ⚠ queue.json not found, skipping\n";
            return;
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !is_array($data)) {
            echo "  ⚠ No queue data to migrate\n";
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO queue_jobs 
            (ip_address, cookie_hash, cookie_encrypted, status, priority, attempts, created_at)
            VALUES (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))
        ");
        
        foreach ($data as $job) {
            try {
                $stmt->execute([
                    $job['ip'] ?? 'Unknown',
                    hash('sha256', $job['cookie'] ?? ''),
                    base64_encode($job['cookie'] ?? ''), // Simple encoding (not secure)
                    $job['status'] ?? 'pending',
                    $job['priority'] ?? 5,
                    $job['attempts'] ?? 0,
                    $job['created_at'] ?? time()
                ]);
                $this->stats['queue_jobs']++;
            } catch (PDOException $e) {
                $this->stats['errors'][] = "Queue migration error: " . $e->getMessage();
            }
        }
        
        echo "  ✓ Migrated {$this->stats['queue_jobs']} queue jobs\n";
    }
    
    /**
     * Migrate IP bans from ip_bans.json
     */
    private function migrateIPBans(): void
    {
        echo "Migrating IP bans...\n";
        
        $file = $this->storageDir . '/ip_bans.json';
        if (!file_exists($file)) {
            echo "  ⚠ ip_bans.json not found, skipping\n";
            return;
        }
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !is_array($data)) {
            echo "  ⚠ No IP bans to migrate\n";
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits 
            (ip_address, request_count, first_request, is_banned, ban_expiry, ban_reason)
            VALUES (?, 999, FROM_UNIXTIME(?), TRUE, FROM_UNIXTIME(?), ?)
            ON DUPLICATE KEY UPDATE 
                is_banned = TRUE,
                ban_expiry = VALUES(ban_expiry),
                ban_reason = VALUES(ban_reason)
        ");
        
        $banCount = 0;
        foreach ($data as $ip => $banInfo) {
            try {
                // Only migrate active bans
                if (isset($banInfo['expiry']) && $banInfo['expiry'] > time()) {
                    $stmt->execute([
                        $ip,
                        $banInfo['banned_at'] ?? time(),
                        $banInfo['expiry'],
                        $banInfo['reason'] ?? 'Rate limit exceeded'
                    ]);
                    $banCount++;
                }
            } catch (PDOException $e) {
                $this->stats['errors'][] = "IP ban migration error for $ip: " . $e->getMessage();
            }
        }
        
        echo "  ✓ Migrated $banCount active IP bans\n";
    }
    
    /**
     * Print migration summary
     */
    private function printSummary(): void
    {
        echo "\n========================================\n";
        echo "Migration Summary\n";
        echo "========================================\n";
        echo "Rate Limits: {$this->stats['rate_limits']}\n";
        echo "Refresh History: {$this->stats['refresh_history']}\n";
        echo "Queue Jobs: {$this->stats['queue_jobs']}\n";
        
        if (!empty($this->stats['errors'])) {
            echo "\n⚠ Errors encountered:\n";
            foreach ($this->stats['errors'] as $error) {
                echo "  - $error\n";
            }
        } else {
            echo "\n✓ Migration completed successfully!\n";
        }
        
        echo "\nNext steps:\n";
        echo "1. Verify data in database\n";
        echo "2. Test application functionality\n";
        echo "3. Backup JSON files if needed\n";
        echo "4. Remove or archive JSON files\n";
        echo "========================================\n";
    }
    
    /**
     * Create backup of JSON files
     */
    public function createBackup(): bool
    {
        $backupDir = $this->storageDir . '/backup_' . date('Y-m-d_H-i-s');
        
        if (!mkdir($backupDir, 0755, true)) {
            echo "✗ Failed to create backup directory\n";
            return false;
        }
        
        $files = glob($this->storageDir . '/*.json');
        foreach ($files as $file) {
            $filename = basename($file);
            if (!copy($file, $backupDir . '/' . $filename)) {
                echo "✗ Failed to backup $filename\n";
                return false;
            }
        }
        
        echo "✓ Backup created at: $backupDir\n";
        return true;
    }
}

// ========================================
// Main Execution
// ========================================

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║  Roblox Refresher Database Migration  ║\n";
echo "║              Version 1.0.0             ║\n";
echo "╚════════════════════════════════════════╝\n";

// Check if database is available
if (!Database::isAvailable()) {
    die("\n✗ Cannot connect to database. Please check your configuration.\n" .
        "  1. Ensure MySQL is running\n" .
        "  2. Database 'refresh_tool' exists\n" .
        "  3. Credentials in config/.env are correct\n\n");
}

// Confirm migration
echo "\nThis will migrate data from JSON files to the database.\n";
echo "It's recommended to backup your JSON files first.\n\n";
echo "Do you want to create a backup? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

$migrator = new Migrator();

if (strtolower($line) === 'y') {
    $migrator->createBackup();
    echo "\n";
}

echo "Proceed with migration? (y/n): ";
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'y') {
    die("Migration cancelled.\n");
}

// Run migration
$migrator->migrate();

echo "\n";
