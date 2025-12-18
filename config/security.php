<?php
/**
 * Security Configuration and Functions
 * Implements access control, audit logging, breach detection, and security monitoring
 */

session_start();

// ============================================================================
// Security Configuration
// ============================================================================

// Rate limiting settings
define('RATE_LIMIT_REQUESTS', 100);           // Requests per time window
define('RATE_LIMIT_WINDOW', 3600);            // Time window in seconds (1 hour)
define('RATE_LIMIT_LOGIN_ATTEMPTS', 5);       // Max login attempts
define('RATE_LIMIT_LOGIN_WINDOW', 900);       // 15 minutes

// Session settings
define('SESSION_TIMEOUT', 3600);              // 1 hour
define('REQUIRE_HTTPS', true);                // Require HTTPS

// Email for breach notifications
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'fletcher.9f@gmail.com');
define('SECURITY_EMAIL', getenv('SECURITY_EMAIL') ?: 'fletcher.9f@gmail.com');

// Audit log settings
define('LOG_PATH', __DIR__ . '/../logs/');
define('AUDIT_LOG_RETENTION', 30);            // Days to keep logs

// Suspicious activity thresholds
define('FAILED_LOGIN_THRESHOLD', 10);         // Alert after N failed logins
define('API_ANOMALY_THRESHOLD', 500);         // Alert on unusual API activity
define('DATA_ACCESS_THRESHOLD', 1000);        // Alert on large data access

// ============================================================================
// Initialize Security
// ============================================================================

// Ensure log directory exists
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0700, true);
}

