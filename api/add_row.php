<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
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
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

$csv_path = "../data/user_{$user_id}/database_{$db_id}.csv";

// Read existing data or initialize with headers
$data = [];
if (file_exists($csv_path)) {
    if (($handle = fopen($csv_path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    }
}

// Get headers from POST data
$headers = array_keys($_POST);
$headers = array_filter($headers, function($h) { return $h !== 'db_id'; });

// If no data, create headers row
if (empty($data)) {
    $data[] = $headers;
}

// Create new row from POST data
$newRow = [];
foreach ($headers as $header) {
    $newRow[] = isset($_POST[$header]) ? $_POST[$header] : '';
}

// Add row to data
$data[] = $newRow;

// Write back to CSV
if (($handle = fopen($csv_path, 'w')) !== false) {
    foreach ($data as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add row']);
}
?>
