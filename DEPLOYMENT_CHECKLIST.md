# ✅ Deployment Checklist

Use this checklist to deploy your upgraded Refresh Tool.

## 📋 Pre-Deployment Checklist

- [ ] **Merge the Pull Request on GitHub**
  - Go to https://github.com/RblxIsAwesome/Refresh-Tool
  - Find the PR for branch: `copilot/add-database-integration-analytics`
  - Click "Merge pull request"
  - Confirm merge

## 🖥️ Server Setup Checklist

- [ ] **Pull the latest code**
  ```bash
  cd /path/to/your/Refresh-Tool
  git checkout main
  git pull origin main
  ```

- [ ] **Check PHP version** (need 8.1+)
  ```bash
  php -v
  ```

- [ ] **Check MySQL/MariaDB** (need 5.7+/10.3+)
  ```bash
  mysql --version
  ```

- [ ] **Run setup script**
  ```bash
  chmod +x setup.sh
  ./setup.sh
  ```

## 🔧 Configuration Checklist

- [ ] **Configure database in `.env`**
  - DB_HOST=localhost
  - DB_NAME=refresh_tool
  - DB_USER=your_user
  - DB_PASS=your_password
  - DB_PERSISTENT=true

- [ ] **Configure Discord OAuth in `.env`**
  - DISCORD_CLIENT_ID=your_id
  - DISCORD_CLIENT_SECRET=your_secret
  - DISCORD_REDIRECT_URI=http://your-domain/callback.php

- [ ] **Set file permissions**
  ```bash
  chmod -R 755 /path/to/Refresh-Tool
  chmod -R 775 /path/to/Refresh-Tool/logs
  chmod -R 775 /path/to/Refresh-Tool/storage
  chown -R www-data:www-data /path/to/Refresh-Tool
  ```

## 🌐 Web Server Configuration

- [ ] **Apache** - Configure virtual host
  ```apache
  <VirtualHost *:80>
      ServerName your-domain.com
      DocumentRoot /path/to/Refresh-Tool/public
      
      <Directory /path/to/Refresh-Tool/public>
          AllowOverride All
          Require all granted
      </Directory>
  </VirtualHost>
  ```

- [ ] **Nginx** - Configure server block
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

- [ ] **Restart web server**
  ```bash
  # Apache
  sudo systemctl restart apache2
  
  # Nginx
  sudo systemctl restart nginx
  sudo systemctl restart php8.1-fpm
  ```

## 🗄️ Database Setup

- [ ] **Create database** (if not done by setup.sh)
  ```sql
  CREATE DATABASE refresh_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

- [ ] **Import schema** (if not done by setup.sh)
  ```bash
  mysql -u root -p refresh_tool < config/schema.sql
  ```

- [ ] **Verify tables created**
  ```sql
  USE refresh_tool;
  SHOW TABLES;
  ```
  Should show: users, refresh_history, rate_limits, analytics, queue_jobs, sessions

- [ ] **Enable MySQL events** (for auto-maintenance)
  ```sql
  SET GLOBAL event_scheduler = ON;
  ```
  Add to my.cnf: `event_scheduler = ON`

## 🔐 Discord OAuth Setup

- [ ] **Create Discord Application**
  - Go to https://discord.com/developers/applications
  - Click "New Application"
  - Name it (e.g., "Refresh Tool")

- [ ] **Configure OAuth2**
  - Go to OAuth2 → General
  - Copy Client ID
  - Copy Client Secret
  - Click "Add Redirect"
  - Add: `http://your-domain.com/callback.php`
  - Save changes

- [ ] **Update .env file** with credentials

## ✨ Optional Features

- [ ] **Set up Queue Worker** (for async processing)
  ```bash
  # Test it works
  php public/api/queue_worker.php
  
  # Set up systemd service (see QUICKSTART.md)
  sudo nano /etc/systemd/system/refresh-queue.service
  sudo systemctl enable refresh-queue
  sudo systemctl start refresh-queue
  ```

- [ ] **Migrate existing data** (if upgrading)
  ```bash
  php config/migrate.php
  ```

- [ ] **Set up SSL/HTTPS** (recommended)
  ```bash
  sudo certbot --apache -d your-domain.com
  # or
  sudo certbot --nginx -d your-domain.com
  ```

## 🧪 Testing Checklist

- [ ] **Visit homepage**
  - URL: http://your-domain.com/
  - Should show login page

- [ ] **Test Discord login**
  - Click "Login with Discord"
  - Authorize application
  - Should redirect to dashboard

- [ ] **Test cookie refresh**
  - Enter a Roblox cookie
  - Click "Refresh Cookie"
  - Should see success message and user data

- [ ] **Check analytics dashboard**
  - URL: http://your-domain.com/analytics.php
  - Should display charts and statistics

- [ ] **Test API endpoint**
  - URL: http://your-domain.com/api/stats.php
  - Should return JSON with statistics

- [ ] **Check database**
  ```sql
  SELECT COUNT(*) FROM users;
  SELECT COUNT(*) FROM refresh_history;
  ```
  Should show your data

- [ ] **Test rate limiting**
  - Make multiple rapid requests
  - Should get rate limit error after 3 requests

- [ ] **Check logs**
  ```bash
  tail -f logs/database_errors.log
  ```
  Should be empty or minimal warnings

## 🎯 Final Verification

- [ ] All pages load without errors
- [ ] Discord login works correctly
- [ ] Cookie refresh functionality works
- [ ] Analytics dashboard displays data
- [ ] Charts render properly
- [ ] Database is receiving data
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors

## 🎉 Post-Deployment

- [ ] **Monitor logs** for first 24 hours
- [ ] **Check database size** periodically
- [ ] **Backup database** regularly
  ```bash
  mysqldump -u root -p refresh_tool > backup_$(date +%Y%m%d).sql
  ```
- [ ] **Update Discord redirect URI** if domain changes
- [ ] **Document your setup** for future reference

## 📊 Success Metrics

After deployment, you should see:
- ✅ Users logging in successfully
- ✅ Cookies being refreshed
- ✅ Data appearing in analytics
- ✅ Charts populating with real data
- ✅ No errors in browser console
- ✅ No errors in server logs

---

## 🆘 If Something Goes Wrong

1. **Check logs:**
   - `logs/database_errors.log`
   - `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
   - Browser console (F12)

2. **Verify config:**
   - Database credentials in `.env`
   - Discord OAuth credentials in `.env`
   - File permissions (755 for files, 775 for logs/storage)

3. **Test components:**
   - Database connection: `php -r "require 'config/database.php'; var_dump(Database::isAvailable());"`
   - PHP syntax: `php -l public/dashboard.php`

4. **Get help:**
   - Check README.md troubleshooting section
   - Check QUICKSTART.md
   - Open GitHub issue with error details

---

**Last Updated:** 2026-02-08
**Version:** 2.0.0 (Database Integration Release)