// HTTPS enforcement
if (REQUIRE_HTTPS && empty($_SERVER['HTTPS']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ============================================================================
// Input Validation & Sanitization
// ============================================================================

/**
 * Sanitize and validate email input
 */
function sanitize_email($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    return $email;
}

/**
 * Sanitize string input (prevent XSS)
 */
function sanitize_string($string, $max_length = 255) {
    $string = trim($string);
    if (strlen($string) > $max_length) {
        throw new Exception("Input exceeds maximum length of $max_length");
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize integer input
 */
function sanitize_int($value, $min = 0, $max = PHP_INT_MAX) {
    $value = intval($value);
    if ($value < $min || $value > $max) {
        throw new Exception("Value must be between $min and $max");
    }
    return $value;
}

/**
 * Validate file name (prevent directory traversal)
 */
function validate_filename($filename) {
    if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
        throw new Exception('Invalid filename');
    }
    if (strpos($filename, '..') !== false) {
        throw new Exception('Directory traversal detected');
    }
    return $filename;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF token validation failed');
    }
    return true;
}

// ============================================================================
// Rate Limiting
// ============================================================================

/**
 * Check if IP is rate limited
 */
function is_rate_limited($key, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
    $cache_key = 'rate_limit_' . $key;
    $cache_file = LOG_PATH . '.rate_' . hash('sha256', $key);
    
    $now = time();
    $data = [];
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true) ?: [];
    }
    
    // Remove old entries
    $data = array_filter($data, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    if (count($data) >= $limit) {
        return true;
    }
    
    $data[] = $now;
    file_put_contents($cache_file, json_encode($data), LOCK_EX);
    return false;
}

/**
 * Get current IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============================================================================
// Audit Logging
// ============================================================================

/**
 * Log security event to audit trail
 */
function log_security_event($user_id, $event_type, $action, $details = [], $severity = 'INFO') {
    global $conn;
    
    $ip = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');
    $details_json = json_encode($details);
    
    try {
        $query = "INSERT INTO security_logs (user_id, event_type, action, details, ip_address, user_agent, severity, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isssssss', $user_id, $event_type, $action, $details_json, $ip, $user_agent, $severity, $timestamp);
        $stmt->execute();
        
        // Check for suspicious patterns
        check_security_anomalies($user_id, $event_type, $severity);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Get audit logs for user
 */
function get_audit_logs($user_id, $limit = 50) {
    global $conn;
    
    $query = "SELECT * FROM security_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================================================
// Access Control
// ============================================================================

/**
 * Verify user owns database
 */
function verify_database_ownership($user_id, $db_id) {
    global $conn;
    
    $query = "SELECT user_id FROM databases WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $db_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        log_security_event($user_id, 'DATABASE_ACCESS', 'UNAUTHORIZED_ACCESS', 
            ['db_id' => $db_id], 'WARNING');
        throw new Exception('Database not found or unauthorized');
    }
    
    $row = $result->fetch_assoc();
    if ($row['user_id'] != $user_id) {
        log_security_event($user_id, 'DATABASE_ACCESS', 'UNAUTHORIZED_ACCESS_ATTEMPT', 
            ['db_id' => $db_id, 'owner_id' => $row['user_id']], 'CRITICAL');
        throw new Exception('Unauthorized access attempt');
    }
    
    return true;
}

/**
 * Verify user file access (check user folder)
 */
function verify_file_access($user_id, $file_path) {
    $user_dir = realpath(__DIR__ . "/../data/user_{$user_id}");
    $file_path = realpath($file_path);
    
    if ($user_dir === false || $file_path === false) {
        throw new Exception('Invalid file path');
    }
    
    if (strpos($file_path, $user_dir) !== 0) {
        log_security_event($user_id, 'FILE_ACCESS', 'UNAUTHORIZED_FILE_ACCESS', 
            ['attempted_path' => $file_path], 'CRITICAL');
        throw new Exception('Access denied');
    }
    
    return true;
}

/**
 * Ensure user directory exists with proper permissions
 */
function ensure_user_directory($user_id) {
    $user_dir = __DIR__ . "/../data/user_{$user_id}";
    
    if (!is_dir($user_dir)) {
        @mkdir($user_dir, 0700, true);
        @file_put_contents($user_dir . '/.htaccess', 'Deny from all');
        @file_put_contents($user_dir . '/index.php', '<?php exit; ?>');
    }
    
    // Verify permissions
    @chmod($user_dir, 0700);
    return $user_dir;
}

// ============================================================================
// Breach Detection & Alerting
// ============================================================================

/**
 * Check for security anomalies and trigger alerts
 */
function check_security_anomalies($user_id, $event_type, $severity) {
    global $conn;
    
    $anomaly_detected = false;
    $anomaly_details = [];
    
    // Check failed login attempts
    if ($event_type === 'LOGIN' && $severity === 'WARNING') {
        $query = "SELECT COUNT(*) as count FROM security_logs 
                  WHERE user_id = ? AND event_type = 'LOGIN' AND severity = 'WARNING' 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] >= FAILED_LOGIN_THRESHOLD) {
            $anomaly_detected = true;
            $anomaly_details['type'] = 'EXCESSIVE_FAILED_LOGINS';
            $anomaly_details['count'] = $result['count'];
            $anomaly_details['threshold'] = FAILED_LOGIN_THRESHOLD;
        }
    }
    
    // Check unusual API access patterns
    if ($event_type === 'API_ACCESS') {
        $query = "SELECT COUNT(*) as count FROM security_logs 
                  WHERE user_id = ? AND event_type = 'API_ACCESS' 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > API_ANOMALY_THRESHOLD) {
            $anomaly_detected = true;
            $anomaly_details['type'] = 'UNUSUAL_API_ACTIVITY';
            $anomaly_details['requests'] = $result['count'];
            $anomaly_details['threshold'] = API_ANOMALY_THRESHOLD;
        }
    }
    
    // Check unauthorized access attempts
    if ($severity === 'CRITICAL') {
        $anomaly_detected = true;
        $anomaly_details['type'] = 'UNAUTHORIZED_ACCESS_ATTEMPT';
    }
    
    if ($anomaly_detected) {
        trigger_security_alert($user_id, $anomaly_details);
    }
}

/**
 * Trigger security alert and send email
 */
function trigger_security_alert($user_id, $details) {
    global $conn;
    
    // Get user email
    $query = "SELECT email, username FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) return;
    
    $breach_data = [
        'user_id' => $user_id,
        'user_email' => $user['email'],
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'anomaly_details' => $details
    ];
    
    // Store breach report
    $query = "INSERT INTO security_breaches (user_id, breach_type, details, ip_address, user_agent, created_at, status) 
              VALUES (?, ?, ?, ?, ?, NOW(), 'OPEN')";
    $stmt = $conn->prepare($query);
    $breach_type = $details['type'] ?? 'UNKNOWN_ANOMALY';
    $details_json = json_encode($breach_data);
    $ip = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt->bind_param('isss', $user_id, $breach_type, $details_json, $ip, $user_agent);
    $stmt->execute();
    
    // Send alert email to user
    send_security_alert_email($user['email'], $user['username'], $breach_data);
    
    // Send report to admin
    send_admin_security_report($breach_data);
}

