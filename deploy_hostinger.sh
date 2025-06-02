#!/bin/bash

# Configuration
FTP_HOST="fishstalkerai.com"
FTP_USER="u523883027_fish_stalker_d"
FTP_PASS="CapBuff1999!"
REMOTE_DIR="/public_html"
LOCAL_DIR="public"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "🚀 Starting Hostinger deployment..."

# Create a temporary directory for deployment
TEMP_DIR="deploy_temp"
mkdir -p $TEMP_DIR

# Copy all files from public directory to temp directory
echo "📦 Copying files..."
cp -r public/* $TEMP_DIR/

# Copy root level files
cp .env.example $TEMP_DIR/
cp README.md $TEMP_DIR/
cp .htaccess $TEMP_DIR/

# Create necessary directories if they don't exist
mkdir -p $TEMP_DIR/vendor
mkdir -p $TEMP_DIR/js
mkdir -p $TEMP_DIR/css

# Install dependencies
echo "📥 Installing dependencies..."
cd $TEMP_DIR
composer install --no-dev --optimize-autoloader

# Create a zip file for deployment
cd ..
zip -r deploy.zip $TEMP_DIR

# Clean up
rm -rf $TEMP_DIR

echo "📤 Deployment package created: deploy.zip"
echo "Upload this file to your Hostinger public_html directory and extract it there."

# Upload files to Hostinger using lftp
echo "⬆️ Uploading files to Hostinger..."
lftp -c "
    set ssl:verify-certificate no;
    open -u $FTP_USER,$FTP_PASS ftp://$FTP_HOST;
    mirror --reverse --delete --verbose $TEMP_DIR/ $REMOTE_DIR/
"

# Check if upload was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Deployment successful!${NC}"
else
    echo -e "${RED}❌ Deployment failed!${NC}"
fi

echo "✨ Done!" 