# Email Notification Debugging Guide

This guide helps troubleshoot issues with email notifications in the OGT-API application.

## Common Issues

### 1. Missing Notification Emails

If notification emails aren't being sent when expected, check the following:

#### Email Configuration
- Verify MailHog is running:
  ```bash
  docker compose ps
  ```
- Check MailHog web interface: http://localhost:8025

#### Data Issues
- Ensure the entry has an email address set
- Verify notify_on_publish flag is set to true
- Check that the entry status is correctly updated to "wikidata_published"

#### Log Analysis
- Check application logs for email-related messages:
  ```bash
  docker compose exec app tail -f storage/logs/laravel.log | grep -i mail
  ```
- Look for specific notification conditions in logs:
  ```bash
  docker compose exec app tail -f storage/logs/laravel.log | grep "notification conditions"
  ```

### 2. Manual Testing

Use the testing script to manually trigger notifications:

```bash
cd scripts
./test_notification.sh <token> <type>
```

Where:
- `<token>` is the status token of the entity request
- `<type>` is either "status" or "published"

Example:
```bash
./test_notification.sh abc123def456 published
```

This will attempt to send the specified notification type and show the API response.

### 3. Database Verification

Check if the notification flags are correctly set in the database:

```bash
docker compose exec app php artisan tinker
```

Then query the database:
```php
$request = \App\Models\WikibaseEntryRequest::where('status_token', 'your-token-here')->first();
var_dump([
    'id' => $request->id,
    'email' => $request->email,
    'notify_on_publish' => $request->notify_on_publish,
    'status' => $request->status
]);
```

### 4. Email Sending Flow

Understanding the notification flow can help diagnose issues:

1. When a user submits a form:
   - Admin notification is sent immediately
   - User status notification is sent if requested on Thank You page

2. When an entity is published:
   - `handleEntityCallback` in WikidataEntityController is called
   - Status is updated to "wikidata_published"
   - `sendStatusChangeNotifications` in EntryStatusController is triggered
   - If user has opted in, publication notification is sent

## Solution Steps

1. **Check Logs**: Examine the Laravel logs for specific error messages
2. **Verify Database**: Ensure entry has correct email and notification settings
3. **Test Manually**: Use the test script to send notifications directly
4. **Check MailHog**: Verify emails are reaching the mail server
5. **Inspect Code Flow**: Follow the execution path for notification sending

## Advanced Debugging

### Adding More Log Points

If needed, add more detailed logging at specific points:

```php
\Illuminate\Support\Facades\Log::info("Debug point", [
    'entry_id' => $entryRequest->id,
    'email' => $entryRequest->email,
    'notify_on_publish' => $entryRequest->notify_on_publish ? 'true' : 'false',
    'status' => $entryRequest->status,
]);
```

### Testing Mail Configuration

Test if mail is working in general:

```bash
docker compose exec app php artisan tinker
```

Then send a test email:
```php
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});
```

Check MailHog for the test email.