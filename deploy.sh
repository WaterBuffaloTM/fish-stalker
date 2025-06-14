#!/bin/bash

# Configuration
FTP_HOST="fishstalkerai.com"
FTP_USER="u523883027_fish_stalker_d"
FTP_PASS="CapBuff1999!"
REMOTE_DIR="/public_html"
LOCAL_DIR="."

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "🚀 Starting deployment..."

# Check if there are any changes to commit
if git diff --quiet && git diff --cached --quiet; then
    echo "📝 No changes to commit"
else
    echo "📝 Committing changes..."
    git add .
    git commit -m "Deploy: $(date +'%Y-%m-%d %H:%M:%S')"
    git push
fi

# Create a temporary directory for deployment
TEMP_DIR="deploy_temp"
mkdir -p $TEMP_DIR

# Copy all files except git-related and deployment files
echo "📦 Copying files..."
cp -r css js vendor *.php *.sql .htaccess $TEMP_DIR/

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