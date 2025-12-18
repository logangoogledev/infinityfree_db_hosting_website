<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Validate input
if (empty($email) || empty($password)) {
    header('Location: ../index.php?error=Please fill in all fields');
    exit;
}

// Query user
$query = "SELECT id, username, password FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php?error=Invalid email or password');
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    header('Location: ../index.php?error=Invalid email or password');
    exit;
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

header('Location: ../dashboard.php');
exit;
?>
