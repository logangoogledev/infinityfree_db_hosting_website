<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
$db_query = "SELECT id, name, created_at FROM `databases` WHERE user_id = ? ORDER BY created_at DESC";
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
    <title>Dashboard - Database Hosting</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">DB Hosting</div>
            <div class="nav-menu">
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1>Your Databases</h1>
                <button class="btn btn-primary" onclick="openModal('newDbModal')">+ Create Database</button>
            </div>

            <div class="databases-grid">
                <?php while ($db = $databases->fetch_assoc()): ?>
                    <div class="database-card">
                        <h3><?php echo htmlspecialchars($db['name']); ?></h3>
                        <p class="db-id">ID: <?php echo htmlspecialchars($db['id']); ?></p>
                        <p class="db-date">Created: <?php echo date('Y-m-d H:i', strtotime($db['created_at'])); ?></p>
                        <div class="card-actions">
                            <a href="database.php?id=<?php echo $db['id']; ?>" class="btn btn-small">View</a>
                            <button class="btn btn-small btn-danger" onclick="deleteDatabase(<?php echo $db['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($databases->num_rows == 0): ?>
                <p class="no-databases">No databases yet. Create one to get started!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Database Modal -->
    <div id="newDbModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('newDbModal')">&times;</span>
            <h2>Create New Database</h2>
            <form id="newDbForm">
                <div class="form-group">
                    <label for="db-name">Database Name:</label>
                    <input type="text" id="db-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="db-file">Upload CSV File (optional):</label>
                    <input type="file" id="db-file" name="file" accept=".csv">
                </div>
                <button type="submit" class="btn">Create Database</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        document.getElementById('newDbForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/create_database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });

        function deleteDatabase(dbId) {
            if (confirm('Are you sure you want to delete this database?')) {
                fetch('api/delete_database.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({id: dbId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>
