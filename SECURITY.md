# Security Implementation Guide

## Overview

Your Database Hosting platform now has enterprise-grade security features including:

- ✅ Rate limiting and DDoS protection
- ✅ Comprehensive audit logging
- ✅ Breach detection and alerting
- ✅ Account lockout after failed login attempts
- ✅ User folder isolation (each user has /data/user_{id}/)
- ✅ Input validation and sanitization
- ✅ CSRF token generation
- ✅ IP address tracking and logging
- ✅ Security breach email notifications
- ✅ API access logging
- ✅ Session security with timeout

## Quick Setup (5 minutes)

### Step 1: Create Security Log Directory
```bash
mkdir -p logs
chmod 700 logs
```

### Step 2: Import Database Schema
1. Log into PhpMyAdmin
2. Select your database: `if0_40374989_db_storage`
3. Go to SQL tab
4. Copy/paste contents of `config/security_schema.sql`
5. Click Execute

### Step 3: Set Environment Variables
Add to your hosting environment or `.env` file:
```
ADMIN_EMAIL=your-email@example.com
SECURITY_EMAIL=security-alerts@example.com
```

## Features in Detail

### 1. Rate Limiting
- **API**: 100 requests/hour per IP
- **Login**: 5 attempts per 15 minutes
- **Auto-lock**: Account locked for 30 minutes after 5 failed attempts

**Code Location**: `config/security.php`
**Functions**: `is_rate_limited()`, `get_client_ip()`

### 2. Input Validation & Sanitization
All user inputs are validated:
- Emails: Format validation + sanitization
- Strings: Length limits + XSS prevention
- Integers: Range validation
- Filenames: Directory traversal prevention

**Functions**:
- `sanitize_email($email)` - Validate email format
- `sanitize_string($string, $max_length)` - Prevent XSS
- `sanitize_int($value, $min, $max)` - Validate integers
- `validate_filename($filename)` - Prevent directory traversal

### 3. User Folder Isolation

Each user's databases are stored in individual, isolated directories:

```
/data/
├── user_1/
│   ├── database_1.csv
│   ├── database_1_schema.json
│   └── .htaccess (Deny from all)
├── user_2/
│   ├── database_1.csv
│   └── ...
└── ...
```

**Security Benefits**:
- Users cannot access other users' data
- Directory traversal attacks prevented
- File permissions set to 0700 (owner only)

**Functions**:
- `ensure_user_directory($user_id)` - Create isolated folders
- `verify_file_access($user_id, $file_path)` - Prevent traversal

### 4. Comprehensive Audit Logging

All user actions are logged:

**Logged Events**:
- Login attempts (success/failure)
- Database access
- API requests
- Data modifications
- Failed security checks
- IP addresses and user agents

**Table**: `security_logs`
**Function**: `log_security_event($user_id, $event_type, $action, $details, $severity)`

**Example**:
```php
log_security_event(
    $user_id,
    'DATABASE_ACCESS',
    'FETCH_DATA',
    ['db_id' => 1, 'rows' => 100],
    'INFO'
);
```

### 5. Breach Detection & Alerts

Automatic detection of:

#### Excessive Failed Logins
- **Threshold**: 10 failed attempts in 1 hour
- **Action**: Email alert to user
- **Admin Alert**: Yes

#### Unusual API Activity
- **Threshold**: 500+ API requests in 1 hour
- **Action**: Email alert
- **Severity**: CRITICAL

#### Unauthorized Access Attempts
- **Detection**: Attempting to access someone else's database
- **Action**: Immediate block + email alert
- **Severity**: CRITICAL

#### Account Lockout
- **Trigger**: 5 failed login attempts
- **Duration**: 30 minutes
- **Notification**: User notified immediately

### 6. Security Breach Emails

When suspicious activity is detected, users receive alerts:

**User Email Contains**:
- ⚠️ Alert type and timestamp
- IP address and location (if available)
- Recommended actions
- Link to security center

**Admin Email Contains**:
- Full breach details in JSON format
- User information
- IP address and user agent
- Recommended investigation steps

**Email Functions**:
- `send_security_alert_email($user_email, $username, $breach_data)`
- `send_admin_security_report($breach_data)`
- `log_breach_to_file($breach_data)`

### 7. Session Security

- **Timeout**: 1 hour of inactivity
- **CSRF Protection**: Token generation on login
- **HTTPS Enforcement**: Redirects HTTP to HTTPS
- **Security Headers**:
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: DENY
  - X-XSS-Protection: 1; mode=block
  - Strict-Transport-Security: max-age=31536000
  - Content-Security-Policy

**Functions**:
- `validate_session()` - Check session validity
- `require_login()` - Enforce authentication
- `generate_csrf_token()` - Create CSRF tokens
- `verify_csrf_token($token)` - Validate CSRF tokens

### 8. API Security

All API requests are secured with:

1. **Authentication**: Email-based token validation
2. **Rate Limiting**: 100 requests/hour per IP
3. **Access Control**: Database ownership verification
4. **Input Validation**: All parameters sanitized
5. **Logging**: Every request logged
6. **File Access Control**: Directory traversal prevention

**API Audit Log Table**: `api_access_logs`

Contains:
- Endpoint accessed
- HTTP method
- Response status code
- Response time
- Database ID accessed

## User-Facing Features

### Security Center Page (`/security.php`)

Users can now view:

1. **Last Login Info**
   - Timestamp
   - IP address

