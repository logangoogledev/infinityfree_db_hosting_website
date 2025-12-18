<?php
/**
 * Remote Database API
 * Allows users to remotely access and sync their databases
 * 
 * Usage:
 * GET /api/remote.php?token=USER_TOKEN&db_id=1&action=get
 * POST /api/remote.php?token=USER_TOKEN&db_id=1&action=update
 */

require_once '../config/db.php';

// Get user token from header or query param
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$db_id = isset($_GET['db_id']) ? intval($_GET['db_id']) : (isset($_POST['db_id']) ? intval($_POST['db_id']) : 0);

// Validate token (using user email as simple token)
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing API token']);
    exit;
}

// Get user by email (token = email)
$user_query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $token);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$user = $user_result->fetch_assoc();
$user_id = $user['id'];

// Verify database belongs to user
if ($db_id > 0) {
    $verify_query = "SELECT id FROM `databases` WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $db_id, $user_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Database not found or unauthorized']);
        exit;
    }
}

// ==================== ACTIONS ====================

// GET - Retrieve database list or specific database
if ($action === 'get' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($db_id > 0) {
        // Get specific database
        $query = "SELECT id, name, created_at FROM `databases` WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $db_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $database = $result->fetch_assoc();
        
        $user_dir = "../data/user_{$user_id}";
        $csv_path = "{$user_dir}/database_{$db_id}.csv";
        $schema_path = "{$user_dir}/database_{$db_id}_schema.json";
        
        $csv_data = [];
        $schema = [];
        
        if (file_exists($csv_path)) {
            if (($handle = fopen($csv_path, 'r')) !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    $csv_data[] = $row;
                }
                fclose($handle);
            }
        }
        
        if (file_exists($schema_path)) {
            $schema = json_decode(file_get_contents($schema_path), true);
        }
        
        echo json_encode([
            'success' => true,
            'database' => $database,
            'schema' => $schema,
            'data' => $csv_data,
            'row_count' => count($csv_data) - 1 // Exclude header
        ]);
    } else {
        // Get all databases for user
        $query = "SELECT id, name, created_at FROM `databases` WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $databases = [];
        while ($db = $result->fetch_assoc()) {
            $databases[] = $db;
        }
        
        echo json_encode([
            'success' => true,
            'databases' => $databases,
            'count' => count($databases)
        ]);
    }
    exit;
}

// POST - Update database data
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db_id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'db_id required for update']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['data']) || !is_array($data['data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid data format']);
        exit;
    }
    
    $user_dir = "../data/user_{$user_id}";
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }
    
    $csv_path = "{$user_dir}/database_{$db_id}.csv";
    
    // Write CSV data
    if (($handle = fopen($csv_path, 'w')) !== false) {
        foreach ($data['data'] as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        
        // Update schema if provided
        if (isset($data['schema']) && is_array($data['schema'])) {
            $schema_path = "{$user_dir}/database_{$db_id}_schema.json";
            file_put_contents($schema_path, json_encode($data['schema'], JSON_PRETTY_PRINT));
        }
        
        echo json_encode(['success' => true, 'message' => 'Database updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to write data']);
    }
    exit;
}

// POST - Add row
if ($action === 'add_row' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db_id === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'db_id required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['row']) || !is_array($data['row'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'row array required']);
        exit;
    }
    
    $user_dir = "../data/user_{$user_id}";
    $csv_path = "{$user_dir}/database_{$db_id}.csv";
    
    $csv_data = [];
    if (file_exists($csv_path)) {
        if (($handle = fopen($csv_path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $csv_data[] = $row;
            }
            fclose($handle);
        }
    }
    
    $csv_data[] = $data['row'];
    
    if (($handle = fopen($csv_path, 'w')) !== false) {
        foreach ($csv_data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        echo json_encode(['success' => true, 'message' => 'Row added']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add row']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
