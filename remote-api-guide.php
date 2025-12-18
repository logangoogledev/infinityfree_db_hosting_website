<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

$user_id = $_SESSION['user_id'];

// Get user info
$user_query = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$user = $user_result->fetch_assoc();

// Get user's databases
$db_query = "SELECT id, name FROM `databases` WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($db_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$databases = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote API Guide - Database Hosting</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .api-guide {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section {
            margin-bottom: 40px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        
        .section h3 {
            color: #555;
            margin-top: 20px;
        }
        
        .code-block {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .token-box {
            background: #fff;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .token-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: monospace;
            background: #f0f0f0;
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        
        .tab-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .tab-btn.active {
            background: #0056b3;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .endpoint-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        
        .endpoint-table th,
        .endpoint-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .endpoint-table th {
            background: #007bff;
            color: white;
        }
        
        .endpoint-table tr:hover {
            background: #f5f5f5;
        }
        
        .method-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .method-get {
            background: #28a745;
            color: white;
        }
        
        .method-post {
            background: #ffc107;
            color: black;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            border-radius: 3px;
            margin: 15px 0;
            color: #856404;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            padding: 12px;
            border-radius: 3px;
            margin: 15px 0;
            color: #155724;
        }
        
        .navbar-guide {
            background: #333;
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">DB Hosting</div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <span class="nav-user"><?php echo htmlspecialchars($user['username']); ?></span>
                <a href="auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="api-guide">
            <h1>📡 Remote Database API Guide</h1>
            <p>Learn how to connect to your databases remotely from anywhere using our API.</p>

            <!-- API Token Section -->
            <div class="section">
                <h2>🔑 Your API Token</h2>
                <p>Your API token is your email address. Use it to authenticate all API requests.</p>
                <div class="token-box">
                    <label>Your Token:</label>
                    <input type="text" id="apiToken" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    <button class="copy-btn" onclick="copyToClipboard('apiToken')">📋 Copy Token</button>
                </div>
                <div class="warning">
                    <strong>⚠️ Security Warning:</strong> Keep your token secret. Anyone with your token can access your databases. Do not share it or commit it to public repositories.
                </div>
            </div>

            <!-- Quick Start Section -->
            <div class="section">
                <h2>🚀 Quick Start</h2>
                <p>Get started with 3 simple steps:</p>
                
                <h3>Step 1: Copy Your Token</h3>
                <p>Use the token displayed above (your email address)</p>
                
                <h3>Step 2: Choose Your Method</h3>
                <p>Select from cURL, JavaScript, Python, or another language below</p>
                
                <h3>Step 3: Make Your First Request</h3>
                <p>Use the examples to fetch your databases and start working with your data</p>
            </div>

            <!-- API Endpoints -->
            <div class="section">
                <h2>📚 API Endpoints</h2>
                <p>Base URL: <code>https://doom-dbhosting.xo.je/api/remote.php</code></p>
                
                <table class="endpoint-table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Parameters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method-badge method-get">GET</span></td>
                            <td><code>get</code></td>
                            <td>Get all databases or specific database</td>
                            <td><code>token</code>, <code>db_id</code> (optional)</td>
                        </tr>
                        <tr>
                            <td><span class="method-badge method-post">POST</span></td>
                            <td><code>update</code></td>
                            <td>Update database data</td>
                            <td><code>token</code>, <code>db_id</code>, <code>data</code></td>
                        </tr>
                        <tr>
                            <td><span class="method-badge method-post">POST</span></td>
                            <td><code>add_row</code></td>
                            <td>Add a new row to database</td>
                            <td><code>token</code>, <code>db_id</code>, <code>row</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Code Examples -->
            <div class="section">
                <h2>💻 Code Examples</h2>
                
                <!-- Tab buttons -->
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('curl')">cURL (Windows/Mac)</button>
                    <button class="tab-btn" onclick="switchTab('javascript')">JavaScript</button>
                    <button class="tab-btn" onclick="switchTab('python')">Python</button>
                    <button class="tab-btn" onclick="switchTab('csharp')">C# / PowerShell</button>
                </div>

                <!-- cURL Examples -->
                <div id="curl" class="tab-content active">
                    <h3>cURL Examples (Windows Command Prompt/PowerShell)</h3>
                    
                    <h4>1. Get All Databases</h4>
                    <p>Retrieve a list of all your databases:</p>
                    <div class="code-block">curl "https://doom-dbhosting.xo.je/api/remote.php?token=<?php echo htmlspecialchars($user['email']); ?>&action=get"</div>
                    <button class="copy-btn" onclick="copyCode('curl \"https://doom-dbhosting.xo.je/api/remote.php?token=<?php echo htmlspecialchars($user['email']); ?>&action=get\"')">Copy Command</button>
                    
                    <h4>2. Get Specific Database</h4>
                    <p>Retrieve data from a specific database (replace 1 with your db_id):</p>
                    <div class="code-block">curl "https://doom-dbhosting.xo.je/api/remote.php?token=<?php echo htmlspecialchars($user['email']); ?>&action=get&db_id=1"</div>
                    <button class="copy-btn" onclick="copyCode('curl \"https://doom-dbhosting.xo.je/api/remote.php?token=<?php echo htmlspecialchars($user['email']); ?>&action=get&db_id=1\"')">Copy Command</button>
                    
                    <h4>3. Add a Row (PowerShell)</h4>
                    <p>Add a new row to your database using PowerShell:</p>
                    <div class="code-block">$token = "<?php echo htmlspecialchars($user['email']); ?>"
$url = "https://doom-dbhosting.xo.je/api/remote.php"

$body = @{
    token = $token
    db_id = 1
    action = "add_row"
    row = @("John", "Doe", "john@example.com")
} | ConvertTo-Json

Invoke-WebRequest -Uri $url -Method POST -Body $body -ContentType "application/json" -UseBasicParsing</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelector('#curl .code-block').innerText)">Copy Script</button>
                    
                    <div class="warning">
                        <strong>⚠️ PowerShell Note:</strong> Use <code>-UseBasicParsing</code> to avoid the script execution security warning.
                    </div>
                </div>

                <!-- JavaScript Examples -->
                <div id="javascript" class="tab-content">
                    <h3>JavaScript Examples (Browser/Node.js)</h3>
                    
                    <h4>1. Get All Databases</h4>
                    <div class="code-block">const token = "<?php echo htmlspecialchars($user['email']); ?>";

fetch(`https://doom-dbhosting.xo.je/api/remote.php?token=${token}&action=get`)
    .then(response => response.json())
    .then(data => {
        console.log('Databases:', data);
        if (data.success) {
            data.databases.forEach(db => {
                console.log(`Database: ${db.name} (ID: ${db.id})`);
            });
        }
    })
    .catch(error => console.error('Error:', error));</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#javascript .code-block')[0].innerText)">Copy Code</button>
                    
                    <h4>2. Get Specific Database with Data</h4>
                    <div class="code-block">const token = "<?php echo htmlspecialchars($user['email']); ?>";
const dbId = 1; // Replace with your database ID

fetch(`https://doom-dbhosting.xo.je/api/remote.php?token=${token}&action=get&db_id=${dbId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Database:', data.database);
            console.log('Schema:', data.schema);
            console.log('Data:', data.data);
            console.log('Rows:', data.row_count);
        }
    })
    .catch(error => console.error('Error:', error));</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#javascript .code-block')[1].innerText)">Copy Code</button>
                    
                    <h4>3. Add a New Row</h4>
                    <div class="code-block">const token = "<?php echo htmlspecialchars($user['email']); ?>";
const dbId = 1; // Replace with your database ID

const newRow = ["John", "Doe", "john@example.com"];

fetch('https://doom-dbhosting.xo.je/api/remote.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        token: token,
        db_id: dbId,
        action: 'add_row',
        row: newRow
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Row added successfully!');
    } else {
        console.error('Error:', data.error);
    }
})
.catch(error => console.error('Error:', error));</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#javascript .code-block')[2].innerText)">Copy Code</button>
                </div>

                <!-- Python Examples -->
                <div id="python" class="tab-content">
                    <h3>Python Examples</h3>
                    
                    <h4>1. Get All Databases</h4>
                    <div class="code-block">import requests
import json

token = "<?php echo htmlspecialchars($user['email']); ?>"
url = "https://doom-dbhosting.xo.je/api/remote.php"

params = {
    'token': token,
    'action': 'get'
}

response = requests.get(url, params=params)
data = response.json()

if data['success']:
    for db in data['databases']:
        print(f"Database: {db['name']} (ID: {db['id']})")
else:
    print(f"Error: {data['error']}")</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#python .code-block')[0].innerText)">Copy Code</button>
                    
                    <h4>2. Get Specific Database</h4>
                    <div class="code-block">import requests

token = "<?php echo htmlspecialchars($user['email']); ?>"
db_id = 1  # Replace with your database ID
url = "https://doom-dbhosting.xo.je/api/remote.php"

params = {
    'token': token,
    'action': 'get',
    'db_id': db_id
}

response = requests.get(url, params=params)
data = response.json()

if data['success']:
    print("Database:", data['database'])
    print("Data rows:", data['row_count'])
    print("CSV Data:")
    for row in data['data']:
        print(row)
else:
    print(f"Error: {data['error']}")</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#python .code-block')[1].innerText)">Copy Code</button>
                    
                    <h4>3. Add a New Row</h4>
                    <div class="code-block">import requests

token = "<?php echo htmlspecialchars($user['email']); ?>"
db_id = 1  # Replace with your database ID
url = "https://doom-dbhosting.xo.je/api/remote.php"

payload = {
    'token': token,
    'db_id': db_id,
    'action': 'add_row',
    'row': ['John', 'Doe', 'john@example.com']
}

response = requests.post(url, json=payload)
data = response.json()

if data['success']:
    print("Row added successfully!")
else:
    print(f"Error: {data['error']}")</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#python .code-block')[2].innerText)">Copy Code</button>
                </div>

                <!-- C# / PowerShell Examples -->
                <div id="csharp" class="tab-content">
                    <h3>C# / PowerShell Examples</h3>
                    
                    <h4>1. PowerShell - Get All Databases</h4>
                    <div class="code-block">$token = "<?php echo htmlspecialchars($user['email']); ?>"
$url = "https://doom-dbhosting.xo.je/api/remote.php?token=$token&action=get"

$response = Invoke-WebRequest -Uri $url -UseBasicParsing
$data = $response.Content | ConvertFrom-Json

if ($data.success) {
    foreach ($db in $data.databases) {
        Write-Host "Database: $($db.name) (ID: $($db.id))"
    }
} else {
    Write-Error "Error: $($data.error)"
}</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#csharp .code-block')[0].innerText)">Copy Code</button>
                    
                    <h4>2. PowerShell - Add a Row</h4>
                    <div class="code-block">$token = "<?php echo htmlspecialchars($user['email']); ?>"
$url = "https://doom-dbhosting.xo.je/api/remote.php"
$dbId = 1

$body = @{
    token = $token
    db_id = $dbId
    action = "add_row"
    row = @("John", "Doe", "john@example.com")
} | ConvertTo-Json

$response = Invoke-WebRequest -Uri $url -Method POST -Body $body `
    -ContentType "application/json" -UseBasicParsing
$data = $response.Content | ConvertFrom-Json

if ($data.success) {
    Write-Host "Row added successfully!"
} else {
    Write-Error "Error: $($data.error)"
}</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#csharp .code-block')[1].innerText)">Copy Code</button>
                    
                    <h4>3. C# - Using HttpClient</h4>
                    <div class="code-block">using System;
using System.Net.Http;
using System.Threading.Tasks;
using Newtonsoft.Json;

class Program {
    static async Task Main() {
        string token = "<?php echo htmlspecialchars($user['email']); ?>";
        string url = "https://doom-dbhosting.xo.je/api/remote.php";
        
        using (HttpClient client = new HttpClient()) {
            // Get all databases
            string getUrl = $"{url}?token={token}&action=get";
            HttpResponseMessage response = await client.GetAsync(getUrl);
            string content = await response.Content.ReadAsStringAsync();
            Console.WriteLine(content);
        }
    }
}</div>
                    <button class="copy-btn" onclick="copyCode(document.querySelectorAll('#csharp .code-block')[2].innerText)">Copy Code</button>
                </div>
            </div>

            <!-- Your Databases -->
            <div class="section">
                <h2>🗂️ Your Databases</h2>
                <p>Here are your databases with their IDs (use these in API calls):</p>
                <table class="endpoint-table">
                    <thead>
                        <tr>
                            <th>Database Name</th>
                            <th>Database ID</th>
                            <th>API Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($databases->num_rows > 0): ?>
                            <?php while ($db = $databases->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($db['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($db['id']); ?></code></td>
                                    <td>Use <code>db_id=<?php echo htmlspecialchars($db['id']); ?></code> in API calls</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No databases created yet. <a href="dashboard.php">Create one now</a></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- FAQ Section -->
            <div class="section">
                <h2>❓ Frequently Asked Questions</h2>
                
                <h3>Q: What if I get a security warning in PowerShell?</h3>
                <p><strong>A:</strong> This is normal. Add <code>-UseBasicParsing</code> to your command to bypass it. This prevents PowerShell from interpreting any scripts in the response.</p>
                
                <h3>Q: Can I use the API from a mobile app?</h3>
                <p><strong>A:</strong> Yes! The API works from any platform that can make HTTP requests (iOS, Android, JavaScript, Python, etc.)</p>
                
                <h3>Q: Is my data encrypted?</h3>
                <p><strong>A:</strong> All API requests use HTTPS for transport security. We recommend keeping your token secure and not sharing it.</p>
                
                <h3>Q: How do I get my database ID?</h3>
                <p><strong>A:</strong> Check the "Your Databases" section on this page. Each database has a unique ID you can use in API calls.</p>
                
                <h3>Q: What if I forget my token?</h3>
                <p><strong>A:</strong> Your token is your email address. You can always see it in the "Your API Token" section on this page.</p>
                
                <h3>Q: Can I use this API with external tools like Zapier or IFTTT?</h3>
                <p><strong>A:</strong> Yes! Any tool that can make HTTP requests can use our API. Look for "Webhook" or "HTTP Request" features in these platforms.</p>
            </div>

            <!-- Security Best Practices -->
            <div class="section">
                <h2>🔒 Security Best Practices</h2>
                <ul>
                    <li><strong>Never share your token</strong> - Treat it like a password</li>
                    <li><strong>Use HTTPS only</strong> - All API calls are HTTPS encrypted</li>
                    <li><strong>Don't commit tokens to git</strong> - Use environment variables instead</li>
                    <li><strong>Rotate regularly</strong> - Generate new tokens periodically</li>
                    <li><strong>Use IP whitelisting</strong> - When possible, restrict API access to specific IPs</li>
                    <li><strong>Monitor API usage</strong> - Check your dashboard for unusual activity</li>
                </ul>
            </div>

            <!-- Back Button -->
            <div style="margin-top: 40px; text-align: center;">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab and mark button as active
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');
            alert('Copied to clipboard!');
        }
        
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('Code copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy. Please try again.');
            });
        }
    </script>
</body>
</html>
