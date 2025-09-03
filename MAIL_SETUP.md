# Email Notification System Setup

This document describes how to set up and use the email notification system in the OGT-API application.

## Overview

The OGT-API includes an email notification system for:

1. Notifying administrators when new entity requests are submitted
2. Sending status tracking links to users via email
3. Notifying users when their entity requests are approved and published to Wikidata

## Local Development Setup with MailHog

For local development, the application uses MailHog to capture outgoing emails for testing. MailHog provides an SMTP server and a web interface to view sent emails without actually delivering them to real recipients.

### How to Use MailHog

1. Start the Docker environment:
   ```
   docker compose -f compose.local.yml up -d
   ```

2. Access the MailHog web interface:
   ```
   http://localhost:8025
   ```

3. Any emails sent by the application will appear in this interface.

### Mail Configuration

The local Docker environment sets these mail settings automatically:

```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@ogt-api.local
MAIL_FROM_NAME=OGT-API
```

No additional configuration is needed for development.

## Production Setup

### Option 1: Using MailHog in Production (Testing)

The production environment is also configured with MailHog for testing. This is useful for:
- Testing email functionality in the production environment
- Reviewing email content before switching to a real mail service
- Preventing accidental emails to real users during final testing

The configuration is automatically set in the Docker environment:

```
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@ogt-api.local
MAIL_FROM_NAME="OGT Wikidata System"
```

Access the MailHog interface at `http://your-server:8025` to view captured emails.

### Option 2: Using a Real Mail Service

When you're ready to send real emails, update your `.env` file with appropriate values:

```
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="OGT Wikidata System"
MAIL_ADMIN_ADDRESS=admin@yourdomain.com
```

## Email Notification Types

### Admin Notifications

Administrators receive email notifications when:
- A new entity request is submitted

The email includes a link to the admin dashboard where they can review and approve the request.

### User Notifications

Users can opt-in to receive email notifications when:
- They want to receive their status tracking link via email
- Their entity request is approved and published to Wikidata

## Testing Email Functionality

To test email functionality:

1. Create a new entity request through the application
2. On the "Thank You" page, enter an email address and opt-in for notifications
3. Check the MailHog interface (in development) to see the sent email
4. As an admin, approve the request
5. Check for the publication notification email in MailHog

## Troubleshooting

If emails are not being sent:

1. Check the Laravel logs (`storage/logs/laravel.log`)
2. Verify the mail configuration in your `.env` file
3. Ensure the MailHog container is running (`docker compose ps` should show mailhog running)
4. Check the MailHog web interface (port 8025) to see if emails are being captured
5. If using a real SMTP server, check your SMTP server settings and credentials

### Switching Between MailHog and Real SMTP

We've provided a helper script to easily switch between MailHog and a real SMTP server:

```bash
cd scripts
./switch_mail_config.sh [mailhog|real]
```

Using the script:
- `./switch_mail_config.sh mailhog` - Configure for MailHog testing
- `./switch_mail_config.sh real` - Configure for a real SMTP server (interactive)
- `./switch_mail_config.sh` - Show current configuration

After switching, restart the application container:
```
docker compose restart app
```

For detailed instructions, see [SWITCHING_EMAIL_CONFIG.md](SWITCHING_EMAIL_CONFIG.md).

No changes to the Docker configuration are needed - the settings in `.env` will override the environment variables.