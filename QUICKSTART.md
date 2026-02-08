# 🚀 Quick Start Guide

This guide will help you get the upgraded Refresh Tool running in minutes!

## Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Discord Developer Application

## 🎯 Quick Setup (3 Steps)

### Step 1: Run the Setup Script

```bash
./setup.sh
```

This will:
- Create your `.env` configuration file
- Set up the MySQL database
- Import the schema
- Optionally migrate existing data

### Step 2: Configure Discord OAuth

Edit `config/.env` and add your Discord application credentials:

```env
DISCORD_CLIENT_ID=your_client_id_here
DISCORD_CLIENT_SECRET=your_client_secret_here
DISCORD_REDIRECT_URI=http://your-domain.com/callback.php
```

**To get Discord credentials:**
1. Go to https://discord.com/developers/applications
2. Click "New Application"
3. Go to "OAuth2" → "General"
4. Copy your Client ID and Client Secret
5. Add redirect URI: `http://your-domain.com/callback.php`

### Step 3: Configure Web Server

Point your web server document root to the `public/` directory.

**Apache (.htaccess already included):**
```apache
DocumentRoot /path/to/Refresh-Tool/public
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/Refresh-Tool/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## ✅ Verify Installation

1. Visit: `http://your-domain.com`
2. You should see the login page
3. Log in with Discord
4. Test cookie refresh functionality
5. Check analytics at: `http://your-domain.com/analytics.php`

## 🎨 What's New?

### Analytics Dashboard
Visit `/analytics.php` to see:
- 📈 Real-time refresh statistics
- 📊 4 interactive charts (Chart.js)
- 🏆 User leaderboard (anonymized)
- 📥 Export data as JSON/CSV
- 🔄 Auto-refresh every 30 seconds

### Enhanced UI
- ✨ Animated loading with progress indicators
- ✅ Real-time cookie validation
- 📋 Improved copy to clipboard with toast notifications
- 🎯 Modern favicon
- 🎭 Ripple button effects

### Database Features
- 💾 All data stored in MySQL
- 📊 Comprehensive refresh history
- 👥 User tracking and statistics
- 🚫 Advanced rate limiting with IP banning
- ⚡ Background job queue system

## 🔧 Optional: Queue Worker

For async job processing, run the queue worker:

```bash
php public/api/queue_worker.php
```

Or set up as a systemd service (create `/etc/systemd/system/refresh-queue.service`):

```ini
[Unit]
Description=Refresh Tool Queue Worker
After=mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/Refresh-Tool
ExecStart=/usr/bin/php /path/to/Refresh-Tool/public/api/queue_worker.php
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable refresh-queue
sudo systemctl start refresh-queue
```

## 🐛 Troubleshooting

### Database Connection Issues

1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify credentials in `config/.env`
3. Check logs: `tail -f logs/database_errors.log`

### Permission Issues

```bash
# Set proper permissions
chmod -R 755 /path/to/Refresh-Tool
chmod -R 775 /path/to/Refresh-Tool/logs
chmod -R 775 /path/to/Refresh-Tool/storage
chown -R www-data:www-data /path/to/Refresh-Tool
```

### Analytics Not Loading

1. Check browser console for JavaScript errors
2. Verify `/api/stats.php` is accessible
3. Check database connection

### Rate Limiting Too Strict

Edit `public/api/rate_limit.php`:
```php
checkRateLimit(10, 60); // 10 requests per 60 seconds
```

## 📚 Additional Resources

- **Full Documentation**: See `README.md`
- **Database Schema**: See `config/schema.sql`
- **Migration Guide**: See `config/migrate.php`
- **API Docs**: See README.md "API Endpoints" section

## 🎉 You're All Set!

Your Refresh Tool is now upgraded with:
- ✅ Database integration
- ✅ Analytics dashboard
- ✅ Enhanced UI
- ✅ Improved security
- ✅ Better performance

Enjoy! 🚀

---

**Need Help?** Open an issue on GitHub: https://github.com/RblxIsAwesome/Refresh-Tool/issues
