# Email Configuration Setup Guide - ConsignX

## Overview
The mailing system has been fixed and now uses environment-based configuration. All emails sent from the application (agent welcome emails, customer shipment notifications) now use the centralized email configuration.

## Quick Start

### Option 1: Using Gmail (Recommended for Production)

#### Step 1: Enable 2-Factor Authentication on Gmail
1. Go to https://myaccount.google.com/security
2. Click "2-Step Verification" and follow the prompts
3. Confirm your recovery email and phone number

#### Step 2: Generate App Password
1. Go back to Security settings
2. Scroll down to "App passwords" (only appears if 2FA is enabled)
3. Select "Mail" and "Windows Computer"
4. Google will generate a 16-character password (e.g., `rjrsdxzhsjsgrbnp`)
5. Copy this password

#### Step 3: Update .env File
Edit `.env` in your project root and update:
```env
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=ConsignX Team

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_DEBUG=0
```

**Note:** Use your full Gmail address (including @gmail.com) for both MAIL_FROM_ADDRESS and SMTP_USERNAME.

---

### Option 2: Using Mailtrap (Recommended for Development/Testing)

Mailtrap is a free service that captures all emails without sending them (perfect for testing).

#### Step 1: Create a Mailtrap Account
1. Go to https://mailtrap.io/
2. Sign up for a free account
3. Log in and create a new inbox

#### Step 2: Get SMTP Credentials
1. Click on your inbox
2. Click "Integrations" > "PHPMailer"
3. Copy your SMTP credentials

#### Step 3: Update .env File
Edit `.env` and uncomment the Mailtrap section:
```env
MAIL_FROM_ADDRESS=noreply@consignx.com
MAIL_FROM_NAME=ConsignX Team

SMTP_HOST=live.smtp.mailtrap.io
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=api
SMTP_PASSWORD=your-mailtrap-token
SMTP_DEBUG=0
```

---

### Option 3: Local Development (MailHog)

For local testing without external services:

#### Step 1: Install MailHog
1. Download from https://github.com/mailhog/MailHog/releases
2. Run the executable (mailhog.exe on Windows)

#### Step 2: Update .env File
```env
SMTP_HOST=localhost
SMTP_PORT=1025
SMTP_ENCRYPTION=none
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_DEBUG=0
```

#### Step 3: View Emails
- Open http://localhost:1025 in your browser to see all emails sent

---

## Environment Variables Explained

| Variable | Description | Example |
|----------|-------------|---------|
| `MAIL_FROM_ADDRESS` | Sender email address | your-email@gmail.com |
| `MAIL_FROM_NAME` | Sender display name | ConsignX Team |
| `SMTP_HOST` | SMTP server hostname | smtp.gmail.com |
| `SMTP_PORT` | SMTP server port (usually 587 for TLS, 465 for SSL) | 587 |
| `SMTP_ENCRYPTION` | Encryption type: `tls`, `ssl`, or leave empty for none | tls |
| `SMTP_USERNAME` | SMTP authentication username | your-email@gmail.com |
| `SMTP_PASSWORD` | SMTP authentication password | your-app-password |
| `SMTP_DEBUG` | Enable debug mode (0=off, 1-2=on) | 0 |

---

## Testing the Email Configuration

### Manual Test (Using PHP)
Create a test file `test_email.php` in your project root:

```php
<?php
require_once 'includes/config.php';
require_once 'includes/mailer.php';

$result = send_email(
    'test@example.com',
    'Test Email from ConsignX',
    '<h2>Hello!</h2><p>This is a test email.</p>'
);

if ($result['success']) {
    echo "✓ Email sent successfully!";
} else {
    echo "✗ Email failed to send:";
    echo "<br>Error: " . htmlspecialchars($result['error']);
}
?>
```

Then visit: `http://localhost/consignxAnti/test_email.php`

### Check Application Logs
Emails send/fail logs are written to PHP error logs. You can check:
- PHP error log location (usually in your XAMPP logs folder)
- Or enable SMTP_DEBUG=2 in .env to see detailed SMTP conversation

---

## Troubleshooting

### "SMTP configuration is incomplete"
- Check that .env file exists in project root
- Verify SMTP_HOST, SMTP_USERNAME, and SMTP_PASSWORD are not empty
- Check for any typos in variable names

### Gmail: "Invalid Credentials"
- Verify you generated an App Password (not just enabling 2FA)
- Use the 16-character app password, not your regular Gmail password
- Ensure 2-Factor Authentication is enabled on your Gmail account
- The password should not have spaces

### Mailtrap: "Authentication Failed"
- Double-check your Mailtrap credentials from the integration page
- Make sure you're using the correct token
- Verify SMTP_HOST is `live.smtp.mailtrap.io` (not the old `smtp.mailtrap.io`)

### "Connection timed out"
- Check SMTP_HOST and SMTP_PORT values
- Verify your server can reach the SMTP server (firewall settings)
- Some hosting providers block SMTP ports (587, 465)

### Emails sent but recipient shows as system user
- Make sure MAIL_FROM_ADDRESS is a valid email format
- Check MAIL_FROM_NAME doesn't contain invalid characters

---

## Email Functions

### send_email()
Low-level function that sends email using configured SMTP.
```php
$result = send_email(
    $recipient_email,
    $subject,
    $html_body
);
// Returns: ['success' => bool, 'message' => string, 'error' => string]
```

### send_shipment_notification_new()
Sends welcome email to new customers with their login credentials.
```php
$result = send_shipment_notification_new(
    $customer_email,
    $customer_name,
    $temp_password,
    $tracking_number
);
```

### send_shipment_notification_existing()
Sends shipment notification to existing customers.
```php
$result = send_shipment_notification_existing(
    $customer_email,
    $customer_name,
    $tracking_number
);
```

### send_agent_welcome_email()
Sends welcome email to new agents with their credentials.
```php
$result = send_agent_welcome_email(
    $agent_email,
    $company_name,
    $status,
    $password
);
```

---

## Changes Made

### Files Modified:
1. **includes/config.php** - Now loads .env file and defines constants from it
2. **includes/mailer.php** - Complete rewrite to use configuration and return detailed results
3. **admin/manage_agents.php** - Checks email status and provides feedback
4. **admin/company_requests.php** - Checks email status and provides feedback
5. **agent/create_shipment.php** - Checks email status and provides feedback
6. **admin/manage_shipments.php** - Checks email status and provides feedback

### New Files:
1. **.env** - Environment configuration file (template with instructions)

### Key Improvements:
✓ Removed hardcoded credentials
✓ Added proper error handling and feedback
✓ Support for multiple email services (Gmail, Mailtrap, MailHog)
✓ Clear logging of email successes/failures
✓ User-facing error messages for email failures
✓ Validation of SMTP configuration
✓ Validation of recipient email addresses

---

## Security Best Practices

1. **Never commit .env with real credentials** - Add .env to .gitignore
2. **Use App Passwords instead of account password** for Gmail
3. **Enable 2-Factor Authentication** on email accounts
4. **Use TLS encryption** when possible (SMTP_ENCRYPTION=tls on port 587)
5. **Rotate credentials regularly** and regenerate app passwords
6. **Keep SMTP_PASSWORD secure** - don't share or expose it

---

## Support

If emails still don't work:
1. Check PHP error logs for detailed error messages
2. Enable SMTP_DEBUG=2 in .env to see SMTP conversation details
3. Verify the recipient email address is valid
4. Test with Option 3 (MailHog) first to verify application code works
5. Then switch to Gmail or Mailtrap once basic functionality is confirmed

