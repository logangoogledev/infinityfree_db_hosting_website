<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db_name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($db_name)) {
    echo json_encode(['success' => false, 'error' => 'Database name is required']);
    exit;
}

// Create database record
$query = "INSERT INTO databases (user_id, name) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $db_name);

if ($stmt->execute()) {
    $db_id = $stmt->insert_id;
    
    // Create directory for user if not exists
    $user_dir = "../data/user_{$user_id}";
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0755, true);
    }
    
    // Handle CSV file upload if provided
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $csv_path = $user_dir . "/database_{$db_id}.csv";
        if (move_uploaded_file($_FILES['file']['tmp_name'], $csv_path)) {
            echo json_encode(['success' => true, 'db_id' => $db_id]);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'db_id' => $db_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create database']);
}
?>
