#!/bin/bash

# Script to test email notifications for OGT-API
# Usage: ./test_notification.sh <token> <type>

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Make sure we're in the right directory
cd "$(dirname "$0")"

# Check if curl is installed
if ! command -v curl &> /dev/null; then
    echo -e "${RED}Error: curl is not installed. Please install it to use this script.${NC}"
    exit 1
fi

# Check arguments
if [ $# -lt 2 ]; then
    echo -e "${YELLOW}Usage: $0 <token> <type>${NC}"
    echo "  token - The status token of the entry request"
    echo "  type  - The notification type: 'status' or 'published'"
    echo ""
    echo "Example:"
    echo "  $0 abc123def456 published"
    exit 1
fi

TOKEN=$1
TYPE=$2

# Validate type
if [ "$TYPE" != "status" ] && [ "$TYPE" != "published" ]; then
    echo -e "${RED}Error: Type must be 'status' or 'published'${NC}"
    exit 1
fi

# Get the app URL from .env
APP_URL=$(grep "^APP_URL=" ../.env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

# Default to localhost if not found
if [ -z "$APP_URL" ]; then
    APP_URL="http://localhost:8000"
fi

API_URL="${APP_URL}/api/eintrag-status/send-manual-notification"

echo -e "${YELLOW}Sending manual $TYPE notification for token: $TOKEN${NC}"
echo "API URL: $API_URL"

# Make the API request
RESPONSE=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"token\":\"$TOKEN\",\"type\":\"$TYPE\"}" \
    "$API_URL")

# Check if the request was successful
if echo "$RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}Notification sent successfully!${NC}"
    echo "$RESPONSE" | sed 's/,/,\n/g' | sed 's/{/{\n/g' | sed 's/}/\n}/g'
else
    echo -e "${RED}Failed to send notification:${NC}"
    echo "$RESPONSE" | sed 's/,/,\n/g' | sed 's/{/{\n/g' | sed 's/}/\n}/g'
    exit 1
fi

echo ""
echo -e "${YELLOW}Check MailHog at http://localhost:8025 to see the sent email${NC}"
