#!/bin/bash
# Refresh Tool - Automated Setup Script
# This script helps you set up the database and configuration

set -e

echo "╔════════════════════════════════════════╗"
echo "║   Refresh Tool - Setup Script          ║"
echo "║   Database & Configuration Setup       ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f "config/.env" ]; then
    echo -e "${YELLOW}Creating .env file from template...${NC}"
    cp config/.env.example config/.env
    echo -e "${GREEN}✓ Created config/.env${NC}"
    echo -e "${YELLOW}⚠ Please edit config/.env with your credentials${NC}"
    echo ""
else
    echo -e "${GREEN}✓ config/.env already exists${NC}"
fi

# Ask for database setup
echo -e "${BLUE}Do you want to set up the MySQL database now? (y/n)${NC}"
read -r setup_db

if [ "$setup_db" = "y" ] || [ "$setup_db" = "Y" ]; then
    echo ""
    echo -e "${YELLOW}Please enter your MySQL credentials:${NC}"
    
    # Read MySQL credentials
    read -p "MySQL Host [localhost]: " db_host
    db_host=${db_host:-localhost}
    
    read -p "MySQL User [root]: " db_user
    db_user=${db_user:-root}
    
    read -sp "MySQL Password: " db_pass
    echo ""
    
    read -p "Database Name [refresh_tool]: " db_name
    db_name=${db_name:-refresh_tool}
    
    echo ""
    echo -e "${YELLOW}Creating database...${NC}"
    
    # Create database
    mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database created successfully${NC}"
        
        echo -e "${YELLOW}Importing schema...${NC}"
        mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name" < config/schema.sql
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Schema imported successfully${NC}"
            
            # Update .env file
            echo -e "${YELLOW}Updating .env file...${NC}"
            sed -i "s/DB_HOST=.*/DB_HOST=$db_host/" config/.env
            sed -i "s/DB_NAME=.*/DB_NAME=$db_name/" config/.env
            sed -i "s/DB_USER=.*/DB_USER=$db_user/" config/.env
            sed -i "s/DB_PASS=.*/DB_PASS=$db_pass/" config/.env
            
            echo -e "${GREEN}✓ Configuration updated${NC}"
        else
            echo -e "${RED}✗ Failed to import schema${NC}"
            exit 1
        fi
    else
        echo -e "${RED}✗ Failed to create database${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${BLUE}Do you want to migrate existing JSON data to database? (y/n)${NC}"
read -r migrate_data

if [ "$migrate_data" = "y" ] || [ "$migrate_data" = "Y" ]; then
    echo -e "${YELLOW}Running migration script...${NC}"
    php config/migrate.php
fi

echo ""
echo "╔════════════════════════════════════════╗"
echo "║        Setup Complete! 🎉              ║"
echo "╚════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Configure Discord OAuth in config/.env"
echo "   - DISCORD_CLIENT_ID"
echo "   - DISCORD_CLIENT_SECRET"
echo "   - DISCORD_REDIRECT_URI"
echo ""
echo "2. Point your web server to the 'public/' directory"
echo ""
echo "3. Visit your site and start using the tool!"
echo ""
echo -e "${BLUE}📊 Access Analytics:${NC} http://your-domain/analytics.php"
echo -e "${BLUE}🔄 Main Dashboard:${NC} http://your-domain/dashboard.php"
echo ""
echo -e "${GREEN}All done! Enjoy your upgraded Refresh Tool! ✨${NC}"
