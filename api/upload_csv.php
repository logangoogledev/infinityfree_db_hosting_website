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

// Check file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

// Ensure directory exists
$user_dir = "../data/user_{$user_id}";
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0755, true);
}

$csv_path = $user_dir . "/database_{$db_id}.csv";
if (move_uploaded_file($_FILES['file']['tmp_name'], $csv_path)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
}
?>
