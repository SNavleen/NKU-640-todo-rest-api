#!/bin/bash

# EC2 Server Setup Script for PHP TODO REST API
# This script should be run on a fresh Ubuntu EC2 instance

set -e  # Exit on error

echo "=== Starting PHP TODO API Deployment ==="

# Update system packages
echo "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install nginx
echo "Installing nginx..."
sudo apt install -y nginx

# Install PHP 8.1 and required extensions
echo "Installing PHP 8.1 and extensions..."
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.1-fpm php8.1-cli php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl

# Install Composer
echo "Installing Composer..."
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Git
echo "Installing Git..."
sudo apt install -y git

# Create application directory
echo "Creating application directory..."
sudo mkdir -p /var/www/php-todo-api
sudo chown -R ubuntu:ubuntu /var/www/php-todo-api

# Clone repository (you'll need to set this up)
echo "Repository should be cloned to /var/www/php-todo-api"
echo "Run: cd /var/www/php-todo-api && git clone <your-repo-url> ."

# Create necessary directories
echo "Creating data and logs directories..."
mkdir -p /var/www/php-todo-api/data
mkdir -p /var/www/php-todo-api/logs
chmod 755 /var/www/php-todo-api/data
chmod 755 /var/www/php-todo-api/logs

# Set permissions
echo "Setting permissions..."
sudo chown -R www-data:www-data /var/www/php-todo-api/data
sudo chown -R www-data:www-data /var/www/php-todo-api/logs

# Configure nginx
echo "Configuring nginx..."
sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -sf /var/www/php-todo-api/nginx-production.conf /etc/nginx/sites-available/php-todo-api
sudo ln -sf /etc/nginx/sites-available/php-todo-api /etc/nginx/sites-enabled/php-todo-api

# Test nginx configuration
echo "Testing nginx configuration..."
sudo nginx -t

# Restart services
echo "Restarting services..."
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# Enable services on boot
echo "Enabling services on boot..."
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm

echo "=== Setup Complete! ==="
echo "Next steps:"
echo "1. Clone your repository to /var/www/php-todo-api"
echo "2. Run 'composer install' in the project directory"
echo "3. Copy .env.example to .env and configure"
echo "4. Access your API at http://<EC2-PUBLIC-IP>/api/v1/"
