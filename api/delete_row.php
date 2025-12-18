<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$db_id = isset($data['db_id']) ? intval($data['db_id']) : 0;
$row_index = isset($data['row_index']) ? intval($data['row_index']) : 0;

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

$csv_path = "../data/user_{$user_id}/database_{$db_id}.csv";

if (!file_exists($csv_path)) {
    echo json_encode(['success' => false, 'error' => 'Database file not found']);
    exit;
}

// Read CSV
$data_rows = [];
if (($handle = fopen($csv_path, 'r')) !== false) {
    while (($row = fgetcsv($handle)) !== false) {
        $data_rows[] = $row;
    }
    fclose($handle);
}

// Remove specified row
if (isset($data_rows[$row_index])) {
    unset($data_rows[$row_index]);
    $data_rows = array_values($data_rows); // Re-index array
}

// Write back to CSV
if (($handle = fopen($csv_path, 'w')) !== false) {
    foreach ($data_rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete row']);
}
?>
