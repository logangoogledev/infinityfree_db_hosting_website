<?php
require_once '../config/db.php';
require_once '../config/security.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Security: Rate limit login attempts
$client_ip = get_client_ip();
if (is_rate_limited('login_' . $client_ip, RATE_LIMIT_LOGIN_ATTEMPTS, RATE_LIMIT_LOGIN_WINDOW)) {
    $_SESSION['error'] = 'Too many login attempts. Please try again later.';
    header('Location: ../index.php?error=Too many login attempts');
    exit;
}

try {
    $email = sanitize_email(trim($_POST['email']));
    $password = sanitize_string(trim($_POST['password']), 255);
} catch (Exception $e) {
    header('Location: ../index.php?error=Invalid input');
    exit;
}

// Validate input
if (empty($email) || empty($password)) {
    log_security_event(0, 'LOGIN', 'EMPTY_CREDENTIALS', ['email' => $email, 'ip' => $client_ip], 'WARNING');
    header('Location: ../index.php?error=Please fill in all fields');
    exit;
}

// Check for account lockout
$lockout_query = "SELECT id, account_locked_until FROM users WHERE email = ?";
$stmt = $conn->prepare($lockout_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$lockout_result = $stmt->get_result();

if ($lockout_result->num_rows > 0) {
    $user_check = $lockout_result->fetch_assoc();
    if ($user_check['account_locked_until'] && strtotime($user_check['account_locked_until']) > time()) {
        log_security_event($user_check['id'], 'LOGIN', 'LOCKED_ACCOUNT_ATTEMPT', ['ip' => $client_ip], 'WARNING');
        header('Location: ../index.php?error=Account temporarily locked. Try again later.');
        exit;
    }
}

// Query user
$query = "SELECT id, username, password, login_attempts FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log failed login with unknown email
    log_security_event(0, 'LOGIN', 'UNKNOWN_EMAIL', ['email' => $email, 'ip' => $client_ip], 'WARNING');
    header('Location: ../index.php?error=Invalid email or password');
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    // Increment failed login attempts
    $attempts = $user['login_attempts'] + 1;
    $lock_until = ($attempts >= 5) ? date('Y-m-d H:i:s', time() + 1800) : null; // Lock for 30 mins after 5 attempts
    
    $update_query = "UPDATE users SET login_attempts = ?, account_locked_until = ?, last_login_ip = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('issi', $attempts, $lock_until, $client_ip, $user['id']);
    $stmt->execute();
    
    log_security_event($user['id'], 'LOGIN', 'FAILED_PASSWORD', ['attempts' => $attempts, 'ip' => $client_ip], 'WARNING');
    
    if ($lock_until) {
        header('Location: ../index.php?error=Account locked due to too many failed attempts.');
    } else {
        header('Location: ../index.php?error=Invalid email or password');
    }
    exit;
}

// Successful login - reset attempts and update last login
$update_query = "UPDATE users SET login_attempts = 0, account_locked_until = NULL, last_login_ip = ?, last_login_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param('si', $client_ip, $user['id']);
$stmt->execute();

// Set session with additional security info
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $email;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

log_security_event($user['id'], 'LOGIN', 'SUCCESS', ['ip' => $client_ip], 'INFO');

header('Location: ../dashboard.php');
exit;
?>
