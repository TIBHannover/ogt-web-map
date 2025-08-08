#!/bin/bash

# Script to switch between MailHog and a real SMTP mail configuration
# Usage: ./switch_mail_config.sh [mailhog|real]

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Path to the .env file
ENV_FILE="../.env"

# Make sure we're in the right directory
cd "$(dirname "$0")"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}Error: .env file not found at $ENV_FILE${NC}"
    echo "Make sure you're running this script from the scripts directory"
    exit 1
fi

# Function to configure MailHog
configure_mailhog() {
    echo -e "${YELLOW}Configuring mail settings for MailHog...${NC}"

    # Create a temporary file
    TMP_FILE=$(mktemp)

    # Read the current .env file, replacing or adding mail settings
    while IFS= read -r line; do
        # Skip existing mail configuration lines
        if [[ $line =~ ^MAIL_ ]]; then
            continue
        fi
        echo "$line" >> "$TMP_FILE"
    done < "$ENV_FILE"

    # Add MailHog configuration
    echo "" >> "$TMP_FILE"
    echo "# Mail configuration (MailHog)" >> "$TMP_FILE"
    echo "MAIL_MAILER=smtp" >> "$TMP_FILE"
    echo "MAIL_HOST=mailhog" >> "$TMP_FILE"
    echo "MAIL_PORT=1025" >> "$TMP_FILE"
    echo "MAIL_USERNAME=null" >> "$TMP_FILE"
    echo "MAIL_PASSWORD=null" >> "$TMP_FILE"
    echo "MAIL_ENCRYPTION=null" >> "$TMP_FILE"
    echo "MAIL_FROM_ADDRESS=noreply@ogt-api.local" >> "$TMP_FILE"
    echo "MAIL_FROM_NAME=\"OGT Wikidata System\"" >> "$TMP_FILE"

    # Replace the original file
    mv "$TMP_FILE" "$ENV_FILE"

    echo -e "${GREEN}Mail configuration set to use MailHog.${NC}"
    echo "You can view emails at http://localhost:8025"
    echo ""
    echo -e "${YELLOW}Remember to restart the application container to apply changes:${NC}"
    echo "docker compose restart app"
}

# Function to configure real SMTP
configure_real_smtp() {
    echo -e "${YELLOW}Configuring mail settings for a real SMTP server...${NC}"

    # Ask for SMTP configuration
    read -p "SMTP Server Host: " smtp_host
    read -p "SMTP Server Port [587]: " smtp_port
    smtp_port=${smtp_port:-587}

    read -p "SMTP Username: " smtp_username
    read -p "SMTP Password: " -s smtp_password
    echo ""

    read -p "SMTP Encryption (tls/ssl/null) [tls]: " smtp_encryption
    smtp_encryption=${smtp_encryption:-tls}

    read -p "From Email Address: " from_address
    read -p "From Name [OGT Wikidata System]: " from_name
    from_name=${from_name:-"OGT Wikidata System"}

    read -p "Admin Email Address: " admin_address

    # Create a temporary file
    TMP_FILE=$(mktemp)

    # Read the current .env file, replacing or adding mail settings
    while IFS= read -r line; do
        # Skip existing mail configuration lines
        if [[ $line =~ ^MAIL_ ]]; then
            continue
        fi
        echo "$line" >> "$TMP_FILE"
    done < "$ENV_FILE"

    # Add real SMTP configuration
    echo "" >> "$TMP_FILE"
    echo "# Mail configuration (Real SMTP)" >> "$TMP_FILE"
    echo "MAIL_MAILER=smtp" >> "$TMP_FILE"
    echo "MAIL_HOST=$smtp_host" >> "$TMP_FILE"
    echo "MAIL_PORT=$smtp_port" >> "$TMP_FILE"
    echo "MAIL_USERNAME=$smtp_username" >> "$TMP_FILE"
    echo "MAIL_PASSWORD=$smtp_password" >> "$TMP_FILE"
    echo "MAIL_ENCRYPTION=$smtp_encryption" >> "$TMP_FILE"
    echo "MAIL_FROM_ADDRESS=$from_address" >> "$TMP_FILE"
    echo "MAIL_FROM_NAME=\"$from_name\"" >> "$TMP_FILE"
    echo "MAIL_ADMIN_ADDRESS=$admin_address" >> "$TMP_FILE"

    # Replace the original file
    mv "$TMP_FILE" "$ENV_FILE"

    echo -e "${GREEN}Mail configuration set to use real SMTP server.${NC}"
    echo ""
    echo -e "${YELLOW}Remember to restart the application container to apply changes:${NC}"
    echo "docker compose restart app"
}

# Function to show current config
show_current_config() {
    echo -e "${YELLOW}Current mail configuration:${NC}"
    grep "^MAIL_" "$ENV_FILE"

    if grep -q "MAIL_HOST=mailhog" "$ENV_FILE"; then
        echo -e "\n${GREEN}Currently using MailHog for email.${NC}"
        echo "You can view emails at http://localhost:8025"
    else
        echo -e "\n${GREEN}Currently using a real SMTP server for email.${NC}"
    fi
}

# Main script logic
case "$1" in
    mailhog)
        configure_mailhog
        ;;
    real)
        configure_real_smtp
        ;;
    *)
        echo -e "${YELLOW}OGT-API Mail Configuration Switcher${NC}"
        echo "Usage: $0 [mailhog|real]"
        echo ""
        echo "  mailhog   - Configure for local MailHog (testing)"
        echo "  real      - Configure for real SMTP server"
        echo ""
        show_current_config
        echo ""
        echo "After changing configuration, restart the application:"
        echo "  docker compose restart app"
        ;;
esac

exit 0
