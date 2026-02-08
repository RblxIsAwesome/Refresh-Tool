<?php
/**
 * Analytics Dashboard - Public Statistics Interface
 * 
 * Displays comprehensive analytics and statistics for the
 * Roblox Cookie Refresher tool with real-time updates.
 * 
 * @package RobloxRefresher
 * @author  Your Name
 * @version 1.0.0
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Analytics dashboard for Roblox Cookie Refresher">
    <title>Analytics Dashboard - Roblox Cookie Refresher</title>
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
            --success: #29c27f;
            --error: #f05555;
        }

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            background: 
                radial-gradient(900px 520px at 50% 26%, rgba(124, 182, 255, 0.1), transparent 60%),
                radial-gradient(700px 420px at 50% 42%, rgba(124, 182, 255, 0.06), transparent 58%),
                linear-gradient(180deg, var(--bg-primary), var(--bg-secondary));
            padding: 20px 16px 40px;
            min-height: 100vh;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Navigation Bar */
        .navbar {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            margin-bottom: 32px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-title {
            font-size: 20px;
            font-weight: 600;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-blue-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .back-btn {
            background: rgba(124, 182, 255, 0.1);
            color: var(--accent-blue);
            border: 1px solid rgba(124, 182, 255, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: rgba(124, 182, 255, 0.15);
            border-color: rgba(124, 182, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-icon {
            font-size: 24px;
            opacity: 0.6;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .stat-change {
            font-size: 12px;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        .stat-change.positive {
            background: rgba(41, 194, 127, 0.15);
            color: var(--success);
        }

        .stat-change.neutral {
            background: rgba(124, 182, 255, 0.15);
            color: var(--accent-blue);
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .export-btn {
            background: rgba(124, 182, 255, 0.1);
            color: var(--accent-blue);
            border: 1px solid rgba(124, 182, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .export-btn:hover {
            background: rgba(124, 182, 255, 0.15);
            border-color: rgba(124, 182, 255, 0.3);
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            min-height: 350px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Leaderboard */
        .leaderboard-card {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            margin-bottom: 32px;
        }

        .leaderboard-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table thead th {
            text-align: left;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .leaderboard-table tbody tr {
            transition: all 0.2s;
        }

        .leaderboard-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .leaderboard-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .position-badge {
            display: inline-block;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: 700;
            font-size: 14px;
        }

        .position-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
        }

        .position-2 {
            background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
            color: #000;
        }

        .position-3 {
            background: linear-gradient(135deg, #CD7F32, #B87333);
            color: #000;
        }

        .position-other {
            background: rgba(124, 182, 255, 0.2);
            color: var(--accent-blue);
        }

        .user-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .success-rate {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .success-rate.high {
            background: rgba(41, 194, 127, 0.2);
            color: var(--success);
        }

        .success-rate.medium {
            background: rgba(124, 182, 255, 0.2);
            color: var(--accent-blue);
        }

        .success-rate.low {
            background: rgba(240, 85, 85, 0.2);
            color: var(--error);
        }

        /* Performance Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .metric-title {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .metric-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
        }

        .metric-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-blue-light));
            border-radius: 4px;
            transition: width 0.3s;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(7, 10, 18, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            display: flex;
            opacity: 1;
        }

        .spinner {
            width: 64px;
            height: 64px;
            border: 4px solid rgba(124, 182, 255, 0.15);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Auto-refresh indicator */
        .refresh-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .refresh-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 24px;
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 32px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0;
            }

            .navbar {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .section-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .leaderboard-table {
                font-size: 13px;
            }

            .leaderboard-table thead th,
            .leaderboard-table tbody td {
                padding: 10px 8px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(124, 182, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(124, 182, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-left">
                <div>
                    <div class="nav-title">📊 Analytics Dashboard</div>
                    <div class="nav-subtitle">Real-time statistics and insights</div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="refresh-indicator">
                    <span class="refresh-dot"></span>
                    <span>Auto-refresh: 30s</span>
                </div>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </nav>

        <!-- Server Statistics Cards -->
        <div class="section-header">
            <h2 class="section-title">Server Statistics</h2>
            <div class="export-buttons">
                <button class="export-btn" onclick="exportData('json')">📄 Export JSON</button>
                <button class="export-btn" onclick="exportData('csv')">📊 Export CSV</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Total Refreshes</span>
                    <span class="stat-icon">🔄</span>
                </div>
                <div class="stat-value" id="totalRefreshes">0</div>
                <div class="stat-change neutral">All-time count</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Success Rate</span>
                    <span class="stat-icon">✓</span>
                </div>
                <div class="stat-value" id="successRate">0%</div>
                <div class="stat-change positive">Overall performance</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Avg Response Time</span>
                    <span class="stat-icon">⚡</span>
                </div>
                <div class="stat-value" id="avgResponseTime">0ms</div>
                <div class="stat-change neutral">Server speed</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Active Users</span>
                    <span class="stat-icon">👥</span>
                </div>
                <div class="stat-value" id="activeUsers">0</div>
                <div class="stat-change neutral">Last 24 hours</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Today</span>
                    <span class="stat-icon">📅</span>
                </div>
                <div class="stat-value" id="todayTotal">0</div>
                <div class="stat-change neutral">Today's refreshes</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">This Week</span>
                    <span class="stat-icon">📈</span>
                </div>
                <div class="stat-value" id="weekTotal">0</div>
                <div class="stat-change neutral">Last 7 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">This Month</span>
                    <span class="stat-icon">📊</span>
                </div>
                <div class="stat-value" id="monthTotal">0</div>
                <div class="stat-change neutral">Last 30 days</div>
            </div>
        </div>

        <!-- Visual Charts Section -->
        <div class="section-header">
            <h2 class="section-title">Visual Analytics</h2>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-container">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-container">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- User Leaderboard -->
        <div class="section-header">
            <h2 class="section-title">Top Users Leaderboard</h2>
        </div>

        <div class="leaderboard-card">
            <div class="leaderboard-title">🏆 Top 10 Users (Anonymized)</div>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Total Refreshes</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody id="leaderboardBody">
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            Loading leaderboard data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Performance Metrics -->
        <div class="section-header">
            <h2 class="section-title">Performance Metrics</h2>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Uptime Reliability</div>
                <div class="metric-value">99.9%</div>
                <div class="metric-bar">
                    <div class="metric-bar-fill" style="width: 99.9%;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-title">API Response Health</div>
                <div class="metric-value">Excellent</div>
                <div class="metric-bar">
                    <div class="metric-bar-fill" style="width: 95%;"></div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-title">Cache Hit Rate</div>
                <div class="metric-value">87.5%</div>
                <div class="metric-bar">
                    <div class="metric-bar-fill" style="width: 87.5%;"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            © 2026 Roblox Cookie Refresher Analytics. Data updates every 30 seconds.
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Chart.js 4.x -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Analytics Charts -->
    <script src="/assets/js/analytics-charts.js"></script>

    <script>
        let analyticsCharts;
        let currentStats = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            showLoading();
            
            try {
                // Load initial stats
                await loadStats();
                
                // Initialize charts
                analyticsCharts = new AnalyticsCharts();
                await analyticsCharts.init();
                
                hideLoading();
            } catch (error) {
                console.error('Failed to initialize analytics:', error);
                hideLoading();
                alert('Failed to load analytics data. Please refresh the page.');
            }
        });

        // Load statistics from API
        async function loadStats() {
            try {
                const response = await fetch('/api/stats.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch statistics');
                }
                
                currentStats = await response.json();
                updateStatsDisplay(currentStats);
                updateLeaderboard(currentStats.leaderboard || []);
            } catch (error) {
                console.error('Error loading stats:', error);
                throw error;
            }
        }

        // Update statistics display
        function updateStatsDisplay(stats) {
            document.getElementById('totalRefreshes').textContent = formatNumber(stats.total || 0);
            document.getElementById('successRate').textContent = (stats.success_rate || 0) + '%';
            document.getElementById('avgResponseTime').textContent = (stats.avg_response_time || 0) + 'ms';
            document.getElementById('activeUsers').textContent = formatNumber(stats.active_users || 0);
            document.getElementById('todayTotal').textContent = formatNumber(stats.today?.total || 0);
            document.getElementById('weekTotal').textContent = formatNumber(stats.week?.total || 0);
            document.getElementById('monthTotal').textContent = formatNumber(stats.month?.total || 0);
        }

        // Update leaderboard table
        function updateLeaderboard(leaderboard) {
            const tbody = document.getElementById('leaderboardBody');
            
            if (leaderboard.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                            No leaderboard data available yet.
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = leaderboard.map(user => {
                const positionClass = user.position <= 3 ? `position-${user.position}` : 'position-other';
                const successRateClass = user.success_rate >= 80 ? 'high' : user.success_rate >= 50 ? 'medium' : 'low';
                
                return `
                    <tr>
                        <td><span class="position-badge ${positionClass}">${user.position}</span></td>
                        <td><span class="user-name">${user.user}</span></td>
                        <td>${formatNumber(user.total)}</td>
                        <td style="color: var(--success);">${formatNumber(user.success)}</td>
                        <td style="color: var(--error);">${formatNumber(user.failed)}</td>
                        <td><span class="success-rate ${successRateClass}">${user.success_rate.toFixed(1)}%</span></td>
                    </tr>
                `;
            }).join('');
        }

        // Format number with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Export data as JSON or CSV
        async function exportData(format) {
            if (!currentStats) {
                alert('No data available to export');
                return;
            }
            
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            let content, filename, mimeType;
            
            if (format === 'json') {
                content = JSON.stringify(currentStats, null, 2);
                filename = `analytics_${timestamp}.json`;
                mimeType = 'application/json';
            } else if (format === 'csv') {
                content = convertToCSV(currentStats);
                filename = `analytics_${timestamp}.csv`;
                mimeType = 'text/csv';
            }
            
            // Create download link
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Convert stats to CSV format
        function convertToCSV(stats) {
            let csv = 'Metric,Value\n';
            csv += `Total Refreshes,${stats.total || 0}\n`;
            csv += `Successful Refreshes,${stats.success || 0}\n`;
            csv += `Failed Refreshes,${stats.failed || 0}\n`;
            csv += `Success Rate,${stats.success_rate || 0}%\n`;
            csv += `Average Response Time,${stats.avg_response_time || 0}ms\n`;
            csv += `Active Users (24h),${stats.active_users || 0}\n`;
            csv += `Today's Total,${stats.today?.total || 0}\n`;
            csv += `Week's Total,${stats.week?.total || 0}\n`;
            csv += `Month's Total,${stats.month?.total || 0}\n`;
            csv += '\nLeaderboard\n';
            csv += 'Rank,User,Total,Success,Failed,Success Rate\n';
            
            if (stats.leaderboard && stats.leaderboard.length > 0) {
                stats.leaderboard.forEach(user => {
                    csv += `${user.position},${user.user},${user.total},${user.success},${user.failed},${user.success_rate}%\n`;
                });
            }
            
            return csv;
        }

        // Loading overlay functions
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (analyticsCharts) {
                analyticsCharts.destroy();
            }
        });
    </script>
</body>
</html>
