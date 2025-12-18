<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$db_id = isset($data['id']) ? intval($data['id']) : 0;
$user_id = $_SESSION['user_id'];

// Verify database belongs to user
$verify_query = "SELECT id FROM `databases` WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $db_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

// Delete from database
$delete_query = "DELETE FROM `databases` WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("ii", $db_id, $user_id);

if ($stmt->execute()) {
    // Delete CSV file
    $csv_path = "../data/user_{$user_id}/database_{$db_id}.csv";
    if (file_exists($csv_path)) {
        unlink($csv_path);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete database']);
}
?>
