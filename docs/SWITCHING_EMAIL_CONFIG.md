# Switching Between Test and Production Email Configuration

This guide explains how to switch between MailHog (for testing) and a real SMTP server (for production) in the OGT-API application.

## Overview

The OGT-API application can be configured to use either:

1. **MailHog** - A development tool that captures emails for testing without sending them to real recipients
2. **Real SMTP Server** - A production email service that delivers emails to actual recipients

## Using the Configuration Switcher Script

A helper script is provided to easily switch between these configurations:

```bash
cd scripts
./switch_mail_config.sh [mailhog|real]
```

### Switch to MailHog (Testing)

```bash
./switch_mail_config.sh mailhog
```

This will:
- Update your `.env` file with MailHog configuration
- Configure the application to send all emails to the MailHog service
- Allow you to view captured emails at http://localhost:8025

### Switch to Real SMTP (Production)

```bash
./switch_mail_config.sh real
```

This will:
- Prompt you for your SMTP server details
- Update your `.env` file with the provided configuration
- Configure the application to send emails through your real SMTP server

### View Current Configuration

```bash
./switch_mail_config.sh
```

Running the script without parameters will show the current mail configuration.

## Manual Configuration

If you prefer to configure the email settings manually, edit your `.env` file:

### For MailHog Testing

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

### For Production SMTP

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

## Applying Changes

After changing the email configuration (either using the script or manually), you need to restart the application container:

```bash
docker compose restart app
```

## Testing the Configuration

### MailHog Testing

1. Switch to MailHog configuration
2. Restart the application container
3. Trigger an email notification (create an entity request, etc.)
4. Check the MailHog web interface at http://localhost:8025

### Production SMTP Testing

1. Switch to real SMTP configuration
2. Restart the application container
3. Trigger an email notification
4. Check the recipient's email inbox

## Security Considerations

- Never commit SMTP credentials to version control
- Use environment variables for sensitive information
- Consider using encrypted SMTP connections (TLS/SSL)
- Regularly rotate SMTP passwords

## Troubleshooting

If emails are not being sent:

1. Check the Laravel logs:
   ```bash
   docker compose exec app tail -f storage/logs/laravel.log
   ```

2. Verify the mail configuration in `.env`:
   ```bash
   docker compose exec app cat .env | grep MAIL_
   ```

3. For MailHog, ensure the container is running:
   ```bash
   docker compose ps
   ```

4. For real SMTP, verify your credentials and server settings