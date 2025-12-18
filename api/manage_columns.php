<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db_id = isset($_POST['db_id']) ? intval($_POST['db_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Verify database belongs to user
$verify_query = "SELECT id FROM `databases` WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $db_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

$schema_path = "../data/user_{$user_id}/database_{$db_id}_schema.json";
$user_dir = "../data/user_{$user_id}";

// GET - Retrieve schema
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($schema_path)) {
        $schema = json_decode(file_get_contents($schema_path), true);
        echo json_encode(['success' => true, 'schema' => $schema]);
    } else {
        echo json_encode(['success' => true, 'schema' => []]);
    }
    exit;
}

// POST - Add column
if ($action === 'add_column') {
    $column_name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $column_type = isset($_POST['type']) ? $_POST['type'] : 'text';

    if (empty($column_name)) {
        echo json_encode(['success' => false, 'error' => 'Column name is required']);
        exit;
    }

    // Valid types
    $valid_types = ['text', 'integer', 'decimal', 'date', 'email', 'url'];
    if (!in_array($column_type, $valid_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid column type']);
        exit;
    }

    // Load existing schema
    $schema = [];
    if (file_exists($schema_path)) {
        $schema = json_decode(file_get_contents($schema_path), true);
    }

    // Check for duplicate column name
    foreach ($schema as $col) {
        if ($col['name'] === $column_name) {
            echo json_encode(['success' => false, 'error' => 'Column already exists']);
            exit;
        }
    }

    // Add new column
    $schema[] = ['name' => $column_name, 'type' => $column_type];

    // Save schema
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    if (file_put_contents($schema_path, json_encode($schema, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'schema' => $schema]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save schema']);
    }
    exit;
}

// DELETE - Remove column
if ($action === 'delete_column') {
    $column_index = isset($_POST['index']) ? intval($_POST['index']) : -1;

    if ($column_index < 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid column index']);
        exit;
    }

    // Load existing schema
    $schema = [];
    if (file_exists($schema_path)) {
        $schema = json_decode(file_get_contents($schema_path), true);
    }

    if (!isset($schema[$column_index])) {
        echo json_encode(['success' => false, 'error' => 'Column not found']);
        exit;
    }

    // Remove column
    unset($schema[$column_index]);
    $schema = array_values($schema); // Re-index

    // Save schema
    if (file_put_contents($schema_path, json_encode($schema, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'schema' => $schema]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save schema']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