2. **Audit Log**
   - Event type
   - Action performed
   - Severity level
   - IP address
   - Timestamp

3. **API Access Log**
   - Endpoints accessed
   - HTTP methods used
   - Response status codes
   - Response times
   - Request timestamps

4. **Security Events**
   - Breach alerts
   - Status updates
   - Investigation notes

## Database Tables

### security_logs
Stores all security events
```
- user_id
- event_type (LOGIN, API_ACCESS, DATABASE_ACCESS, etc.)
- action (description)
- details (JSON)
- ip_address
- user_agent
- severity (INFO, WARNING, CRITICAL)
- created_at
```

### security_breaches
Stores detected breaches
```
- user_id
- breach_type
- details (JSON)
- ip_address
- user_agent
- status (OPEN, INVESTIGATING, RESOLVED, FALSE_ALARM)
- created_at
- resolved_at
- admin_notes
```

### api_access_logs
Logs all API requests
```
- user_id
- endpoint
- method
- db_id
- ip_address
- status_code
- response_time_ms
- request_size
- response_size
- created_at
```

### failed_logins
Tracks failed login attempts
```
- email
- ip_address
- user_agent
- reason
- attempted_at
```

### ip_restrictions
IP whitelist/blacklist per user
```
- user_id
- ip_address
- restriction_type (WHITELIST, BLACKLIST)
- reason
- created_at
- expires_at
```

### api_tokens
Future API key management
```
- user_id
- token
- token_hash
- description
- last_used
- created_at
- expires_at
- is_active
```

## Configuration

Edit `config/security.php` to adjust thresholds:

```php
define('RATE_LIMIT_REQUESTS', 100);           // Requests per hour per IP
define('RATE_LIMIT_WINDOW', 3600);            // Time window (seconds)
define('RATE_LIMIT_LOGIN_ATTEMPTS', 5);       // Max login attempts
define('RATE_LIMIT_LOGIN_WINDOW', 900);       // 15 minutes
define('SESSION_TIMEOUT', 3600);              // 1 hour
define('FAILED_LOGIN_THRESHOLD', 10);         // Alert after N failures
define('API_ANOMALY_THRESHOLD', 500);         // Alert on unusual activity
```

## Integration with Existing Code

### Login Process
```php
require_once 'config/security.php';

// Automatically includes:
// - Rate limiting checks
// - Input sanitization
// - Audit logging
// - Account lockout logic
// - IP tracking
```

### API Requests
```php
require_once 'config/security.php';

// Automatically includes:
// - Rate limiting
// - CSRF validation
// - Input validation
// - Database ownership verification
// - Access logging
```

### File Access
```php
// Verify user can access file
verify_file_access($user_id, $file_path);

// Ensure user directory exists
$user_dir = ensure_user_directory($user_id);
```

## Monitoring

### Check Security Logs
```sql
SELECT * FROM security_logs 
WHERE severity = 'CRITICAL' 
ORDER BY created_at DESC;
```

### Check Breaches
```sql
SELECT * FROM security_breaches 
WHERE status = 'OPEN' 
ORDER BY created_at DESC;
```

### Check API Anomalies
```sql
SELECT user_id, COUNT(*) as requests, MAX(created_at) as last_request
FROM api_access_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY user_id
HAVING requests > 500;
```

### Check Failed Logins
```sql
SELECT email, COUNT(*) as attempts, MAX(attempted_at) as last_attempt
FROM failed_logins 
WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY email
ORDER BY attempts DESC;
```

## Troubleshooting

### Users Not Receiving Email Alerts

1. Check `ADMIN_EMAIL` and `SECURITY_EMAIL` environment variables
2. Verify your hosting allows mail() function
3. Check server logs: `/logs/`
4. Manually test:
   ```php
   $to = "test@example.com";
   $subject = "Test";
   $message = "Test email";
   mail($to, $subject, $message);
   ```

### Rate Limiting Too Strict

Edit `config/security.php`:
```php
define('RATE_LIMIT_REQUESTS', 200);  // Increase from 100
define('RATE_LIMIT_WINDOW', 7200);   // Increase to 2 hours
```

### Users Locked Out of Accounts

Check `users` table:
```sql
SELECT email, account_locked_until FROM users WHERE account_locked_until IS NOT NULL;
```

To unlock manually:
```sql
UPDATE users SET account_locked_until = NULL WHERE email = 'user@example.com';
```

## Best Practices

1. **Review logs regularly** - Check security center for unusual patterns
2. **Respond to alerts** - Don't ignore breach notifications
3. **Update passwords** - Encourage users to change passwords every 90 days
4. **Monitor API usage** - Check for unusual activity patterns
5. **Backup logs** - Archive old logs for compliance
6. **Test security** - Periodically test that alerts work
7. **Keep PHP updated** - Security depends on current PHP version

## Files Modified/Created

- ✅ `config/security.php` - Core security functions
- ✅ `config/security_schema.sql` - Database tables
- ✅ `auth/login.php` - Enhanced with security
- ✅ `api/remote.php` - Enhanced with security
- ✅ `security.php` - New security center page
- ✅ `SECURITY_SETUP.sh` - Setup script
- ✅ `/logs/` - New logs directory

## Support

For issues or questions:
1. Check logs in `/logs/` directory
2. Review security center page at `/security.php`
3. Check database tables for error details
4. Contact hosting support with error details

---

**Security is ongoing.** Monitor regularly and respond to alerts promptly!
