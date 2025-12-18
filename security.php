<?php
session_start();
require_once 'config/db.php';
require_once 'config/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = "SELECT username, email, last_login_ip, last_login_at FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$user = $user_result->fetch_assoc();

// Get security logs
$logs_query = "SELECT event_type, action, severity, ip_address, created_at FROM security_logs 
               WHERE user_id = ? ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($logs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs_result = $stmt->get_result();

// Get security breaches
$breaches_query = "SELECT breach_type, status, created_at, details FROM security_breaches 
                   WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($breaches_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$breaches_result = $stmt->get_result();

// Get API access logs
$api_query = "SELECT endpoint, method, status_code, response_time_ms, created_at FROM api_access_logs 
              WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($api_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$api_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Center - Database Hosting</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .security-center {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .security-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .status-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-card.warning {
            border-left-color: #ffc107;
        }
        
        .status-card.critical {
            border-left-color: #dc3545;
        }
        
        .status-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .log-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .log-table th {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .log-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .log-table tr:hover {
            background: #f5f5f5;
        }
        
        .severity-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .severity-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .severity-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .severity-critical {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            margin-top: 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .alert-banner {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-banner.critical {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">DB Hosting</div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="security.php" class="nav-link active">Security</a>
                <span class="nav-user"><?php echo htmlspecialchars($user['username']); ?></span>
                <a href="auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="security-center">
            <div class="security-header">
                <h1>🔒 Security Center</h1>
                <p>Monitor your account activity and security events</p>
            </div>

            <!-- Status Overview -->
            <div class="status-box">
                <div class="status-card">
                    <h3>Last Login</h3>
                    <p>
                        <?php echo $user['last_login_at'] ? date('M d, Y H:i', strtotime($user['last_login_at'])) : 'Never'; ?>
                        <br>
                        <small>IP: <?php echo htmlspecialchars($user['last_login_ip'] ?? 'Unknown'); ?></small>
                    </p>
                </div>
                
                <div class="status-card">
                    <h3>Security Status</h3>
                    <p style="color: #28a745; font-weight: bold;">✓ All Clear</p>
                    <small>No active threats detected</small>
                </div>
                
                <div class="status-card">
                    <h3>API Tokens</h3>
                    <p>Token: <code><?php echo htmlspecialchars(substr($user['email'], 0, 10)); ?>***</code></p>
                    <small>Email-based authentication</small>
                </div>
            </div>

            <!-- Security Breaches -->
            <?php if ($breaches_result->num_rows > 0): ?>
            <div class="section">
                <h2>🚨 Recent Security Events</h2>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($breach = $breaches_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($breach['breach_type']); ?></td>
                            <td>
                                <span class="severity-badge <?php echo strtolower($breach['status']); ?>">
                                    <?php echo htmlspecialchars($breach['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($breach['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Audit Log -->
            <div class="section">
                <h2>📋 Audit Log (Last 100 Events)</h2>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Action</th>
                            <th>Severity</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs_result->num_rows > 0): ?>
                            <?php while ($log = $logs_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td>
                                    <span class="severity-badge severity-<?php echo strtolower($log['severity']); ?>">
                                        <?php echo htmlspecialchars($log['severity']); ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No security events recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- API Access Log -->
            <div class="section">
                <h2>🔌 API Access Log (Last 50 Requests)</h2>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Response Time</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($api_result->num_rows > 0): ?>
                            <?php while ($api = $api_result->fetch_assoc()): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($api['endpoint']); ?></code></td>
                                <td><?php echo htmlspecialchars($api['method']); ?></td>
                                <td>
                                    <?php 
                                    $status = $api['status_code'];
                                    $color = ($status >= 200 && $status < 300) ? 'green' : (($status >= 400) ? 'red' : 'orange');
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($api['response_time_ms']); ?>ms</td>
                                <td><?php echo date('M d, Y H:i', strtotime($api['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No API requests recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Security Tips -->
            <div class="section">
                <h2>🛡️ Security Best Practices</h2>
                <ul style="line-height: 1.8;">
                    <li><strong>Protect your email:</strong> Your email serves as your API token. Never share it.</li>
                    <li><strong>Monitor activity:</strong> Regularly check your audit log for suspicious activity.</li>
                    <li><strong>Update password:</strong> Change your password every 90 days.</li>
                    <li><strong>Check login locations:</strong> Review "Last Login IP" to ensure you recognize the location.</li>
                    <li><strong>Report issues:</strong> If you see unauthorized activity, contact support immediately.</li>
                    <li><strong>Use HTTPS:</strong> Always use HTTPS for secure communication.</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
