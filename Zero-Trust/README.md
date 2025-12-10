# ZeroTrustBank - Secure Banking Application

A secure PHP-based banking application with zero-trust security features, OTP verification, and comprehensive activity logging.

## Features

### 🔐 Security Features
- **Two-Factor Authentication (2FA)**: OTP verification via email for all critical actions
- **Zero-Trust Architecture**: Device fingerprinting and location tracking
- **Session Management**: 
  - 5-minute absolute session timeout
  - 5-second inactivity detection with 30-second warning
  - Automatic logout on timeout
- **Rate Limiting**:
  - 3 password attempts → 2-minute block
  - 3 OTP resends → 2-minute block
  - 15-second cooldown between OTP resends
- **Activity Logging**: All user actions logged with timestamps and IP addresses

### 💰 Banking Features
- **User Registration**: Secure signup with email verification
- **User Login**: Multi-step authentication with OTP
- **Send Money**: Transfer funds with transaction password and OTP verification
- **Account Settings**: Update username/password with OTP confirmation
- **Transaction Password**: 6-digit PIN for money transfers

### 🛡️ Zero-Trust Features
- Device fingerprinting (browser, OS, screen resolution)
- IP address tracking and location verification
- Login history with success/failure tracking
- Trusted device management
- Automatic OTP invalidation on resend

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server
- PHPMailer (included)
- SMTP email account for sending OTPs

## Installation

### 1. Clone or Download
```bash
git clone <your-repo-url>
cd ZeroTrustBank
```

### 2. Database Setup

Create the database and tables:

```bash
mysql -u root -p < database_setup.sql
```

Or manually in phpMyAdmin:
1. Create database `zerotrustdb`
2. Import `database_setup.sql`

### 3. Configure Database Connection

Edit `db.php`:
```php
$host = "localhost";
$dbname = "zerotrustdb";
$username = "root";  // Your MySQL username
$password = "";      // Your MySQL password
```

### 4. Configure Email (SMTP)

Edit `email_config.php`:
```php
$mail->Host       = 'smtp.gmail.com';  // Your SMTP server
$mail->SMTPAuth   = true;
$mail->Username   = 'your-email@gmail.com';  // Your email
$mail->Password   = 'your-app-password';     // Your app password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;
$mail->setFrom('your-email@gmail.com', 'ZeroTrustBank');
```

**For Gmail:**
1. Enable 2-Factor Authentication
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `email_config.php`

### 5. Run the Application

**Option A: PHP Built-in Server**
```bash
php -S localhost:8000
```
Then visit: http://localhost:8000/login.php

**Option B: XAMPP/WAMP**
1. Copy files to `htdocs/zerotrustbank/`
2. Start Apache and MySQL
3. Visit: http://localhost/zerotrustbank/login.php

## Usage

### First Time Setup
1. Visit `signup.php`
2. Fill in registration form (email, username, phone, password)
3. Receive OTP via email
4. Enter OTP to verify account
5. Login with email and password
6. Receive login OTP
7. Enter OTP to access dashboard

### Sending Money
1. From dashboard, click "Send Money"
2. First time: Set 6-digit transaction password
3. Enter recipient email, amount, and transaction password
4. Receive OTP via email
5. Enter OTP to confirm transfer

### Account Settings
1. From dashboard, click "Account Settings"
2. Update username or password
3. Enter current password
4. Receive OTP via email
5. Enter OTP to confirm changes

## Security Timeouts

| Action | Timeout | Consequence |
|--------|---------|-------------|
| Session | 5 minutes | Auto-logout |
| Inactivity | 5 seconds | Warning appears |
| Inactivity Warning | 30 seconds | Auto-logout |
| Password Attempts | 3 failures | 2-minute block |
| OTP Resends | 3 resends | 2-minute block |
| OTP Resend Cooldown | 15 seconds | Must wait |
| OTP Validity | 2 minutes | OTP expires |

## File Structure

```
ZeroTrustBank/
├── README.md                          # This file
├── database_setup.sql                 # Complete database schema
├── SECURITY_FEATURES.md              # Security documentation
│
├── Core Files
├── db.php                            # Database connection
├── auth.php                          # Session & authentication check
├── email_config.php                  # SMTP email configuration
├── activity_logger.php               # Activity logging functions
├── device_fingerprint.php            # Device tracking
├── location_check.php                # IP/location tracking
│
├── Authentication
├── signup.php                        # Registration form
├── signup_action.php                 # Registration handler
├── login.php                         # Login form
├── login_action.php                  # Login handler
├── logout.php                        # Logout handler
│
├── OTP Verification
├── otp.php                           # OTP input form
├── otp_verify.php                    # Signup OTP verification
├── login_otp_verify.php              # Login OTP verification
├── resend_otp.php                    # OTP resend handler
│
├── Dashboard & Features
├── dashboard.php                     # Main dashboard
├── send_money.php                    # Money transfer form
├── send_money_action.php             # Transfer initiation
├── send_money_otp_verify.php         # Transfer OTP verification
├── set_tx_password.php               # Transaction password setup
├── set_tx_password_action.php        # Transaction password handler
├── account_settings.php              # Account settings form
├── account_settings_action.php       # Settings initiation
├── account_settings_otp_verify.php   # Settings OTP verification
│
├── Logs & Assets
├── logs/                             # Activity logs (auto-created)
│   └── activity_log.txt              # User activity log
├── phpmailer/                        # PHPMailer library
└── .gitignore                        # Git ignore file
```

## Database Schema

### Tables
- **Users**: Main user accounts
- **PendingUsers**: Temporary storage during signup
- **otps**: OTP codes for all purposes
- **TrustedDevices**: Device fingerprinting data
- **LoginHistory**: Login attempt tracking
- **OTPResendTracking**: OTP resend rate limiting

See `database_setup.sql` for complete schema.

## Activity Logging

All user actions are logged to `logs/activity_log.txt`:
- User ID
- Action type
- Status (success/failed/pending/blocked)
- IP address
- User agent
- Timestamp
- Additional details

## Troubleshooting

### Email not sending
- Check SMTP credentials in `email_config.php`
- For Gmail: Use App Password, not regular password
- Check firewall/antivirus blocking port 587

### Session timeout issues
- Check PHP session settings in `php.ini`
- Ensure cookies are enabled in browser

### Database connection failed
- Verify MySQL is running
- Check credentials in `db.php`
- Ensure database `zerotrustdb` exists

### OTP not working
- Check `otps` table exists
- Verify email is being sent
- Check OTP hasn't expired (2 minutes)

## Security Best Practices

1. **Change default database credentials** in production
2. **Use HTTPS** in production (SSL certificate)
3. **Secure email_config.php** - don't commit to public repos
4. **Regular backups** of database and logs
5. **Monitor activity logs** for suspicious behavior
6. **Update PHP and MySQL** regularly

## License

This project is for educational purposes.

## Support

For issues or questions, check the logs at `logs/activity_log.txt` for debugging information.
