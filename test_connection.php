<?php
// Test MySQL connection and database access
session_start();

// For testing without session
$_SESSION['user_id'] = 1; // Change to test user ID

require_once 'config/db.php';

echo "=== MySQL Connection Test ===\n\n";

// Test 1: Connection
if ($conn->connect_error) {
    die("Connection FAILED: " . $conn->connect_error);
}
echo "✅ Connected to: " . DB_NAME . " on " . DB_HOST . "\n\n";

// Test 2: Check users table
echo "=== Testing Users Table ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Users table exists. Count: " . $row['count'] . "\n\n";
} else {
    echo "❌ Error querying users: " . $conn->error . "\n\n";
}

// Test 3: Check databases table with backticks
echo "=== Testing Databases Table ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM `databases`");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✅ Databases table exists. Count: " . $row['count'] . "\n\n";
} else {
    echo "❌ Error querying databases: " . $conn->error . "\n\n";
}

// Test 4: Test prepared statement (like dashboard.php uses)
echo "=== Testing Prepared Statement ===\n";
$user_id = 1;
$query = "SELECT id, name, created_at FROM `databases` WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "❌ Prepare failed: " . $conn->error . "\n\n";
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo "❌ Execute failed: " . $stmt->error . "\n\n";
    } else {
        $result = $stmt->get_result();
        echo "✅ Query successful. Rows found: " . $result->num_rows . "\n";
        while ($db = $result->fetch_assoc()) {
            echo "   - ID: " . $db['id'] . ", Name: " . $db['name'] . "\n";
        }
        echo "\n";
    }
}

// Test 5: Test database.php specific query
echo "=== Testing database.php Verify Query ===\n";
$db_id = 1;
$user_id = 1;
$verify_query = "SELECT id, name FROM `databases` WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
if (!$stmt) {
    echo "❌ Prepare failed: " . $conn->error . "\n\n";
} else {
    $stmt->bind_param("ii", $db_id, $user_id);
    if (!$stmt->execute()) {
        echo "❌ Execute failed: " . $stmt->error . "\n\n";
    } else {
        $result = $stmt->get_result();
        echo "✅ Query successful. Rows found: " . $result->num_rows . "\n";
        if ($result->num_rows > 0) {
            $db = $result->fetch_assoc();
            echo "   - Found database: " . $db['name'] . "\n";
        } else {
            echo "   - No database found with id=" . $db_id . " and user_id=" . $user_id . "\n";
        }
        echo "\n";
    }
}

// Test 6: File system test
echo "=== Testing File System ===\n";
$test_dir = "data/user_1";
if (is_dir($test_dir)) {
    echo "✅ User data directory exists: " . $test_dir . "\n";
    $files = scandir($test_dir);
    echo "   Files: " . implode(", ", array_diff($files, ['.', '..'])) . "\n";
} else {
    echo "⚠️  User data directory does not exist: " . $test_dir . "\n";
    echo "   (This is OK if no databases have been created yet)\n";
}
echo "\n";

echo "=== All Tests Complete ===\n";
?>