/**
 * Send security alert email to user
 */
function send_security_alert_email($user_email, $username, $breach_data) {
    $subject = "⚠️ Security Alert - Unusual Activity Detected";
    
    $html_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .alert-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .danger-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .details-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px; }
            h2 { color: #dc3545; }
        </style>
    </head>
    <body>
        <h2>🚨 Security Alert - Unusual Activity</h2>
        
        <div class='alert-box'>
            <strong>Hello " . htmlspecialchars($username) . ",</strong><br>
            We detected unusual activity on your Database Hosting account. Please review the details below.
        </div>
        
        <div class='danger-box'>
            <h3>Alert Details:</h3>
            <p><strong>Alert Type:</strong> " . htmlspecialchars($breach_data['anomaly_details']['type']) . "</p>
            <p><strong>Timestamp:</strong> " . htmlspecialchars($breach_data['timestamp']) . "</p>
            <p><strong>IP Address:</strong> " . htmlspecialchars($breach_data['ip_address']) . "</p>
            <p><strong>User Agent:</strong> " . htmlspecialchars($breach_data['user_agent']) . "</p>
        </div>
        
        <div class='details-box'>
            <strong>Full Details:</strong><br>
            " . nl2br(htmlspecialchars(json_encode($breach_data['anomaly_details'], JSON_PRETTY_PRINT))) . "
        </div>
        
        <h3>What You Should Do:</h3>
        <ul>
            <li>Review your account activity in the Security Log</li>
            <li>If this wasn't you, <strong>change your password immediately</strong></li>
            <li>Check for unauthorized databases or data modifications</li>
            <li>Enable two-factor authentication if available</li>
            <li>Contact support if you need assistance</li>
        </ul>
        
        <p><strong>Security Team</strong><br>
        Database Hosting</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SECURITY_EMAIL . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    @mail($user_email, $subject, $html_body, $headers);
}

/**
 * Send security report to admin
 */
function send_admin_security_report($breach_data) {
    $subject = "🚨 SECURITY ALERT - " . $breach_data['anomaly_details']['type'];
    
    $html_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .danger-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .details-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .code { font-family: monospace; font-size: 12px; }
        </style>
    </head>
    <body>
        <h2>🚨 SECURITY BREACH ALERT</h2>
        
        <div class='danger-box'>
            <h3>CRITICAL SECURITY EVENT</h3>
            <p><strong>Alert Type:</strong> " . htmlspecialchars($breach_data['anomaly_details']['type']) . "</p>
            <p><strong>User ID:</strong> " . htmlspecialchars($breach_data['user_id']) . "</p>
            <p><strong>User Email:</strong> " . htmlspecialchars($breach_data['user_email']) . "</p>
            <p><strong>Timestamp:</strong> " . htmlspecialchars($breach_data['timestamp']) . "</p>
            <p><strong>IP Address:</strong> " . htmlspecialchars($breach_data['ip_address']) . "</p>
        </div>
        
        <div class='details-box'>
            <h3>Full Breach Data:</h3>
            <pre class='code'>" . htmlspecialchars(json_encode($breach_data, JSON_PRETTY_PRINT)) . "</pre>
        </div>
        
        <h3>Recommended Actions:</h3>
        <ul>
            <li>Review account activity immediately</li>
            <li>Verify user credentials integrity</li>
            <li>Check database access logs</li>
            <li>Consider temporary account restrictions</li>
            <li>Contact user if breach is confirmed</li>
        </ul>
        
        <p>Time: " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: security-system@database-hosting\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    @mail(ADMIN_EMAIL, $subject, $html_body, $headers);
}

/**
 * Log breach to file system
 */
function log_breach_to_file($breach_data) {
    $log_file = LOG_PATH . 'breaches_' . date('Y-m-d') . '.log';
    $log_entry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($breach_data) . "\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    @chmod($log_file, 0600);
}

// ============================================================================
// Session Security
// ============================================================================

/**
 * Validate session security
 */
function validate_session() {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session expired or invalid');
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        throw new Exception('Session timeout');
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Require login
 */
function require_login() {
    try {
        validate_session();
    } catch (Exception $e) {
        header('Location: /index.php?error=' . urlencode('Login required'));
        exit;
    }
}

?>
